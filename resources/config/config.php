<?php

return array(
    'paths'                      => array(
        base_path('extensions')
    ),
    'defaultExtensionAttributes' => array(
        'paths'        => array(
            'config'     => 'resources/config',
            'theme'      => 'resources/theme',
            'migrations' => 'resources/migrations',
            'seeds'      => 'resources/seeds',
        ),
        'handle' => array(
            'config'     => true,
            'theme'      => true,
            'migrations' => true,
            'seeds'      => true,
        ),
        'dependencies' => array(),
        'seeds'        => array(),
        'migrations'   => array(),
        'register'     => null,
        'boot'         => null,
        'install'      => null,
        'uninstall'    => null
    )
);
