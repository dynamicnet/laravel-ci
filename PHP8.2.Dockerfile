FROM php:8.2-cli-alpine3.20

RUN apk update && apk add \
    nodejs \
    npm \
    rsync \
    git \
    curl \
    unzip \
    && curl -fsSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    -o /usr/local/bin/install-php-extensions && chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions curl ftp fileinfo pdo_mysql mysqli openssl pdo_sqlite gd mbstring zip bcmath intl exif

COPY --from=composer:2.8.1 /usr/bin/composer /usr/bin/composer

WORKDIR /tmp
USER root

RUN rm -rf /tmp/* \
    /usr/includes/* \
    /usr/share/man/* \
    /var/cache/apk/* \
    /var/tmp/*

COPY scripts/ /scripts/
RUN chmod +x /scripts/*

RUN mkdir -p ~/.ssh && touch ~/.ssh/config && echo -e "Host *\n\tStrictHostKeyChecking no\n\nUserKnownHostsFile=/dev/null\n" >> ~/.ssh/config

WORKDIR /var/www/html
