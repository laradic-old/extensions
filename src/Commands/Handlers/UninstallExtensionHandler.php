<?php namespace Laradic\Extensions\Commands\Handlers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Queue\InteractsWithQueue;
use Laradic\Extensions\Commands\UninstallExtension;
use Laradic\Extensions\Events\ExtensionUninstalled;
use Laradic\Extensions\Traits\ExtensionDbRecordTrait;
use Symfony\Component\VarDumper\VarDumper;

/**
 * {@inheritDoc}
 */
class UninstallExtensionHandler extends Handler
{
    use ExtensionDbRecordTrait;


    /**
     * Handle the command.
     *
     * @param  UninstallExtension $command
     * @throws \Exception
     */
    public function handle(UninstallExtension $command)
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app        = $this->app;
        $extensions = $this->extensions;
        $extension  = $command->extension;

        #$extension->register();
        $app->register($extension);
        $extension->onUninstall();

        // Dependency checking
        $sorter     = $extensions->getSorter();
        $sorted     = $sorter->sort();
        $slug       = $command->extension->getSlug();
        $dependents = $sorter->requiredBy($extension->getSlug());
        foreach ( $dependents as $_slug )
        {
            if ( $extensions->get($_slug)->isInstalled() )
            {
                throw new \Exception("Could not uninstall {$extension->getSlug()}, there are other extensions installed depending on it. ");
            }
        }

        // Migrations
        $paths = array_merge($extension->getMigrations(), [ path_join($extension->getPath(), 'resources/migrations') ]);
        $this->runMigrations($extension, $paths, 'down');
        $this->connection = app('db')->connection();

        // Writeout
        $this->recordUninstall($slug);
        event(new ExtensionUninstalled($extension));
        $extension->onInstalled();
    }

}
