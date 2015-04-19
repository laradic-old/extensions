<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use ArrayAccess;
use Config;
use Debugger;
use Illuminate\Database\QueryException;
use Laradic\Extensions\Contracts\Extension as ExtensionContract;
use Laradic\Support\Filesystem;
use Laradic\Support\Path;
use Laradic\Support\Traits\EventDispatcherTrait;
use Symfony\Component\VarDumper\VarDumper;
use Themes;

/**
 * Class Extension
 *
 * @package     Laradic\Extensions
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class Extension implements ExtensionContract, ArrayAccess
{
    use EventDispatcherTrait;

    /** @var \Laradic\Extensions\ExtensionFactory */
    protected $extensions;

    /** @var string */
    protected $slug;

    /** @var string[] */
    protected $dependencies;

    /** @var string */
    protected $name;

    /** @var array */
    protected $attributes;

    /** @var string */
    protected $path;

    /** @var \StdClass */
    protected $record;

    protected $files;

    /**
     * Instanciates the class
     *
     * @param \Laradic\Extensions\ExtensionFactory $extensions
     * @internal param \Illuminate\Database\ConnectionInterface $connection
     * @internal param \Laradic\Extensions\Contracts\ExtensionRepository $repository
     * @internal param $path
     * @internal param array $attributes
     */
    public function __construct(ExtensionFactory $extensions, Filesystem $files)
    {
        $this->extensions = $extensions;
        $this->files      = $files;
    }


    public function install()
    {
        $this->fireEvent('extension.installing', [$this]);
        $this->callPropertiesClosure('pre_install');
        if ( ! $this->canInstall() )
        {
            return false;
        }

        if ( $this->handles('migrations') )
        {
            $this->runMigrations('up');
        }

        if ( $this->handles('seeds') )
        {
            $this->runSeeders();
        }

        $this->callPropertiesClosure('install');
        $this->extensions->dbInstall($this->slug);
        $this->callPropertiesClosure('installed');
        $this->fireEvent('extension.installed', [$this]);
    }

    public function uninstall()
    {
        $this->fireEvent('extension.uninstalling', [$this]);
        $this->callPropertiesClosure('pre_uninstall');
        if ( ! $this->canUninstall() )
        {
            // Cancel the uninstallation
            return false;
        }

        if ( $this->handles('migrations') )
        {
            $this->runMigrations('down');
        }

        $this->callPropertiesClosure('uninstall');
        $this->extensions->dbUninstall($this->slug);
        $this->callPropertiesClosure('uninstalled');
        $this->fireEvent('extension.uninstalled', [$this]);
    }

    public function register()
    {
        if ( ! $this->isInstalled() )
        {
            return;
        }
        $this->fireEvent('extension.register', [$this]);
        if ( $this->handles('config') )
        {
            Config::addPublisher($this->getSlug(), $this->getPath('config'));
        }
        $this->callPropertiesClosure('register');
        $this->fireEvent('extension.registered', [$this]);
    }

    public function boot()
    {
        if ( ! $this->isInstalled() )
        {
            return;
        }
        $this->fireEvent('extension.booting', [$this]);
        if ( $this->handles('themes') )
        {
            Themes::addPackagePublisher($this->getSlug(), $this->getPath('themes'));
        }
        $this->callPropertiesClosure('boot');
        $this->fireEvent('extension.booted', [$this]);
    }

    public function canInstall()
    {
        foreach ($this->getDependencies() as $dep)
        {
            if ( ! $this->extensions->isInstalled($dep) )
            {
                return false;
            }
        }

        return true;
    }

    public function canUninstall()
    {
        return empty($this->getInstalledDependants());
    }

    public function isInstalled()
    {
        return (bool)$this->record->installed;
    }

    protected function callPropertiesClosure($name)
    {
        if ( $this->attributes[$name] instanceof \Closure )
        {
            $this->attributes[$name]($this->extensions->getApplication(), $this, $this->extensions);
        }
    }

    public function getDependants()
    {
        $deps = [];
        foreach ($this->extensions->all() as $e)
        {
            if ( in_array($this->slug, $e->getDependencies()) )
            {
                $deps[] = $e->getSlug();
            }
        }

        return $deps;
    }

    public function getInstalledDependants()
    {
        $installedDeps = [];
        foreach ($this->getDependants() as $dep)
        {
            $ex = $this->extensions->get($dep);
            if ( $ex->isInstalled() )
            {
                $installedDeps[] = $ex->getSlug();
            }
        }

        return $installedDeps;
    }

    public function handles($handle)
    {
        if ( ! in_array($handle, $this['handles']) or $this["handles.${handle}"] !== true )
        {
            return false;
        }

        return true;
    }

    /**
     * Run Migrations
     *
     * @param string $way
     */
    protected function runMigrations($way = 'up')
    {
        $paths = $this->getPath('migrations');
        if ( ! isset($paths) or ! is_array($paths) )
        {
            return;
        }

        /** @var \Illuminate\Foundation\Application $app */
        $app            = $this->getExtensions()->getApplication();
        $migrator       = $app->make('migrator');
        $migrator->setConnection($this->extensions->getResolver()->getDefaultConnection());

        foreach ($paths as $path)
        {
            if ( ! $this->extensions->getFiles()->isDirectory($path) )
            {
                return;
            }

            $migrationFiles = $migrator->getMigrationFiles($path);
            $migrator->requireFiles($path, $migrationFiles);
            Debugger::dump(compact('path', 'migrationFiles', 'way'));

            foreach ($migrationFiles as $migrationFile)
            {

                $migration = $migrator->resolve($migrationFile);

                #VarDumper::dump(compact('path', 'migrationFile', 'way', 'migration'));
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
                catch(QueryException $qe)
                {
                    Debugger::dump('Error migrating: ' . $qe->getMessage());
                }
                catch(\PDOException $pe)
                {
                    Debugger::dump('Error migrating: ' . $pe->getMessage());
                }
            }
        }
    }

    protected function runSeeders()
    {
        $paths = $this->getPath('seeds');
        if ( ! isset($paths) or ! is_array($paths) )
        {
            return;
        }

        foreach ($paths as $path)
        {
            if ( ! $this->extensions->getFiles()->isDirectory($path) )
            {
                return;
            }

            $seederFiles = $this->files->glob(Path::join($path, '*Seeder.php'));

            foreach ($seederFiles as $file)
            {
                $this->extensions->runSeed($file);
            }
        }

        foreach($this['seeds'] as $seedFilePath => $seedClassName)
        {
            $this->extensions->runSeed(
                is_int($seedFilePath) ? $seedClassName : $seedFilePath,
                is_int($seedFilePath) ? null : $seedClassName
            );
        }
    }


    /**
     * Get the value of path
     *
     * @return mixed
     */
    public function getPath($path = null)
    {
        if ( is_null($path) )
        {
            return realpath($this->path);
        }
        else
        {
            $paths = $this["paths.$path"];
            if ( ! is_array($paths) )
            {
                $paths = [$paths];
            }

            $basePath = $this->path;
            foreach ($paths as $i => $p)
            {
                #VarDumper::dump(compact('i', 'p'));
                if ( Path::isRelative($p) )
                {
                    #VarDumper::dump('is relative : ' . realpath(Path::join($this->path, $p)));
                    $paths[$i] = realpath(Path::join($this->path, $p));
                }
                else
                {
                    #VarDumper::dump('is NOT relative');
                    $paths[$i] = realpath($p);
                }
            }

            #VarDumper::dump(compact('basePath', 'paths', 'path'));
           # VarDumper::dump($this["paths.$path"]);

            return $paths;
        }
    }

    /**
     * Sets the value of path
     *
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * getDefaultAttributes
     *
     * @return mixed
     */
    public function getDefaultAttributes()
    {
        return Config::get('laradic/extensions::defaultExtensionAttributes');
    }

    /**
     * ensureRecord
     *
     * @throws \ErrorException
     */
    public function ensureRecord()
    {
        if ( isset($this->record) )
        {
            return;
        }

        if ( ! $this->record = $this->extensions->dbGetBySlug($this->slug) )
        {
            if ( ! $this->extensions->dbCreate($this->slug) )
            {
                throw new \ErrorException("Could not ensure record for [{$this->getSlug()}]");
            }
            #$this->record = $this->extensions->dbGetBySlug($this->slug);
            $this->ensureRecord();
        }
    }

    /**
     * Get the value of extensions
     *
     * @return \Laradic\Extensions\ExtensionFactory
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Get the value of slug
     *
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Get the value of dependencies
     *
     * @return mixed
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Get the value of name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the value of properties
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * setAttributes
     *
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes   = array_merge_recursive($this->getDefaultAttributes(), $attributes);
        $this->slug         = $attributes['slug'];
        $this->dependencies = $attributes['dependencies'];
        $this->name         = $attributes['name'];

        $this->ensureRecord();

        return $this;
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_has($this->attributes, $key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return array_get($this->attributes, $key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if ( is_array($key) )
        {
            foreach ($key as $innerKey => $innerValue)
            {
                array_set($this->attributes, $innerKey, $innerValue);
            }
        }
        else
        {
            array_set($this->attributes, $key, $value);
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        array_set($this->attributes, $key, null);
    }

}
