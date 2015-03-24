<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

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
class InstallExtensionsCommand extends AbstractConsoleCommand
{

    protected $name = 'extensions:install';

    protected $description = 'List all extensions.';

    public function fire()
    {
        $extensions = app('extensions');
        $slug       = $this->argument('slug');
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
            ['slug', InputArgument::REQUIRED, 'The extension slug']
        ];
    }
}
