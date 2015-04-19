<?php
/**
 * Part of the Laradic packages.
 * MIT License and copyright information bundled with this package in the LICENSE file.
 *
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
namespace Laradic\Extensions;

use ArrayAccess;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
#use Laradic\Extensions\Contracts\ExtensionRepository;
use Laradic\Extensions\Contracts\ExtensionRepository;
use Laradic\Extensions\Contracts\Extensions as ExtensionsContract;
use Laradic\Extensions\Extension;
use Laradic\Extensions\Repositories\EloquentExtensionRepository;
use Laradic\Support\Sorter;
use Laradic\Support\TemplateParser;

/**
 * Class ExtensionCollection
 *
 * @package     Laradic\Extensions
 */
class ExtensionCollection implements ArrayAccess, ExtensionsContract
{

    /** @var \Illuminate\Filesystem\Filesystem */
    protected $files;

    /** @var \Laradic\Extensions\ExtensionFileFinder */
    protected $finder;

    /** @var \Illuminate\Foundation\Application */
    protected $app;

    /** @var \Illuminate\Support\Collection */
    protected $extensions;

    /** @var \Laradic\Extensions\Contracts\ExtensionRepository */
    protected $repository;

    /**
     * Instanciates the class
     *
     * @param \Illuminate\Contracts\Foundation\Application      $app
     * @param \Illuminate\Filesystem\Filesystem                 $files
     * @param \Laradic\Extensions\ExtensionFileFinder           $finder
     * @param \Laradic\Extensions\Contracts\ExtensionRepository $repository
     */
    public function __construct(Application $app, Filesystem $files, ExtensionFileFinder $finder, ExtensionRepository $repository)
    {
        $this->repository = $repository;
        $this->app        = $app;
        $this->files      = $files;
        $this->finder     = $finder;

        $this->extensions = new Collection();
    }

    /**
     * get
     *
     * @param string $slug
     * @return Extension
     */
    public function get($slug)
    {
        return $this->extensions->get($slug);
    }

    /**
     * has
     *
     * @param mixed $slug
     * @return bool
     */
    public function has($slug)
    {
        return $this->extensions->has($slug);
    }

    /**
     * all
     *
     * @return Extension[]
     */
    public function all()
    {
        return $this->extensions->all();
    }

    /**
     * getTemplateParser
     *
     * @param null $sourcePath
     * @return \Laradic\Support\TemplateParser
     */
    public function getTemplateParser($sourcePath = null)
    {
        $sourcePath = is_null($sourcePath) ? realpath(__DIR__ . '/resources/stubs') : $sourcePath;

        return new TemplateParser($this->app->make('files'), $sourcePath);
    }

    /**
     * Checks if an extension is installed
     *
     * @param $slug
     * @return bool
     */
    public function isInstalled($slug)
    {
        if ( ! $this->has($slug) )
        {
            return false;
        }

        return $this->get($slug)->isInstalled();
    }

    /**
     * Adds a path to include while searching for extensions
     *
     * @param string $path The absolute path to the directory
     */
    public function addPath($path)
    {
        $this->finder->addPath($path);
    }

    /**
     * Creates an extension instance using the extension.php file
     *
     * @param string $extensionFilePath Path to the extension.php file
     * @return \Laradic\Extensions\Extension
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function createFromFile($extensionFilePath)
    {
        $attributes = $this->files->getRequire($extensionFilePath);

        return $this->make()
            ->setPath(dirname($extensionFilePath))
            ->setAttributes($attributes);
    }

    /**
     * make
     *
     * @return Extension
     */
    public function make()
    {
        return new Extension($this, $this->repository); #$this->app->make('Laradic\Extensions\Extension');
    }

    /**
     * Finds all extensions and registers them
     *
     * @return $this
     */
    public function locateAndRegisterAll()
    {
        foreach ($this->finder->findAll() as $extensionFilePath)
        {
            $extension = $this->createFromFile($extensionFilePath);
            $this->extensions->put($extension->getSlug(), $extension);
        }

        foreach ($this->sortByDependencies()->all() as $extension)
        {
            $this->register($extension);
        }

        return $this;
    }

    /**
     * register
     *
     * @param Extension|string $extension An Extension instance or extension slug
     * @return $this
     */
    public function register($extension)
    {

        if ( ! $this->has($extension) and ! $extension instanceof Extension )
        {
            $extension = $this->createFromFile($extension);
        }
        elseif ( ! $extension instanceof Extension and $this->has($extension) )
        {
            $extension = $this->get($extension);
        }
        $extension->register();

        return $this;
    }

    /**
     * Sorts all registered extensions by dependency
     *
     * @return $this
     */
    public function sortByDependencies()
    {
        $sorter = new Sorter();
        foreach ($this->all() as $extension)
        {
            $sorter->addItem($extension->getSlug(), $extension->getDependencies());
        }
        $extensions = [];
        $sorted     = $sorter->sort();
        foreach ($sorted as $slug)
        {
            $extensions[$slug] = $this->get($slug);
        }
        $this->items = $extensions;

        return $this;
    }

    /**
     * Get the value of finder
     *
     * @return \Laradic\Extensions\ExtensionFileFinder
     */
    public function getFinder()
    {
        return $this->finder;
    }

    /**
     * Get the value of files
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get the value of app
     *
     * @return \Illuminate\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * offsetGet
     *
     * @param string $slug
     * @return \Laradic\Extensions\Extension
     */
    public function offsetGet($slug)
    {
        return $this->get($slug);
    }

    /**
     * Set the item at a given offset.
     *
     * @param  string $slug
     * @param  \Laradic\Extensions\Extension $extension
     * @return void
     */
    public function offsetSet($slug, $extension)
    {
        if ( is_array($slug) )
        {
            foreach ($slug as $innerKey => $innerValue)
            {
                $this->extensions->put($innerKey, $innerValue);
            }
        }
        else
        {
            $this->extensions->put($slug, $extension);
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string $slug
     * @return void
     */
    public function offsetUnset($slug)
    {
        $this->extensions->put($slug, null);
    }
}
