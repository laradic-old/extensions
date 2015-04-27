<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Laradic\Extensions\Console\Traits\ExtensionCommandTrait;
use Laradic\Console\Command;
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
class InstallExtensionsCommand extends Command
{
    use ExtensionCommandTrait;

    protected $name = 'extensions:install';

    protected $description = 'List all extensions.';

    public function fire()
    {

        $extensions = $this->getExtensions();

        if ( ! $slug = $this->argument('slug') )
        {
            foreach($extensions->sortByDependencies()->all() as $extension)
            {
                $slug = $extension->getSlug();
                $answer = config('app.debug') ? true : $this->confirm('Do you want to install ' . $this->colorize(['bold', 'black'], $slug), true);
                if($answer)
                {
                    //$this->install($slug);
                    $this->call('extensions:install', [
                        'slug' => $slug
                    ]);
                }
            }
        }
        else
        {
            $this->install($slug);
        }
    }

    protected function install($slug)
    {
        $extensions = $this->getExtensions();
        if ( ! $extensions->has($slug) )
        {
            return $this->error("Extension [$slug] does not exist");
        }
        $extension = $extensions->get($slug);
        if ( ! $extension->isInstalled() )
        {
            $extension->install();
            $this->info("Extension [$slug] installed");
        }
        else
        {
            $this->comment("Extension [$slug] already installed");
        }
    }

    public function getArguments()
    {
        return [
            [ 'slug', InputArgument::OPTIONAL, 'The extension slug' ]
        ];
    }
}
