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

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Laradic\Extensions\Contracts\Extensions as ExtensionsContract;
use Laradic\Support\Sorter;
use Laradic\Support\TemplateParser;

/**
 * Class ExtensionCollection
 *
 * @package     Laradic\Extensions
 * @method Extension[] all
 */
class ExtensionCollection extends Collection implements ExtensionsContract
{
    /** @var \Illuminate\Filesystem\Filesystem */
    protected $files;

    /** @var \Laradic\Extensions\ExtensionFileFinder  */
    protected $finder;

    /** @var \Illuminate\Foundation\Application  */
    protected $app;

    /** @var \Illuminate\Database\Connection  */
    protected $connection;

    /**
     * Instanciates the class
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Filesystem\Filesystem            $files
     * @param \Laradic\Extensions\ExtensionFileFinder      $finder
     * @param \Illuminate\Database\Connection              $connection
     */
    public function __construct(Application $app, Filesystem $files, ExtensionFileFinder $finder, Connection $connection)
    {
        $this->connection = $connection;
        $this->app        = $app;
        $this->files      = $files;
        $this->finder     = $finder;
    }

    /**
     * get
     *
     * @param string $slug
     * @return Extension
     */
    public function get($slug)
    {
        return parent::get($slug);
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
        $properties = $this->files->getRequire($extensionFilePath);

        return new Extension($this, dirname($extensionFilePath), $properties);
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
            $this->put($extension->getSlug(), $extension);
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

        #Debugger::log('deps', $this->items, $sorted, array_reverse($sorted));
        return $this;
    }

    /**
     * Get the value of connection
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->connection;
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
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}
