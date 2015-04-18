<?php namespace {namespace}\Providers;

use Laradic\Support\AbstractConsoleProvider;

class ConsoleServiceProvider extends AbstractConsoleProvider
{

    protected $namespace = '{namespace}\Console';

    protected $commands = [
        'Stub' => 'commands.{vendor}.{package}.stub',
    ];
}
