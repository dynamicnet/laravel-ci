#!/usr/local/bin/php
<?php

require __DIR__."/vendor/autoload.php";
require __DIR__."/functions.php";

if (false == get_env("DEPLOY_PRIVATE_KEY", false)) {
    echo "echo '/!\ Missing an SSH key to deploy, set DEPLOY_PRIVATE_KEY env var /!\ '".PHP_EOL;
    echo "exit 1".PHP_EOL;
    exit(1);
}

// Sanitize deploy dir
$DEPLOY_DIR = get_env("DEPLOY_DIR", "");

if (!check_safe_dir($DEPLOY_DIR, $error)) {
    echo "echo '/!\  Dangerous DEPLOY_DIR  /!\ '".PHP_EOL;
    echo "echo 'Error: ".$error."'".PHP_EOL;
    echo "exit 1".PHP_EOL;
    exit(1);
}


// Extract VAR
$DEPLOY_TARGET_HOST = get_env("DEPLOY_TARGET_HOST");
$DEPLOY_USER = get_env("DEPLOY_USER");
$DEPLOY_PRIVATE_KEY = get_env("DEPLOY_PRIVATE_KEY");
$DEPLOY_CHOWN_USER = get_env("DEPLOY_CHOWN_USER");
$DEPLOY_CHOWN_GROUP = get_env("DEPLOY_CHOWN_GROUP");
$DEPLOY_CHMOD_DIR = get_env("DEPLOY_CHMOD_DIR");
$DEPLOY_CHMOD_FILE = get_env("DEPLOY_CHMOD_FILE");
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


// On lance un rsync qui va supprimer les fichiers sur la destination si il ne sont pas présents sur la source
// on exclus donc "storage" pour ne pas supprimer tous les fichiers sur l'env de destination
echo "rsync --recursive --compress --delete --times -e \"ssh\" --exclude 'storage/*' --exclude '.git' ./ {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST}:{$DEPLOY_DIR}\n";

// Synchro de storage sans le flag --delete
echo "rsync --recursive --compress --times -e \"ssh\" ./storage/ {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST}:{$DEPLOY_DIR}storage\n";



if ($DEPLOY_CHOWN_USER) {
    echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"chown -R {$DEPLOY_CHOWN_USER} {$DEPLOY_DIR}\"\n";
}
if ($DEPLOY_CHOWN_GROUP) {
    echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"chown -R :{$DEPLOY_CHOWN_GROUP} {$DEPLOY_DIR}\"\n";
}

if ($DEPLOY_CHMOD_DIR) {
    echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"find {$DEPLOY_DIR} -type d -exec chmod {$DEPLOY_CHMOD_DIR} {} \;\"\n";
}

if ($DEPLOY_CHMOD_FILE) {
    echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"find {$DEPLOY_DIR} -type f -exec chmod {$DEPLOY_CHMOD_FILE} {} \;\"\n";
}

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

$SUPERVISOR_PROGRAM_NAME = get_env("SUPERVISOR_PROGRAM_NAME");
$CI_ENVIRONMENT_NAME = get_env("CI_ENVIRONMENT_NAME");

if ("" != $SUPERVISOR_PROGRAM_NAME) {
    $CONF_FILENAME = $SUPERVISOR_PROGRAM_NAME."_".$CI_ENVIRONMENT_NAME.".conf";
    $PROGRAM_NAME = $SUPERVISOR_PROGRAM_NAME."_".$CI_ENVIRONMENT_NAME;
    $CONF_DIR = get_env("SUPERVISOR_CONFD_DIR", "/etc/supervisor/conf.d");

    $replacements = [
        "CI_PROJECT_TITLE" => get_env("CI_PROJECT_TITLE"),
        "CI_PROJECT_URL" => get_env("CI_PROJECT_URL"),
        "PROGRAM_NAME" => $PROGRAM_NAME,
        "USER" => $DEPLOY_USER,
        "NUM_PROCS" => get_env("SUPERVISOR_NUM_PROCS", 2),
        "LOGFILE" => $DEPLOY_DIR."storage/logs/workers.log",
        "COMMAND" => $DEPLOY_ARTISAN_PATH." queue:work --sleep=3 --tries=3 --max-time=120",
        "PHP_EXECUTABLE_PATH" => $DEPLOY_HOST_PHP_PATH,
    ];

    $keys = array_keys($replacements);
    $keys = array_map("build_key", $keys);

    $conf = str_replace($keys, array_values($replacements), get_supervisor_conf_tpl());

    echo "STATUS=`ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"sudo supervisorctl status ".$PROGRAM_NAME.":*\"`\n";
    echo 'echo -e "'.$conf.'" > '.$CONF_FILENAME.''."\n";
    echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"sudo ln -sf ./".$CONF_FILENAME." ".$CONF_DIR."/".$CONF_FILENAME."\"\n";

    // Si les workers sont déjà gérés par supervisor on update, sinon on start
    echo 'if [[ $STATUS == *"no such group"* ]]; then'."\n";
    echo "\tssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"sudo supervisorctl start ".$PROGRAM_NAME."\"\n";
    echo "else\n";
    echo "\tssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"sudo supervisorctl update ".$PROGRAM_NAME."\"\n";
    echo "fi\n";

    // Attend que les process démarrent avant de voir le status
    echo "sleep 5\n";
    echo "ssh -ttq {$DEPLOY_USER}@{$DEPLOY_TARGET_HOST} \"sudo supervisorctl status ".$PROGRAM_NAME.":*\"\n";
}
