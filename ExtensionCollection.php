<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use Illuminate\Contracts\Foundation\Application;
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
 */
class ExtensionCollection extends Collection implements ExtensionsContract
{

    protected $files;

    protected $finder;

    protected $app;

    protected $extensions = [];

    /**
     * Instanciates the class
     */
    public function __construct(Application $app, Filesystem $files, ExtensionFileFinder $finder)
    {
        $this->app    = $app;
        $this->files  = $files;
        $this->finder = $finder;
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
            $this->register($extensionFilePath);
        }

        return $this;
    }

    public function register($extension)
    {
        if ( ! $extension instanceof Extension )
        {
            $extension = $this->createFromFile($extension);
        }
        $extension->register();
        $this->put($extension->getSlug(), $extension);

        return $this;
    }

    public function sortByDependencies()
    {
        $sorter = new Sorter();
        foreach ($this->all() as $extension)
        {
            /** @var \Laradic\Extensions\Extension $extension */
            $sorter->addItem($extension->getSlug(), $extension->getDependencies());
        }
        $extensions = [];
        foreach ($sorter->sort() as $slug)
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
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}
