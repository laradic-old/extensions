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
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Laradic\Extensions\Contracts\Extensions as ExtensionsContract;
use Laradic\Support\Sorter;
use Laradic\Support\Traits\DotArrayAccess;

/**
 * Class ExtensionCollection
 *
 * @package     Laradic\Extensions
 */
class ExtensionFactory implements ArrayAccess, ExtensionsContract
{
    use DotArrayAccess;

    /**
     * {@inheritDoc}
     */
    protected function getArrayAccessor()
    {
        return 'extensions';
    }

    /** @var Extension[] */
    protected $extensions;

    /**
     * get extensions value
     *
     * @return Extension[]
     */
    public function getExtensions()
    {
        return $this->extensions;
    }


    /** @var \Laradic\Support\Filesystem */
    protected $files;

    /** @var \Laradic\Extensions\ExtensionFileFinder */
    protected $finder;

    /** @var \Illuminate\Foundation\Application */
    protected $app;


    /** @var \Illuminate\Database\ConnectionInterface */
    protected $connection;

    protected $resolver;

    protected $sorter;

    protected $records;

    protected $defaultAttributes = [ ];

    /**
     * Instanciates the class
     *
     * @param \Illuminate\Foundation\Application               $app
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
        $this->resolver   = $resolver;
        $this->connection = $resolver->connection($resolver->getDefaultConnection());
        $this->app        = $app;

        $this->finder = $finder;

        $this->defaultAttributes = Config::get('laradic/extensions::defaultExtensionAttributes');
        $this->sorter            = new Sorter();
        $this->extensions        = [ ];
    }


    /**
     * query on the extension table
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function query()
    {
        return $this->resolver->connection()->table(Config::get('laradic/extensions::table'));
    }

    public function updateAllRecords()
    {
        $this->records = [ ];
        foreach ( $this->query()->get() as $record )
        {
            $this->records[ $record->slug ] = $record->installed;
        }
    }

    protected function dbGetBySlug($slug)
    {
        return $this->query()->where('slug', '=', $slug)->first();
    }

    protected function dbCreate($slug)
    {
        return $this->query()->insert([ 'slug' => $slug, 'installed' => 0 ]);
    }

    protected function dbInstall($slug)
    {
        $this->query()->where('slug', '=', $slug)->update([ 'installed' => 1 ]);
    }

    protected function dbUninstall($slug)
    {
        $this->query()->where('slug', '=', $slug)->update([ 'installed' => 0 ]);
    }

    /**
     * make
     *
     * @return Extension
     */
    protected function register($attributes)
    {
        $attributes             = array_replace_recursive($this->defaultAttributes, $attributes);
        $attributes[ 'sorter' ] =& $this->sorter;

        return BaseExtension::make($this->app, $this, $attributes);
    }

    public function get($slug)
    {
        return $this->extensions[ $slug ];
    }

    public function has($slug)
    {
        return isset($this->extensions[ $slug ]);
    }

    public function all()
    {
        return $this->extensions;
    }


    /**
     * Finds all extensions and registers them
     *
     * @return $this
     */
    public function findAndRegisterAll()
    {
        $this->updateAllRecords();

        $found = $this->finder->findAll();

        foreach ( $found as $slug => $attributes )
        {
            if ( ! isset($this->records[ $slug ]) )
            {
                $this->dbCreate($slug);
            }

            if ( ! isset($this->extensions[ $slug ]) )
            {
                $dependencies = (isset($attributes[ 'dependencies' ]) and is_array($attributes[ 'dependencies' ])) ? $attributes[ 'dependencies' ] : [ ];
                $this->sorter->addItem($slug, $dependencies);
                $this->extensions[ $slug ] = $this->register($attributes);
            }
        }

        return $this;
    }


    public function install(Extension $extension)
    {
    }

    public function canInstall(Extension $extension)
    {
        $this->sorter->sort()
    }

    public function isInstalled(Extension $extension)
    {
        return isset($this->records[ $extension->getSlug() ]) and $this->records[ $extension->getSlug() ] === 1;
    }

    public function uninstall(Extension $extension)
    {
    }

    public function canUninstall(Extension $extension)
    {
    }

    public function seed(Extension $extension)
    {
    }

    public function migrate(Extension $extension)
    {
    }

    protected function runSeed($filePath, $className = null)
    {
        $seeder = $this->app->make('seeder');
        $this->files->requireOnce($filePath);
        $className = isset($className) ? $className : Path::getFilenameWithoutExtension($filePath);
        $seeder->call($className);
        #Debugger::dump("Seeded $filePath / $className");
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
     * get installer value
     *
     * @return \Laradic\Extensions\Installer
     */
    public function getInstaller()
    {
        return $this->installer;
    }



}
