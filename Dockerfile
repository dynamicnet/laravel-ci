FROM edbizarro/gitlab-ci-pipeline-php:7.4-alpine

WORKDIR /tmp
USER root

RUN apk update
RUN apk add git rsync

RUN rm -rf /tmp/* \
    /usr/includes/* \
    /usr/share/man/* \
    /var/cache/apk/* \
    /var/tmp/*

COPY scripts/ /scripts/
RUN chmod +x /scripts/*

USER php
WORKDIR /var/www/html
