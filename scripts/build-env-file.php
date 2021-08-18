#!/usr/local/bin/php
<?php

foreach ($_ENV as $name => $value) {
    $prefix_position = strpos($name, "DOTENV_");

    if (false === $prefix_position || $prefix_position > 0 ) {
        continue;
    }

    // escape double quote in value
    $value = str_replace('"', '\"', $value);
    $name = str_replace('DOTENV_', '', $name);

    echo "{$name}=\"{$value}\"\n";
}

// Set a placeholder for the app key generated later with artisan
echo "APP_KEY=\n";
