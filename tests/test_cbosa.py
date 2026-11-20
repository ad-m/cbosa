import json
import os
import subprocess

import pytest

import cbosa

FIXTURES = os.path.join(os.path.dirname(__file__), "fixtures")


def fixture(name):
    with open(os.path.join(FIXTURES, name), encoding="utf-8") as f:
        return f.read()


def test_parse_serp_returns_keys_matching_state_format():
    rows = cbosa.parse_serp(fixture("serp_648.html"))
    assert len(rows) == 10
    assert "I SAB/Op 32/26 - Postanowienie WSA w Opolu z 2026-11-18" in rows
    assert all("info-list" in table.split(">", 1)[0] for table in rows.values())


def test_parse_serp_without_results_signals_captcha():
    assert cbosa.parse_serp("<html><body><form>captcha</form></body></html>") is None


@pytest.mark.parametrize("mode", range(5))
def test_dump_state_matches_php_format(mode):
    path = os.path.join(os.path.dirname(FIXTURES), "..", f"{mode}.json")
    with open(path, encoding="utf-8") as f:
        raw = f.read()
    assert cbosa.dump_state(json.loads(raw)) == raw


class FakeClient:
    def __init__(self, pages):
        self.pages = pages
        self.requested = []

    def get(self, url):
        self.requested.append(url)
        return self.pages.get(url)

    def post(self, url, data):
        self.requested.append(url)
        return self.pages.get(url)


def test_scrape_skips_known_and_stops_on_empty_response():
    serp = fixture("serp_648.html")
    keys = list(cbosa.parse_serp(serp))
    client = FakeClient({f"{cbosa.BASE_URL}/cbo/search": serp})
    new, seen, parsed = cbosa.scrape(client, "sad=dowolny", range(2, 5), keys[:4])
    assert list(new) == keys[4:]
    assert (seen, parsed) == (10, 1)
    assert client.requested[-1] == f"{cbosa.BASE_URL}/cbo/find?p=2"
