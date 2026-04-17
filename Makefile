.PHONY: clean proxy run logs

RANGE?=0
COURT?=dowolny
SYMBOL?=648*
MODE?=uzasadnione

PROXY_VERSION=v1.18.2-fork
PROXY_URL=https://github.com/snawoot-proxies-forks/hola-proxy/releases/download/$(PROXY_VERSION)/hola-proxy.linux-amd64
PROXY_SHA256=558279149334988ea163786b92195593dc86dc41b86e122f4a6f8edca711b96c
PROXY_BIN=/tmp/hola-proxy
PROXY_LOG=/tmp/hola-proxy.log
PROXY_PIDFILE=/tmp/hola-proxy.pid

clean:
	-test -f $(PROXY_PIDFILE) && kill `cat $(PROXY_PIDFILE)` 2>/dev/null; rm -f $(PROXY_PIDFILE)
	-docker rm --force cbosa-php

proxy:
	curl -fsSL -o $(PROXY_BIN) $(PROXY_URL)
	echo "$(PROXY_SHA256)  $(PROXY_BIN)" | sha256sum -c -
	chmod +x $(PROXY_BIN)
	$(PROXY_BIN) -bind-address 0.0.0.0:8080 > $(PROXY_LOG) 2>&1 & echo $$! > $(PROXY_PIDFILE)
	for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		if curl -s -o /dev/null --max-time 2 http://127.0.0.1:8080/ ; then echo "hola-proxy ready (pid=`cat $(PROXY_PIDFILE)`)"; exit 0; fi; \
		sleep 1; \
	done; \
	echo "hola-proxy failed to respond on 8080 within 15s"; cat $(PROXY_LOG); exit 1

run:
	docker run --rm -v $$(pwd):/data/public -e SMTP_HOST -e SMTP_USER -e SMTP_PASSWORD -e SMTP_FROM -e SMTP_TO -e HTTP_PROXY="http://hola-proxy:8080" --add-host=hola-proxy:host-gateway --name cbosa-php --workdir /data/public chialab/php:5.6 php -d memory_limit=-1 -d date.timezone=Europe/Warsaw scrap.php "${RANGE}" "${COURT}" "${MODE}"

logs:
	@cat $(PROXY_LOG) 2>/dev/null || echo "no proxy log at $(PROXY_LOG)"
