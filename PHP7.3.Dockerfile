FROM edbizarro/gitlab-ci-pipeline-php:7.3-alpine

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

RUN mkdir -p ~/.ssh && touch ~/.ssh/config && echo -e "Host *\n\tStrictHostKeyChecking no\n\nUserKnownHostsFile=/dev/null\n" >> ~/.ssh/config

WORKDIR /var/www/html
