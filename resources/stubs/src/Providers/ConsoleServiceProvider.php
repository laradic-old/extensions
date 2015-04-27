<?php namespace {namespace}\Providers;

use Laradic\Console\AggregateConsoleProvider;

class ConsoleServiceProvider extends AggregateConsoleProvider
{

    protected $namespace = '{namespace}\Console';

    protected $commands = [
        'Stub' => 'commands.{vendor}.{package}.stub',
    ];
}
