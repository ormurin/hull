<?php

spl_autoload_register(function ($class) {

    $namespace_directories = [
        'App\\' => __DIR__ . '/../src',
        'Unit\\' => __DIR__ . '/../units',
        'Ormurin\\Hull\\' => __DIR__ . '/../../src',
        '' => __DIR__ . '/../../lib'
    ];

    foreach ( $namespace_directories as $namespace => $directory ) {
        if ( !str_starts_with($class, $namespace) ) {
            continue;
        }
        $file_path = realpath($directory) . '/' . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
        if ( file_exists($file_path) ) {
            require_once $file_path;
        }
    }

});
