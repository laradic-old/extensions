<?php
 /**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Laradic\Console\Command;
use Laradic\Support\String;
use Packagist\Api\Client;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class ListExtensionsCommand
 *
 * @package     Laradic\Extensions\Console
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class SearchExtensionsCommand extends Command
{
    protected $name = 'extensions:search';
    protected $description = 'List or search packagist.org for extensions';

    public function fire()
    {
        #$this->comment('Checking remote package repositories for available packages');

        $packagist = new Client();
        $extensions = app('extensions');
        $filters = ['type' => \Config::get('laradic/extensions::type')];

        if($query = $this->argument('query'))
        {
            $result = $packagist->search($query, $filters);
        }
        else
        {
            $result = $packagist->search('', $filters);
        }

        if(count($result) === 1)
        {
            $this->dump($packagist->get(head($result)));
        }
        elseif(count($result) > 1)
        {
            $rows = [];
            foreach($result as $package)
            {
                /** @var \Packagist\Api\Result\Result $package */
                #$this->line('- ' . $package->getName());
                $rows[] = [$package->getName(), String::limit($package->getDescription(), 30), $package->getDownloads(), $package->getRepository()];
            }
            $this->table(['Name', 'Description', 'Downloads', 'Repository'], $rows);
        }
        else
        {
            $this->comment('No packages found using your search criteria');
        }

    }

    public function getArguments()
    {
        return [
            [ 'query', InputArgument::OPTIONAL, 'The search query' ]
        ];
    }
}
