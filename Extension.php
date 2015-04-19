<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use ArrayAccess;
use Config;
use Laradic\Extensions\Contracts\Extension as ExtensionContract;
use Laradic\Extensions\Contracts\ExtensionRepository;
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

    /** @var \Laradic\Extensions\ExtensionCollection */
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

    /** @var array */
    protected $record;

    /**
     * @var \Laradic\Extensions\Repositories\EloquentExtensionRepository
     */
    protected $repository;

    /**
     * Instanciates the class
     *
     * @param \Laradic\Extensions\ExtensionCollection $extensions
     * @param                                         $path
     * @param array                                   $attributes
     */
    public function __construct(ExtensionCollection $extensions, ExtensionRepository $repository)
    {
        $this->extensions = $extensions;
        $this->repository = $repository;
    }


    public function install()
    {
        $this->fireEvent('extension.installing', [$this]);
        if(!$this->canInstall())
        {
            return false;
        }
        if($this->handles('migrations'))
        {
            $this->runMigrations('up');
        }
        $this->callPropertiesClosure('install');
        $this->repository->install($this->slug);
        $this->fireEvent('extension.installed', [$this]);
    }

    public function uninstall()
    {
        $this->fireEvent('extension.uninstalling', [$this]);
        // Check if there are extensions installed that rely on this one
        if ( ! $this->canUninstall() )
        {
            // Cancel the uninstallation
            return false;
        }

        $this->runMigrations('down');
        $this->callPropertiesClosure('uninstall');
        $this->repository->uninstall($this->slug);
        $this->fireEvent('extension.uninstalled', [$this]);
    }

    public function register()
    {
        if ( ! $this->isInstalled() )
        {
            return;
        }
        $this->fireEvent('extension.register', [$this]);
        if($this->handles('config'))
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
        if($this->handles('themes'))
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
        return (bool)$this->record['installed'];
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
        if(!in_array($handle, $this['handles']) or $this["handles.${handle}"] !== true)
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
        $path = $this->getPath('migrations');
        if ( ! $this->extensions->getFiles()->isDirectory($path) )
        {
            return;
        }

        /** @var \Illuminate\Foundation\Application $app */
        $app            = $this->getExtensions()->getApplication();
        $migrator       = $app->make('migrator');
        $migrationFiles = $migrator->getMigrationFiles($path);
        $migrator->requireFiles($path, $migrationFiles);

        foreach ($migrationFiles as $migrationFile)
        {
            $migration = $migrator->resolve($migrationFile);

            if ( $way === 'up' )
            {
                $migration->up($path);
            }
            elseif ( $way === 'down' )
            {
                $migration->down();
            }
        }
    }

    /**
     * Get the value of path
     *
     * @return mixed
     */
    public function getPath($path = null)
    {
        $path = is_null($path) ? $this->path : Path::join($this->path, $this["paths.$path"]);

        return realpath($path);
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


    public function getDefaultAttributes()
    {
        return Config::get('laradic/extensions::defaultExtensionAttributes');
    }

    /**
     * Get the value of extensions
     *
     * @return \Laradic\Extensions\ExtensionCollection
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
        $this->attributes   = array_merge($this->getDefaultAttributes(), $attributes);
        $this->slug         = $attributes['slug'];
        $this->dependencies = $attributes['dependencies'];
        $this->name         = $attributes['name'];

        if ( ! $this->record = $this->repository->getBySlug($this->slug) )
        {
            $this->repository->create($this->slug);
        }

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
