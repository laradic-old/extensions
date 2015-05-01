<?php namespace Laradic\Extensions\Handlers\Commands;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Queue\InteractsWithQueue;
use Laradic\Extensions\Commands\InstallExtension;
use Laradic\Extensions\Commands\UninstallExtension;
use Laradic\Extensions\Events\ExtensionUninstalled;
use Laradic\Extensions\Traits\ExtensionDbRecordTrait;
use Symfony\Component\VarDumper\VarDumper;

/**
 * {@inheritDoc}
 */
class InstallExtensionHandler extends Handler
{
    use ExtensionDbRecordTrait;


    /**
     * Handle the command.
     *
     * @param  UninstallExtension $command
     * @throws \Exception
     */
    public function handle(InstallExtension $command)
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app        = $this->app;
        $extensions = $this->extensions;
        $extension  = $command->extension;

        #$extension->register();
        $app->register($extension);
        $extension->onInstall();

        // Dependency checking
        $sorter     = $extensions->getSorter();
        $sorted     = $sorter->sort();
        $slug       = $command->extension->getSlug();
        $dependents = $sorter->requiredBy($extension->getSlug());
        foreach ( $dependents as $_slug )
        {
            if ( $extensions->get($_slug)->isInstalled() )
            {
                throw new \Exception("Could not install {$extension->getSlug()}, there are other extensions installed depending on it. ");
            }
        }

        // Migrations
        $paths = array_merge($extension->getMigrations(), [ path_join($extension->getPath(), 'resources/migrations') ]);
        $this->runMigrations($extension, $paths, 'up');
        $this->connection = app('db')->connection();

        $seedPaths = array_merge($extension->getSeeds(), [ path_join($extension->getPath(), 'resources/seeds') ]);
        $this->runSeeders($extension, $seedPaths);


        // Writeout
        $this->recordInstall($slug);
        event(new ExtensionInstalled($extension));
        $extension->onInstalled();
    }

}
