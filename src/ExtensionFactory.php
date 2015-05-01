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
use Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Collection;
use Laradic\Extensions\Contracts\Extensions as ExtensionsContract;
use Laradic\Extensions\Traits\ExtensionDbRecordTrait;
use Laradic\Support\Path;
use Laradic\Support\Sorter;
use Laradic\Support\Traits\DotArrayAccess;
use Laradic\Support\Traits\EventDispatcher;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class ExtensionCollection
 *
 * @package     Laradic\Extensions
 */
class ExtensionFactory implements ArrayAccess, ExtensionsContract
{

    use DotArrayAccess, EventDispatcher, ExtensionDbRecordTrait;

    /**
     * {@inheritDoc}
     */
    protected function getArrayAccessor()
    {
        return 'attributes';
    }

    /** @var Extension[] */
    protected $extensions = [ ];

    /**
     * get
     *
     * @param string $slug
     * @return Extension
     */
    public function get($slug)
    {
        return $this->extensions[ $slug ];
    }

    /**
     * has
     *
     * @param mixed $slug
     * @return bool
     */
    public function has($slug)
    {
        return isset($this->extensions[ $slug ]);
    }

    /**
     * all
     *
     * @return Extension[]
     */
    public function all()
    {
        return $this->extensions;
    }

    public function on($eventName, \Closure $cb)
    {
        $this->registerEvent($eventName, $cb);
    }

    /**
     * @var \Laradic\Support\Filesystem
     */
    protected $files;

    /** @var \Laradic\Extensions\ExtensionFileFinder */
    protected $finder;

    /** @var \Illuminate\Foundation\Application */
    protected $app;

    /** @var \Illuminate\Database\ConnectionInterface */
    protected $connection;

    /**
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * @var array
     */
    protected $records;

    /**
     * @var mixed
     */
    protected $defaultAttributes;

    /**
     * Instanciates the class
     *
     * @param \Illuminate\Contracts\Foundation\Application     $app
     * @param \Laradic\Support\Filesystem                      $files
     * @param \Laradic\Extensions\ExtensionFileFinder          $finder
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @internal param \Illuminate\Database\ConnectionInterface $connection
     */
    public function __construct(
        Application $app,
        ExtensionFileFinder $finder,
        ConnectionResolverInterface $resolver
    ) {
        $this->resolver = $resolver;
        $this->app      = $app;
        $this->finder   = $finder;

        $this->connection        = $resolver->connection();
        $this->files             = $app->make('files');
        $this->defaultAttributes = Config::get('laradic/extensions::defaultExtensionAttributes');
        $this->extensions        = [ ];
        $this->records           = [ ];
    }


    /**
     * make
     *
     * @return Extension
     */
    protected function make($attributes)
    {
        $attributes             = array_replace_recursive($this->defaultAttributes, $attributes);
        $class                  = $attributes[ 'class' ];
        return new $class($this->app, $this, $attributes);
    }

    /**
     * Checks if an extension is installed
     *
     * @param $slug
     * @return bool
     */
    public function isInstalled($slug)
    {
        return isset($this->records[ $slug ]) and $this->records[ $slug ] === 1;
    }

    protected function findAll()
    {
        $found = $this->finder->findAll();
        foreach ( $found as $slug => $attributes )
        {
            if ( ! isset($this->records[ $slug ]) )
            {
                $this->recordCreate($slug);
            }
            if ( ! isset($this->extensions[ $slug ]) )
            {
                $dependencies                           = (isset($attributes[ 'dependencies' ]) and is_array($attributes[ 'dependencies' ])) ? $attributes[ 'dependencies' ] : [ ];
                $extension = $this->extensions[ $slug ] = $this->make($attributes);
            }
        }

        return $this;
    }

    /**
     * registerAll
     */
    protected function registerAll()
    {
        foreach ( $this->getSorter()->sort() as $slug )
        {
            if ( ! $this->isInstalled($slug) )
            {
                continue;
            }
            $ex = $this->extensions[ $slug ];
            $v  = $ex->getVersion();
            $this->app->register($ex);
        }
    }

    /**
     * Finds all extensions and registers them
     *
     * @return $this
     */
    public function findAndRegisterAll()
    {
        $this->updateRecords();
        $this->findAll();
        $this->registerAll();

        return $this;
    }

    public function getSorter()
    {
        $sorter = new Sorter();
        $sorter->add($this->extensions);

        return $sorter;
    }

    public function getSortedByDependency()
    {
        $extensions = [ ];
        $sorted     = $this->getSorter()->sort();

        foreach ( $sorted as $slug )
        {
            $extensions[ $slug ] = $this->get($slug);
        }

        return new Collection($extensions);
    }





    /**
     * Get the value of connection
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Sets the value of connection
     *
     * @param ConnectionInterface $connection
     * @return ConnectionInterface
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the value of resolver
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Sets the value of resolver
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function setResolver($resolver)
    {
        $this->resolver = $resolver;

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
     * Get the value of app
     *
     * @return \Illuminate\Foundation\Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * get files value
     *
     * @return \Laradic\Support\Filesystem
     */
    public function getFiles()
    {
        return $this->files;
    }



}
