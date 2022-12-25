#!/bin/bash

chown -R www-data:www-data .

composer install

docker-php-entrypoint

apache2-foreground
