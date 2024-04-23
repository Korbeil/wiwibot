FROM thecodingmachine/php:8.3-v4-cli

COPY app /var/www/html
WORKDIR /var/www/html

USER root
RUN composer install

CMD ["php", "bin/console", "wiwi:bot"]
