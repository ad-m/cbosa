"""Powiadomienia o nowych orzeczeniach z Centralnej Bazy Orzeczeń Sądów Administracyjnych.

Użycie: python cbosa.py RANGE COURT MODE

RANGE - numer paczki stron wyników (0 -> strony 2-101, 1 -> 102-201, ...); strona 1 zawsze jest analizowana
COURT - wartość pola "sad" formularza, już zakodowana (np. "dowolny")
MODE  - indeks wpisu w config.json; stan przechowywany jest w pliku MODE.json
"""

import json
import os
import random
import smtplib
import sys
import time
from datetime import datetime
from email.mime.text import MIMEText
from email.utils import formataddr
from zoneinfo import ZoneInfo

import requests
from bs4 import BeautifulSoup

BASE_URL = "https://orzeczenia.nsa.gov.pl"
PER_RUN = 100
NEW_SOFT_LIMIT = 100
NEW_HARD_LIMIT = 200
FALLBACK_EMAIL = "naczelnik@jawne.info.pl"
USER_AGENT = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:13.0) Gecko/20100101 Firefox/13.0"
TZ = ZoneInfo("Europe/Warsaw")


class Client:
    def __init__(self, proxy=None, sleep=(10, 15)):
        self.session = requests.Session()
        self.session.headers["User-Agent"] = USER_AGENT
        if proxy:
            self.session.proxies = {"http": proxy, "https": proxy}
        self.sleep = sleep
        self.referer = None

    def request(self, method, url, data=None):
        if self.sleep:
            time.sleep(random.randint(*self.sleep))
        print(f"[{method}] {url}", flush=True)
        headers = {"Referer": self.referer} if self.referer else {}
        if data is not None:
            headers["Content-Type"] = "application/x-www-form-urlencoded"
        try:
            resp = self.session.request(method, url, data=data, headers=headers, timeout=120)
        except requests.RequestException as e:
            print(f"Błąd połączenia: {e}", flush=True)
            return None
        print(f"[HTTP {resp.status_code}] {len(resp.content)} bajtów", flush=True)
        self.referer = url
        if resp.status_code != 200 or not resp.content:
            if 0 < len(resp.content) < 2000:
                print(resp.text, flush=True)
            return None
        return resp.text

    def get(self, url):
        return self.request("GET", url)

    def post(self, url, data):
        return self.request("POST", url, data=data)


def parse_serp(html):
    """Zwraca {klucz orzeczenia: HTML tabeli} lub None, gdy na stronie nie ma listy (np. CAPTCHA)."""
    soup = BeautifulSoup(html, "html.parser")
    tables = soup.select("table.info-list")
    if not tables:
        return None
    return {table.find("a").get_text().strip(): str(table) for table in tables}


def load_state(path):
    if not os.path.exists(path):
        return []
    with open(path, encoding="utf-8") as f:
        return json.load(f)


def dump_state(keys):
    # Ten sam format co json_encode(JSON_PRETTY_PRINT) w PHP, aby nie przepisywać całych plików stanu.
    return json.dumps(keys, indent=4).replace("/", "\\/")


def build_message(output, summary):
    return (
        '<html><head><meta charset="utf-8"><base href="https://orzeczenia.nsa.gov.pl/" target="_blank">'
        "<style>a:link, a:active {color:#1155CC; text-decoration:none} "
        "a:hover {text-decoration:underline; cursor: pointer} a:visited{color:#6611CC}</style>"
        f"</head><body>{''.join(output)}<p>{summary}</p></body></html>"
    )


def send_mail(to, subject, html, title):
    msg = MIMEText(html, "html", "utf-8")
    msg["Subject"] = subject
    msg["From"] = formataddr((f"CBOSA-{title}", os.environ["SMTP_FROM"]))
    msg["To"] = to
    with smtplib.SMTP(os.environ["SMTP_HOST"], 587) as smtp:
        smtp.starttls()
        smtp.login(os.environ["SMTP_USER"], os.environ["SMTP_PASSWORD"])
        smtp.send_message(msg)


def scrape(client, payload, pages, known):
    """Przegląda stronę 1 (wynik wyszukiwania) oraz podane strony. Zwraca (nowe, liczba orzeczeń, liczba stron)."""
    known = set(known)
    new = {}
    seen = 0
    parsed = 0
    client.get(f"{BASE_URL}/cbo/query")
    html = client.post(f"{BASE_URL}/cbo/search", payload)
    for page in [None, *pages]:
        if page is not None:
            html = client.get(f"{BASE_URL}/cbo/find?p={page}")
        if html is None:
            print("Przerwano z powodu pustej odpowiedzi z serwera", flush=True)
            break
        rows = parse_serp(html)
        if rows is None:
            print("Przerwano z powodu wykrycia CAPTCHY", flush=True)
            break
        parsed += 1
        for key, table in rows.items():
            if key not in known:
                known.add(key)
                new[key] = table
            seen += 1
        if len(new) > NEW_SOFT_LIMIT:
            print(f"Ponad {NEW_SOFT_LIMIT} nowych orzeczeń, kończę przeglądanie", flush=True)
            break
    return new, seen, parsed


def main(argv):
    range_, court, mode = int(argv[1]), argv[2], int(argv[3])
    with open("config.json", encoding="utf-8") as f:
        config = json.load(f)[mode]
    start = range_ * PER_RUN + 2
    end = start + PER_RUN - 1
    payload = f"sad={court}&{config['query']}"
    print(f"Tryb {mode} ({config['title']}), strony 1 i {start}-{end}", flush=True)

    proxy = os.environ.get("PROXY_URL") or os.environ.get("HTTP_PROXY")
    client = Client(proxy=proxy)
    try:
        print("IP:", client.session.get("https://api.ipify.org", timeout=30).text, flush=True)
    except requests.RequestException as e:
        print(f"Nie ustalono IP: {e}", flush=True)

    state_path = f"{mode}.json"
    known = load_state(state_path)
    new, seen, pages = scrape(client, payload, range(start, end + 1), known)

    if seen == 0:
        print("Nie pobrano żadnych orzeczeń - błąd połączenia lub blokada", flush=True)
        return 1
    if len(new) > NEW_HARD_LIMIT:
        raise RuntimeError(f"Notification overload ({len(new)} > {NEW_HARD_LIMIT}). Has there been a filter failure?")

    keys = known + list(new)
    now = datetime.now(TZ)
    os.makedirs("artifact", exist_ok=True)
    with open(state_path, "w", encoding="utf-8") as f:
        f.write(dump_state(keys))
    with open(now.strftime("artifact/%Y-%m-%d-%H-%M.json"), "w", encoding="utf-8") as f:
        f.write(dump_state(keys))

    summary = f"Przeanalizowano {pages} stron wyników dla {payload} ({start}-{end}) znajdując {seen} orzeczeń, w tym {len(new)} nowych."
    print(summary, flush=True)
    message = build_message(new.values(), summary)
    with open(now.strftime("artifact/%Y-%m-%d-%H-%M.html"), "w", encoding="utf-8") as f:
        f.write(message)

    if not new:
        print("Wstrzymano się od powiadomienia", flush=True)
        return 0
    to = config["email"] if os.environ.get("SMTP_TO") == "ok" else FALLBACK_EMAIL
    if not os.environ.get("SMTP_HOST"):
        print(f"Brak SMTP_HOST - pominięto wysyłkę do {to}", flush=True)
        return 0
    send_mail(to, now.strftime(f"Orzecznictwo {config['title']} na dzień %d.%m.%Y"), message, config["title"])
    print(f"Wysłano powiadomienie do {to}", flush=True)
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
