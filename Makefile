.PHONY: clean proxy run test logs

RANGE?=0
COURT?=dowolny
SYMBOL?=648*
MODE?=uzasadnione

PROXY_VERSION=v1.18.2-fork
PROXY_DOWNLOAD_URL=https://github.com/snawoot-proxies-forks/hola-proxy/releases/download/$(PROXY_VERSION)/hola-proxy.linux-amd64
PROXY_SHA256=558279149334988ea163786b92195593dc86dc41b86e122f4a6f8edca711b96c
PROXY_BIN=/tmp/hola-proxy
PROXY_LOG=/tmp/hola-proxy.log
PROXY_PIDFILE=/tmp/hola-proxy.pid
PROXY_COUNTRY?=pl
PROXY_URL?=http://127.0.0.1:8080

clean:
	-test -f $(PROXY_PIDFILE) && kill `cat $(PROXY_PIDFILE)` 2>/dev/null; rm -f $(PROXY_PIDFILE)

proxy:
	curl -fsSL -o $(PROXY_BIN) $(PROXY_DOWNLOAD_URL)
	echo "$(PROXY_SHA256)  $(PROXY_BIN)" | sha256sum -c -
	chmod +x $(PROXY_BIN)
	$(PROXY_BIN) -bind-address 0.0.0.0:8080 -country $(PROXY_COUNTRY) > $(PROXY_LOG) 2>&1 & echo $$! > $(PROXY_PIDFILE)
	for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		if curl -s -o /dev/null --max-time 2 http://127.0.0.1:8080/ ; then echo "hola-proxy ready (pid=`cat $(PROXY_PIDFILE)`)"; exit 0; fi; \
		sleep 1; \
	done; \
	echo "hola-proxy failed to respond on 8080 within 15s"; cat $(PROXY_LOG); exit 1

run:
	PROXY_URL="$(PROXY_URL)" python cbosa.py "${RANGE}" "${COURT}" "${MODE}"

test:
	python -m pytest -q tests

logs:
	@cat $(PROXY_LOG) 2>/dev/null || echo "no proxy log at $(PROXY_LOG)"
