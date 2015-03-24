<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use Laradic\Extensions\Contracts\Extension as ExtensionContract;
use Laradic\Support\Traits\EventDispatcherTrait;

/**
 * Class Extension
 *
 * @package     Laradic\Extensions
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class Extension implements ExtensionContract
{
    use EventDispatcherTrait;

    protected $extensions;

    protected $slug;

    protected $dependencies;

    protected $name;

    protected $properties;

    protected $path;

    protected $record;

    /**
     * Instanciates the class
     */
    public function __construct(ExtensionCollection $extensions, $path, array $properties)
    {
        $this->extensions   = $extensions;
        $this->path         = $path;
        $this->properties   = $properties;
        $this->slug         = $properties['slug'];
        $this->dependencies = $properties['dependencies'];
        $this->name         = $properties['name'];

        if ( ! $this->record = $this->getDatabaseRecord() )
        {
            $this->insertDatabaseRecord();
        }
    }

    public function queryDatabase()
    {
        return $this->extensions->getConnection()->table('extensions');
    }

    public function getDatabaseRecord()
    {
        return (array) $this->queryDatabase()->where('slug', $this->slug)->first();
    }

    public function insertDatabaseRecord()
    {
        $this->queryDatabase()->insert([
            'slug'      => $this->slug,
            'installed' => false
        ]);
    }

    public function install()
    {
        $this->fireEvent('extension.installing', [$this]);
        $this->callPropertiesClosure('install');
        $this->queryDatabase()->where('slug', $this->slug)->update(['installed' => true]);
        $this->fireEvent('extension.installed', [$this]);
    }

    public function uninstall()
    {
        $this->fireEvent('extension.uninstalling', [$this]);
        $this->callPropertiesClosure('uninstall');
        $this->queryDatabase()->where('slug', $this->slug)->update(['installed' => false]);
        $this->fireEvent('extension.uninstalled', [$this]);
    }

    public function isInstalled()
    {
        return (bool)$this->record['installed'];
    }

    protected function callPropertiesClosure($name)
    {
        if ( $this->properties[$name] instanceof \Closure )
        {
            $this->properties[$name]($this->extensions->getApplication(), $this, $this->extensions);
        }
    }

    public function register()
    {
        $this->fireEvent('extension.register', [$this]);
        $this->callPropertiesClosure('register');
        $this->fireEvent('extension.registered', [$this]);
    }

    public function boot()
    {
        if(!$this->isInstalled())
        {
            return;
        }
        $this->fireEvent('extension.booting', [$this]);
        $this->callPropertiesClosure('boot');
        $this->fireEvent('extension.booted', [$this]);
    }


    /**
     * Get the value of path
     *
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
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
    public function getProperties()
    {
        return $this->properties;
    }
}
