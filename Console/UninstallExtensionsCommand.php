<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Laradic\Extensions\Console\Traits\ExtensionCommandTrait;
use Laradic\Support\AbstractConsoleCommand;
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
class UninstallExtensionsCommand extends AbstractConsoleCommand
{
    use ExtensionCommandTrait;

    protected $name = 'extensions:uninstall';

    protected $description = 'List all extensions.';

    public function fire()
    {
        if(!$slug       = $this->argument('slug'))
        {
            foreach($this->getExtensions()->getSortedByDependency()->reverse()->all() as $extension)
            {
                //$extension->uninstall();
                $this->call('extensions:uninstall', [
                    'slug' => $extension->getSlug()
                ]);
            }
        }
        else
        {
            $this->uninstall($slug);
        }
    }

    protected function uninstall($slug)
    {
        $extensions = $this->getExtensions();
        $slug       = $this->argument('slug');
        if ( ! $extensions->has($slug) )
        {
            return $this->error("Extension [$slug] does not exist");
        }
        $extension = $extensions->get($slug);
        if ( $extension->isInstalled() )
        {
            $extension->uninstall();
            $this->info("Extension [$slug] uninstalled");

        }
        else
        {
            $this->comment("Extension [$slug] not installed");
        }
    }

    public function getArguments()
    {
        return [
            ['slug', InputArgument::OPTIONAL, 'The extension slug']
        ];
    }
}
