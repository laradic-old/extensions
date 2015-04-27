<?php namespace {namespace}\Console;

use Laradic\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class StubCommand extends Command
{

    protected $name = '{package}:stub';

    protected $description = 'Command description.';

    public function fire()
    {
        $this->info('Replace this stub command with an actual command');
    }

    public function getArguments()
    {
        return [
            ['arg', InputArgument::OPTIONAL, 'Description']
        ];
    }

    public function getOptions()
    {
        return [
            ['opt', 'o', InputOption::VALUE_OPTIONAL, 'Description']
        ];
    }
}
