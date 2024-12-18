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
 * DEPLOY_CHMOD_DIR = 755
 * DEPLOY_CHMOD_FILE = 644
 */

echo "#!/bin/sh".PHP_EOL;

if (! isset($_ENV["DEPLOY_PRIVATE_KEY"])) {
    echo "echo '/!\   Missing an SSH key to deploy, var DEPLOY_PRIVATE_KEY   /!\ '".PHP_EOL;
    echo "exit 0".PHP_EOL;
    exit(0);
}

// Sanitize deploy dir
$DEPLOY_DIR = $_ENV["DEPLOY_DIR"] ?? "";
$DEPLOY_DIR = realpath($DEPLOY_DIR);

if ("/" == $DEPLOY_DIR) {
    echo "/!\  Dangerous DEPLOY_DIR  /!\ ".PHP_EOL;
    exit(1);
}

echo "mkdir -p ~/.ssh/\n";
echo 'echo -e "Host '.$_ENV["DEPLOY_TARGET_HOST"].'\n\tStrictHostKeyChecking no\n\tUser '.$_ENV["DEPLOY_USER"].'\n\n" > ~/.ssh/config'."\n";
echo 'echo -e "'.$_ENV["DEPLOY_PRIVATE_KEY"].'" > ~/.ssh/id_rsa'."\n";
echo 'chmod 0400 ~/.ssh/id_rsa'."\n";
echo 'ssh-add ~/.ssh/id_rsa'."\n";


// On lance un rsync qui va supprimer les fichiers sur la destination si il ne sont pas présents sur la source
// on exclus donc "storage" pour ne pas supprimer tous les fichiers sur l'env de destination
echo "rsync --recursive --compress --delete --times -e \"ssh\" --exclude 'storage/*' --exclude '.git' ./ {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]}:{$_ENV["DEPLOY_DIR"]}\n";

// Synchro de storage sans le flag --delete
echo "rsync --recursive --compress --times -e \"ssh\" ./storage/ {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]}:{$_ENV["DEPLOY_DIR"]}/storage\n";

$DEPLOY_CHOWN_USER = $_ENV["DEPLOY_CHOWN_USER"] ?? "";
$DEPLOY_CHOWN_GROUP = $_ENV["DEPLOY_CHOWN_GROUP"] ?? "";

if ($DEPLOY_CHOWN_USER || $DEPLOY_CHOWN_GROUP) {
    echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"chown -R {$DEPLOY_CHOWN_USER}:{$DEPLOY_CHOWN_GROUP} {$_ENV["DEPLOY_DIR"]}\"\n";
}

$DEPLOY_CHMOD_DIR = $_ENV["DEPLOY_CHMOD_DIR"] ?? "";

if ($DEPLOY_CHMOD_DIR) {
    echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"find {$_ENV["DEPLOY_DIR"]} -type d -exec chmod {$DEPLOY_CHMOD_DIR} {} \;\"\n";
}

$DEPLOY_CHMOD_FILE = $_ENV["DEPLOY_CHMOD_FILE"] ?? "";

if ($DEPLOY_CHMOD_FILE) {
    echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"find {$_ENV["DEPLOY_DIR"]} -type f -exec chmod {$DEPLOY_CHMOD_FILE} {} \;\"\n";
}

$APP_KEY = $_ENV["DOTENV_APP_KEY"] ?? "";
if ("" == $APP_KEY) {
    echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"{$_ENV["DEPLOY_HOST_PHP_PATH"]} {$_ENV["DEPLOY_ARTISAN_PATH"]} key:generate\"\n";
} else {
    echo "echo '\033[38;5;226mAn APP_KEY is given in the CI/CD env var, using it \(and dont generate a new one\)\033[0m'\n";
}

echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"{$_ENV["DEPLOY_HOST_PHP_PATH"]} {$_ENV["DEPLOY_ARTISAN_PATH"]} storage:link\"\n";
echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"{$_ENV["DEPLOY_HOST_PHP_PATH"]} {$_ENV["DEPLOY_ARTISAN_PATH"]} route:cache\"\n";
echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"{$_ENV["DEPLOY_HOST_PHP_PATH"]} {$_ENV["DEPLOY_ARTISAN_PATH"]} view:cache\"\n";

$RUN_MIGRATION = ! isset($_ENV["DO_NOT_RUN_MIGRATION"]);

if ($RUN_MIGRATION) {
    echo "ssh -ttq {$_ENV["DEPLOY_USER"]}@{$_ENV["DEPLOY_TARGET_HOST"]} \"{$_ENV["DEPLOY_HOST_PHP_PATH"]} {$_ENV["DEPLOY_ARTISAN_PATH"]} migrate --force --no-interaction\"\n";
} else {
    echo "echo '\033[38;5;226mSkip migrations (DO_NOT_RUN_MIGRATION)\033[0m'\n";
}
