.PHONY: network clean proxy run

PHP_CMD?=php -d memory_limit=-1 scrap.php
PHP_ARGV?=

network:
	docker network create cbosa  || echo 'Network exists'

clean:
	docker rm --force hola-proxy cbosa-php

proxy: network
	docker run -d -p 8080:8080 --name hola-proxy --network cbosa --restart unless-stopped yarmak/hola-proxy

run: network
	docker run --rm -v $$(pwd):/data/public -e SMTP_HOST -e SMTP_USER -e SMTP_PASSWORD -e SMTP_FROM -e SMTP_TO -e HTTP_PROXY="http://hola-proxy:8080" --network cbosa --name cbosa-php --workdir /data/public chialab/php:5.6 bash -c '${PHP_CMD} ${PHP_ARGV}'

logs:
	docker logs hola-proxy
