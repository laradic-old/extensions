<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Laradic\Extensions\Commands\InstallExtension;
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
    use ExtensionCommandTrait, DispatchesCommands;

    protected $name = 'extensions:install';

    protected $description = 'Install one or several extensions';

    public function fire()
    {

        $extensions = $this->getExtensions();

        if ( ! $slug = $this->argument('slug') )
        {
            foreach($extensions->getSortedByDependency()->all() as $extension)
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
            $extension = $this->getExtensions()->get($slug);
            if(!$extension->isInstalled())
            {
                $c = new InstallExtension($this->getExtensions()->get($slug));
                $this->dispatch($c);
            }
            else
            {
                $this->error('Extension could not be installed, the extension was alreayd installed');
            }
        }
    }


    public function getArguments()
    {
        return [
            [ 'slug', InputArgument::OPTIONAL, 'The extension slug' ]
        ];
    }
}
