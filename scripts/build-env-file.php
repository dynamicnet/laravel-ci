#!/usr/local/bin/php
<?php

$extracted_env = [
    "APP_KEY" => "" // Set a placeholder for the app key generated later by Laravel's artisan command
];

// Extrait les variables d'environnement qui doivent être inscrites dans le .env
// Elles sont préfixées par DOTENV_
foreach ($_ENV as $name => $value) {
    $prefix_position = strpos($name, "DOTENV_");

    if (false === $prefix_position || $prefix_position > 0) {
        continue;
    }

    // Strip prefix
    $name = str_replace('DOTENV_', '', $name);

    $extracted_env[$name] = $value;
}

// Sort by keys for a better readability of the resulting .env file
ksort($extracted_env);

foreach ($extracted_env as $name => $value) {
    // escape double quote in value
    $value = str_replace('"', '\"', $value);

    echo "{$name}=\"{$value}\"\n";
}
