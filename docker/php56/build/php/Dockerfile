FROM php:5.6-cli

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip libfreetype6-dev libjpeg62-turbo-dev && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/

RUN docker-php-ext-install gd

RUN curl --silent --show-error https://getcomposer.org/installer | php

# Endless command to keep container running
CMD tail -f /dev/null