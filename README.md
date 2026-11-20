# cbosa

Repozytorium zawiera kod źródłowy oraz stanowi środowisko wykonawcze dla grupy dyskusyjnej "cbosa-648".

Każda zainteresowana osoba może dołączyć do grupy "[cbosa-648](https://groups.google.com/forum/#!forum/cbosa-648)", aby otrzymywać informacje o orzeczeniach sądów administracyjnych z zakresu dostępu do informacji publicznej opublikowanych w [Centralnej Bazie Sądów Administracynych](http://orzeczenia.nsa.gov.pl/cbo/query). Uczestnicy dzień w dzień otrzymują informacje o orzeczeniach za pośrednictwem poczty elektronicznej, jeżeli takie zostaną opublikowane.

## Harmonogram

Automat każdego dnia roboczego przeszukuje Centralną Bazę Orzeczeń Sądów Administracyjnych w godzinach:

- około północy – dla wszystkich sądów administracyjnych o sprawy o symbolu ```648*``` dla stron 1-202
- około 8:00 – dla wszystkich sądów administracyjnych o sprawy o symbolu sprawy ```648*``` dla stron 203-402
- około 16:00 – dla Naczelnego Sądu Administracyjnego o sprawy o symbolu sprawy ```648*``` dla stron 1-202

W przypadku znalezienia jakichkolwiek nowych orzeczeń wysyłane jest powiadomienie. W przypadku braku nowych orzeczeń podczas danego  

## Uruchomienie

Scraper to `cbosa.py` (Python 3.12, zależności w `requirements.txt`):

```bash
pip install -r requirements-dev.txt
make test                                        # testy parsera na zapisanej stronie wyników
PROXY_URL= python cbosa.py 0 dowolny 1           # tryb 1 (648*) bez proxy
```

Bez `SMTP_HOST` powiadomienie nie jest wysyłane, a jedynie zapisywane w `artifact/`. Gdy `SMTP_TO` jest różne od `ok`, powiadomienia trafiają na `naczelnik@jawne.info.pl` zamiast na listę z `config.json`. Proces kończy się kodem 1, gdy nie pobrano żadnego orzeczenia.

## Proxy

Scraper używa lokalnego proxy `hola-proxy` w celu obejścia blokad sieciowych. Binarka pobierana jest na żądanie z release'u upstreamu:

- Źródło: https://github.com/snawoot-proxies-forks/hola-proxy/releases/tag/v1.18.2-fork (asset `hola-proxy.linux-amd64`)
- Wersja i suma SHA-256 są zapisane w `Makefile` (`PROXY_VERSION`, `PROXY_SHA256`).
- Węzeł wyjściowy to Polska (`PROXY_COUNTRY=pl`) – od 11.09.2026 NSA zrywa połączenia TLS z domyślnych (US) węzłów Hola.
- `make proxy` pobiera binarkę, weryfikuje sumę kontrolną i uruchamia ją w tle (port 8080).
- `make run` uruchamia `cbosa.py` z proxy `PROXY_URL` (domyślnie `http://127.0.0.1:8080`); pusty `PROXY_URL=` oznacza połączenie bezpośrednie.

Aktualizacja do nowszego release'u: zmień `PROXY_VERSION` i `PROXY_SHA256` w `Makefile` na wartości z odpowiedniego releasu (`gh release view <tag> --repo snawoot-proxies-forks/hola-proxy --json assets`).

Dotyczy wyłącznie środowiska CI (Linux x86_64). Lokalnie na innym OS wymaga osobnej binarki dla danej platformy.

## Utrudnienia ze strony Naczelnego Sądu Administracyjnego

Wskazać należy, że Naczelny Sąd Administracyjny podejmuje [aktywne działania blokujące działanie narzędzia](https://ochrona.jawne.info.pl/2015/11/10/otwarte-dane-wsparciem-partnerstwa-obywatel-panstwo/), co skutkuje drastycznym wzrostem kosztu infrastruktury wymaganej na obchodzenie wprowadzanych zabezpieczeń. To zaś powoduje m. in. dostępność listy dyskusyjnej wyłącznie dla spraw z zakresu dostępu do informacji publicznej, pomimo zainteresowaniami innymi tematami. Istnieje możliwość odpłatnego uzyskania subskrypcji orzeczeń innych tematów – szczegóły do indywidualnego ustalenia po kontakcie pod adresem `naczelnik@jawne.info.pl`.
