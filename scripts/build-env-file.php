<?php

foreach ($_ENV as $name => $value) {
    $prefix_position = strpos($name, "DOTENV_");

    if (false === $prefix_position || $prefix_position > 0 ) {
        continue;
    }

    // escape double quote in value
    $value = str_replace('"', '\"', $value);

    echo "{$name}=\"{$value}\"\n";
}