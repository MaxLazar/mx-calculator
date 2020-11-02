<?php

$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

if (!defined('MX_CALC_NAME')) {
    define('MX_CALC_NAME', $addonJson->name);
    define('MX_CALC_VERSION', $addonJson->version);
    define('MX_CALC_DOCS', '');
    define('MX_CALC_DESCRIPTION', $addonJson->description);
    define('MX_CALC_DEBUG', false);
}

return [
    'name' => $addonJson->name,
    'description' => $addonJson->description,
    'version' => $addonJson->version,
    'namespace' => $addonJson->namespace,
    'author' => 'Max Lazar',
    'author_url' => 'https://eecms.dev',
    'settings_exist' => false
];
