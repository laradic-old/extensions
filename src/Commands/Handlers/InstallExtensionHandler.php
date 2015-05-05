<?php namespace Laradic\Extensions\Commands\Handlers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Queue\InteractsWithQueue;
use Laradic\Extensions\Commands\InstallExtension;
use Laradic\Extensions\Commands\UninstallExtension;
use Laradic\Extensions\Events\ExtensionInstalled;
use Laradic\Extensions\Traits\ExtensionDbRecordTrait;
use Laradic\Support\Path;
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
        $this->log->info("extensions.install starting [{$extension->getSlug()}] ");
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
        $paths = array_merge($extension->getMigrations(), [ Path::join($extension->getPath(), 'resources/migrations') ]);
        $this->runMigrations($extension, $paths, 'up');
        $this->connection = app('db')->connection();

        $seedPaths = array_merge($extension->getSeeds(), [ Path::join($extension->getPath(), 'resources/seeds') ]);
        $this->runSeeders($extension, $seedPaths);

        // Publish theme
        if(class_exists('Laradic\Themes\ThemeServiceProvider') and $app->isShared('theme'))
        {
            $themes     = $app->make('themes');
            $themePaths = $extension->getThemes();
            if ( $themePaths !== false )
            {
                $publishers = $themes->getPublishers();
                if ( in_array($slug, array_keys($publishers)) )
                {
                    $themes->publish($slug);
                }
            }
        }

        // Writeout
        $this->recordInstall($slug);

        event(new ExtensionInstalled($extension));
        $extension->onInstalled();
        $this->log->info("extensions.install done [{$extension->getSlug()}] ");
    }

}
