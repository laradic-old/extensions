<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions\Commands\Handlers;


use Debugger;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\InteractsWithQueue;
use Laradic\Extensions\Contracts\Extensions;
use Laradic\Extensions\Extension;
use Laradic\Support\Path;
use Symfony\Component\VarDumper\VarDumper;


/**
 * This is the InstallerHandlerTrait.
 *
 * @package        Laradic\Extensions
 * @version        1.0.0
 * @author         Robin Radic
 * @license        MIT License
 * @copyright      2015, Robin Radic
 * @link           https://github.com/robinradic
 */
class Handler
{

    protected $app;

    /**
     * @var \Laradic\Extensions\ExtensionFactory
     */
    protected $extensions;

    protected $migrator;

    protected $connection;

    protected $files;

    protected $db;

    /**
     * Create the command handler.
     *
     * @param \Illuminate\Contracts\Foundation\Application         $app
     * @param \Extensions|\Laradic\Extensions\Contracts\Extensions $extensions
     */
    public function __construct(Application $app, Extensions $extensions, Filesystem $files, DatabaseManager $db, Log $log)
    {
        /** @var \Illuminate\Foundation\Application $app */
        $this->app        = $app;
        $this->extensions = $extensions;
        $this->files      = $files;
        $this->db         = $db;
        $this->connection = $db->connection();
        $this->files      = $files;
        $this->migrator   = $migrator = $app->make('migrator');
        $this->log        = $log;
        $migrator->setConnection($this->connection->getName());
    }


    /**
     * Run Migrations
     *
     * @param \Laradic\Extensions\Extension $extension
     * @param                               $paths
     * @param string                        $way
     */
    protected function runMigrations(Extension $extension, $paths, $way = 'up')
    {
        $this->log->info("extensions.migration [$way] starting [{$extension->getSlug()}] ");
        if ( $extension->getMigrations() === false )
        {
            // The extension doesnt want me to handle migrations, skip it
            $this->log->info("extensions.migration [$way] SKIPPING [{$extension->getSlug()}] ");
            return;
        }

        $migrator = $this->migrator;
        $files    = $this->files;
        if ( ! isset($paths) or ! is_array($paths) )
        {
            return;
        }

        foreach ( $paths as $path )
        {
            $path = $this->resolvePath($extension, $path);
            if ( $path === false )
            {
                continue;
            }

            $migrationFiles = $migrator->getMigrationFiles($path);
            if ( $way === 'down' )
            {
                $migrationFiles = array_reverse($migrationFiles);
            }

            $migrator->requireFiles($path, $migrationFiles);

            foreach ( $migrationFiles as $migrationFile )
            {

                $migration = $migrator->resolve($migrationFile);

                #Debugger::dump(compact('path', 'migrationFile', 'way', 'migration'));
                try
                {
                    if ( $way === 'up' )
                    {
                        $this->log->info("extensions.migration $way -> [{$extension->getSlug()}] migrating [$migrationFile]");
                        $migration->up();
                    }
                    elseif ( $way === 'down' )
                    {
                        $this->log->info("extensions.migration $way -> [{$extension->getSlug()}] migrating [$migrationFile]");
                        $migration->down();
                    }
                }
                catch (QueryException $qe)
                {
                    Debugger::dump('Error migrating: ' . $qe->getMessage());
                }
                catch (\PDOException $pe)
                {
                    Debugger::dump('Error migrating: ' . $pe->getMessage());
                }
            }
        }
    }


    protected function runSeeders(Extension $extension, array $paths = [ ])
    {
        $this->log->info("extensions.seeding starting [{$extension->getSlug()}]");
        if ( $extension->getSeeds() === false )
        {
            // The extension doesnt want me to handle seeds, skip it
            $this->log->info("extensions.seeding skipping [{$extension->getSlug()}]");
            return;
        }

        if ( ! isset($paths) or ! is_array($paths) )
        {
            return;
        }

        foreach ( $paths as $path )
        {
            $path = $this->resolvePath($extension, $path);
            if ( $path === false )
            {
                continue;
            }

            $seederFiles = $this->files->glob(Path::join($path, '*Seeder.php'));

            foreach ( $seederFiles as $file )
            {
                $this->log->info("extensions.seeding [{$extension->getSlug()}] [$file]");
                $this->runSeed($file);
            }
        }

        foreach ( $extension->getSeeds() as $seedFilePath => $seedClassName )
        {
            $this->log->info("extensions.seeding [{$extension->getSlug()}] [$seedFilePath]");
            $this->runSeed(
                is_int($seedFilePath) ? $seedClassName : $seedFilePath,
                is_int($seedFilePath) ? null : $seedClassName
            );
        }
    }


    protected function runSeed($filePath, $className = null)
    {

        $seeder = $this->app->make('seeder');
        $this->files->requireOnce($filePath);
        $className = isset($className) ? $className : Path::getFilenameWithoutExtension($filePath);
        $seeder->call($className);
    }


    protected function resolvePath(Extension $extension, $path)
    {
        $files = $this->files;
        if ( $files->isDirectory($path) )
        {
            return $path;
        }
        elseif ( Path::isRelative($path) )
        {
            if ( $files->isDirectory(Path::join($extension->getPath(), $path)) )
            {
                return Path::join($extension->getPath(), $path);
            }
            elseif ( $files->isDirectory(base_path($path)) )
            {
                return base_path($path);
            }
        }

        return false;
    }

}
