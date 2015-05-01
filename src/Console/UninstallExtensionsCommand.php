<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Laradic\Console\Command;
use Laradic\Extensions\Commands\UninstallExtension;
use Laradic\Extensions\Console\Traits\ExtensionCommandTrait;
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
class UninstallExtensionsCommand extends Command
{
    use ExtensionCommandTrait, DispatchesCommands;

    protected $name = 'extensions:uninstall';

    protected $description = 'Uninstall one or multiple extensions';

    protected $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        parent::__construct();
        $this->dispatcher = $dispatcher;
        # $this->dump($dispatcher);
    }


    public function fire()
    {
        if ( ! $slug = $this->argument('slug') )
        {
            foreach ( $this->getExtensions()->getSortedByDependency()->reverse()->all() as $extension )
            {
                //$extension->uninstall();
                $this->call('extensions:uninstall', [
                    'slug' => $extension->getSlug()
                ]);
            }
        }
        else
        {
            $extension = $this->getExtensions()->get($slug);
            if($extension->isInstalled())
            {
                $c = new UninstallExtension($extension);
                $this->dispatch($c);
                $this->info('Extension [' . $slug . '] uninstalled');
                #$this->uninstall($slug);
            }
            else
            {
                $this->error('Could not uninstall extension. The extension is not installed.');
            }
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
            [ 'slug', InputArgument::OPTIONAL, 'The extension slug' ]
        ];
    }
}
