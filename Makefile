.PHONY: run

run:
	docker run -v $$PWD:/data/public --workdir /data/public -it h1cr.io/website/php-apache:latest php scrap.py