<?php

use Classes\Logger;
use Classes\ThemeLoader;

require_once __DIR__ . '/autoloader.php';

function dd($var)
{
    var_dump($var);
    die();
}

$logger = new Logger(__DIR__ . '/logs/log.txt', true);
$logger->setPrefix('[run.php]');

$logger->info('Starting the importation of themes...');

$themes_directory = __DIR__ . '/themes';
$out_directory = __DIR__ . '/out';

$themes = array_filter(scandir($themes_directory), function ($file) use ($themes_directory) {
    return is_dir("{$themes_directory}/{$file}") && !in_array($file, ['.', '..']);
});

foreach ($themes as $theme) {
    $logger->info("Importing theme {$theme}...");

    $theme_directory = "{$themes_directory}/{$theme}";
    $theme_out_directory = "{$out_directory}/{$theme}";

    if (!file_exists($theme_out_directory)) {
        mkdir($theme_out_directory, 0777, true);
    }

    $theme_loader = new ThemeLoader($theme_directory);
    $theme_loader->extractData();
    $theme_loader->exportData($theme_out_directory);
}