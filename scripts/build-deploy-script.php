#!/usr/local/bin/php
<?php

/**
 * DEPLOY_HOST_PHP_PATH = binaire ou commande php à lancer sur le cible du déploiement pour lancer les commandes artisans
 *  par exemple "docker exec PHP74 php" en KWDEV ou "php7.4" en PROD
 * DEPLOY_TARGET_HOST = 192.168.0.60
 * DEPLOY_USER = root
 * DEPLOY_PRIVATE_KEY = "qsdfsdfsdfsdfsdfsdfsdf"
 * DEPLOY_DIR = /disk2/www/p/p.kwdev/bla/www/
 * DEPLOY_CHOWN_USER = www-data (user owner of the deployed files)
 * DEPLOY_CHOWN_GROUP = www-data (group owner of the deployed files)
 * DEPLOY_ARTISAN_PATH = /var/www/html/p/pierre-fabre.dev/library/www/artisan (path artisan sur l'hote (en kwdev dans le container))
 */


echo "#!/bin/sh\n";

if (! isset($_ENV["DEPLOY_PRIVATE_KEY"])) {
    echo "exit 0\n";
    exit(0);
}

echo "mkdir -p ~/.ssh/\n";
echo 'echo -e "Host '.$_ENV["DEPLOY_TARGET_HOST"].'\n\tStrictHostKeyChecking no\n\tUser '.$_ENV["DEPLOY_USER"].'\n\n" > ~/.ssh/config'."\n";
echo 'echo -e "'.$_ENV["DEPLOY_PRIVATE_KEY"].'" > ~/.ssh/id_rsa'."\n";
echo 'chmod 0400 ~/.ssh/id_rsa'."\n";
echo 'ssh-add ~/.ssh/id_rsa'."\n";


// On lance un rsync qui va supprimer les fichiers sur la destination si il ne sont pas présents sur la source
// on exclus donc "storage" pour ne pas supprimer tous les fichiers sur l'env de destination
echo "rsync -auz -e \"ssh\" --delete --exclude 'storage/*' --exclude '.git' ./ {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]}:{$_ENV["DEPLOY_DIR"]}\n";

// Synchro de storage sans le flag --delete
echo "rsync -auz -e \"ssh\" ./storage/ {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]}:{$_ENV["DEPLOY_DIR"]}/storage\n";

if (! isset($_ENV["DEPLOY_CHOWN_USER"])) {
    echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"chown -R {$_ENV["DEPLOY_CHOWN_USER"]}:{$_ENV["DEPLOY_CHOWN_GROUP"]} {$_ENV["DEPLOY_DIR"]}\"\n";
}

echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"{$_ENV["DEPLOY_HOST_PHP_PATH"]} {$_ENV["DEPLOY_ARTISAN_PATH"]} key:generate\"\n";
echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"{$_ENV["DEPLOY_HOST_PHP_PATH"]} {$_ENV["DEPLOY_ARTISAN_PATH"]} migrate\"\n";

#ssh -ttq root@192.168.0.60 "docker exec PHP74 php /var/www/html/p/pierre-fabre.dev/library/www/artisan route:cache"
#ssh root@192.168.0.60 "docker exec PHP74 php /var/www/html/p/pierre-fabre.dev/library/www/artisan storage:link"
