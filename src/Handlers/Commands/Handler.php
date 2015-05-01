<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions\Handlers\Commands;


use Debugger;
use Illuminate\Contracts\Foundation\Application;
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

    /**
     * Create the command handler.
     *
     * @param \Illuminate\Contracts\Foundation\Application         $app
     * @param \Extensions|\Laradic\Extensions\Contracts\Extensions $extensions
     */
    public function __construct(Application $app, Extensions $extensions)
    {
        /** @var \Illuminate\Foundation\Application $app */
        $this->app        = $app;
        $this->extensions = $extensions;
        $this->connection = $app->make('db')->connection();

        $this->migrator = $migrator = $app->make('migrator');
        $migrator->setConnection($this->connection->getName());
        $this->files = $app->make('files');
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
        $migrator = $this->migrator;
        $files = $this->files;
        if ( ! isset($paths) or ! is_array($paths) )
        {
            return;
        }

        foreach ( $paths as $path )
        {
            $path = $this->resolvePath($extension, $path);
            if( $path === false)
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
                        $migration->up();
                    }
                    elseif ( $way === 'down' )
                    {
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


    protected function runSeeders(Extension $extension, array $paths = [])
    {
        if ( ! isset($paths) or ! is_array($paths) )
        {
            return;
        }

        foreach ($paths as $path)
        {
            $path = $this->resolvePath($extension, $path);
            if( $path === false)
            {
                continue;
            }

            $seederFiles = $this->files->glob(path_join($path, '*Seeder.php'));

            foreach ($seederFiles as $file)
            {
                $this->runSeed($file);
            }
        }

        foreach ($extension->getSeeds() as $seedFilePath => $seedClassName)
        {
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
        if ( $files->isDirectory($path)  )
        {
            return $path;
        }
        elseif(path_is_relative($path))
        {
            if($files->isDirectory(path_join($extension->getPath(), $path)))
            {
                return path_join($extension->getPath(), $path);
            }
            elseif($files->isDirectory(base_path($path)))
            {
                return base_path($path);
            }
        }
        return false;
    }

}
