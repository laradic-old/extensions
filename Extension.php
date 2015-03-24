<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use Laradic\Extensions\Contracts\Extension as ExtensionContract;

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

    protected $extensions;

    protected $slug;

    protected $dependencies;

    protected $name;

    protected $properties;

    protected $path;

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
    }

    public function register()
    {
        if ( $this->properties['register'] instanceof \Closure )
        {
            $this->properties['register']($this->extensions->getApplication(), $this, $this->extensions);
        }
    }

    public function boot()
    {
        if ( $this->properties['boot'] instanceof \Closure )
        {
            $this->properties['boot']($this->extensions->getApplication(), $this, $this->extensions);
        }
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
