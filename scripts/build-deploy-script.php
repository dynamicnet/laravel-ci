#!/usr/local/bin/php
<?php

require __DIR__."/vendor/autoload.php";
require __DIR__."/functions.php";
$outputs = [];

check_private_key($outputs);

// Sanitize deploy dir
check_safe_dir($outputs);

$DEPLOY_DIR = $outputs["DEPLOY_DIR"];

// Extract VAR
$DEPLOY_TARGET_HOST = get_env("DEPLOY_TARGET_HOST");
$DEPLOY_USER = get_env("DEPLOY_USER");
$DEPLOY_PRIVATE_KEY = get_env("DEPLOY_PRIVATE_KEY");
$DEPLOY_HOST_PHP_PATH = get_env("DEPLOY_HOST_PHP_PATH");
$DEPLOY_ARTISAN_PATH = get_env("DEPLOY_ARTISAN_PATH");
$APP_KEY = get_env("DOTENV_APP_KEY");
$RUN_MIGRATION = !cast_bool(get_env("DO_NOT_RUN_MIGRATION", false));


// Génère le script de déploiement
echo "#!/bin/sh".PHP_EOL;

echo "mkdir -p ~/.ssh/\n";
echo 'echo -e "Host '.$DEPLOY_TARGET_HOST.'\n\tStrictHostKeyChecking no\n\tUser '.$DEPLOY_USER.'\n\n" > ~/.ssh/config'."\n";
echo 'echo -e "'.$DEPLOY_PRIVATE_KEY.'" > ~/.ssh/id_rsa'."\n";
echo 'chmod 0400 ~/.ssh/id_rsa'."\n";
echo 'ssh-add ~/.ssh/id_rsa'."\n";

build_supervisor_config($outputs);


// On lance un rsync qui va supprimer les fichiers sur la destination si il ne sont pas présents sur la source
// on exclus donc "storage" pour ne pas supprimer tous les fichiers sur l'env de destination
echo "rsync --recursive --compress --delete --times -e \"ssh\" --exclude 'storage/*' --exclude '.git' --exclude 'node_modules' ./ {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST}:{$DEPLOY_DIR}\n";

// Synchro de storage sans le flag --delete
echo "rsync --recursive --compress --times -e \"ssh\" ./storage/ {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST}:{$DEPLOY_DIR}storage\n";


apply_chown($outputs);
apply_chmod($outputs);

if ("" == $APP_KEY) {
    echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"{$DEPLOY_HOST_PHP_PATH} {$DEPLOY_ARTISAN_PATH} key:generate\"\n";
} else {
    echo "echo '\033[38;5;226mAn APP_KEY is given in the CI/CD env var, using it \(and dont generate a new one\)\033[0m'\n";
}

echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"{$DEPLOY_HOST_PHP_PATH} {$DEPLOY_ARTISAN_PATH} storage:link\"\n";
echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"{$DEPLOY_HOST_PHP_PATH} {$DEPLOY_ARTISAN_PATH} route:cache\"\n";
echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"{$DEPLOY_HOST_PHP_PATH} {$DEPLOY_ARTISAN_PATH} view:cache\"\n";

if ($RUN_MIGRATION) {
    echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"{$DEPLOY_HOST_PHP_PATH} {$DEPLOY_ARTISAN_PATH} migrate --force --no-interaction\"\n";
} else {
    echo "echo '\033[38;5;226mSkip migrations (DO_NOT_RUN_MIGRATION)\033[0m'\n";
}

symlink_supervisor_config_file($outputs);
update_supervisor_processes($outputs);

set_crontab($outputs);
