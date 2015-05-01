<?php

return array(
    'paths'                      => array(
        base_path('workbench')
    ),
    'table' => 'extensions',
    'defaultExtensionAttributes' => array(
        'paths'        => array(
            'config'     => 'resources/config',
            'theme'      => 'resources/theme',
            'migrations' => 'resources/migrations',
            'seeds'      => 'resources/seeds',
        ),
        'handles' => array(
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
