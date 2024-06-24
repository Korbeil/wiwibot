FROM thecodingmachine/php:8.3-v4-cli

ARG CACHE_DATE=2024-06-24

COPY app /var/www/html
WORKDIR /var/www/html

USER root
RUN composer install

CMD ["php", "bin/console", "wiwi:bot", "-vvv"]
