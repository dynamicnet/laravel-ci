FROM php:8.2-cli-alpine3.20

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && curl -fsSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    -o /usr/local/bin/install-php-extensions && chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions curl ftp fileinfo pdo_mysql mysqli openssl pdo_sqlite gd mbstring zip bcmath intl exif

COPY --from=composer:2.7.8 /usr/bin/composer /usr/bin/composer

WORKDIR /tmp
USER root

RUN apk update
RUN apk add git rsync

RUN rm -rf /tmp/* \
    /usr/includes/* \
    /usr/share/man/* \
    /var/cache/apk/* \
    /var/tmp/*

COPY --from=mhart/alpine-node:16 /usr/bin/node /usr/bin/
COPY --from=mhart/alpine-node:16 /usr/lib/libgcc* /usr/lib/libstdc* /usr/lib/* /usr/lib/

COPY scripts/ /scripts/
RUN chmod +x /scripts/*

USER php

RUN mkdir -p ~/.ssh && touch ~/.ssh/config && echo -e "Host *\n\tStrictHostKeyChecking no\n\nUserKnownHostsFile=/dev/null\n" >> ~/.ssh/config

WORKDIR /var/www/html
