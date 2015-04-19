<?php

use Illuminate\Contracts\Foundation\Application;
use Laradic\Extensions\Extension;
use Laradic\Extensions\ExtensionCollection;

return array(
    'name' => '{packageName}',
    'slug' => '{vendor}/{package}',
    'dependencies' => [
    ],
    'migrations' => [],
    'seeds' => [],
    'paths' => [
        // defaults
        'config' => 'resources/config',
        'theme' => 'resources/theme',
        'migrations' => 'resources/migrations',
        'seeds' => 'resources/seeds'
    ],
    'register' => function(Application $app, Extension $extension, ExtensionCollection $extensions)
    {

    },
    'boot' => function(Application $app, Extension $extension, ExtensionCollection $extensions)
    {
        $app->register('{namespace}\{packageName}ServiceProvider');
    },
    'install' => function(Application $app, Extension $extension, ExtensionCollection $extensions)
    {

    },
    'uninstall' => function(Application $app, Extension $extension, ExtensionCollection $extensions)
    {

    }
);
