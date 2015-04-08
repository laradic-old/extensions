<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Laradic\Extensions\Contracts\Extensions as ExtensionsContract;
use Laradic\Support\Sorter;

/**
 * Class Repository
 *
 * @package     Laradic\Extensions
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 * @method Extension[] all
 * @method Extension get(string $slug)
 */
class ExtensionCollection extends Collection implements ExtensionsContract
{

    protected $files;

    protected $finder;

    protected $app;

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
    public function addPath($path)
    {
        $this->finder->addPath($path);
    }

    public function createFromFile($extensionFilePath)
    {
        $properties = $this->files->getRequire($extensionFilePath);

        return new Extension($this, dirname($extensionFilePath), $properties);
    }

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

    public function register($extension)
    {

        if ( ! $this->has($extension) and ! $extension instanceof Extension )
        {
            $extension = $this->createFromFile($extension);
        }
        elseif ( ! $extension instanceof Extension )
        {
            $extension = $this->get($extension);
        }
        $extension->register();

        return $this;
    }


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
