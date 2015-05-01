<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions;

use ArrayAccess;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Laradic\Config\Traits\ConfigProviderTrait;
use Laradic\Extensions\Contracts\Extension as ExtensionContract;
use Laradic\Support\Contracts\Dependable;
use Laradic\Support\ServiceProvider;
use Laradic\Support\Traits\DotArrayAccess;
use Laradic\Support\Traits\DotArrayObjectAccess;
use Laradic\Themes\Traits\ThemeProviderTrait;
use vierbergenlars\SemVer\version;

/**
 * This is the BaseExtension class.
 *
 * @package        Laradic\Extensions
 * @version        1.0.0
 * @author         Robin Radic
 * @license        MIT License
 * @copyright      2015, Robin Radic
 * @link           https://github.com/robinradic
 */
abstract class Extension extends ServiceProvider implements ExtensionContract, ArrayAccess, Dependable
{
    use DotArrayAccess, DotArrayObjectAccess,
        ConfigProviderTrait, ThemeProviderTrait;


    /**
     * getArrayAccessor
     *
     * @return mixed
     */
    protected function getArrayAccessor()
    {
        return 'attributes';
    }

    protected $attributes = [ ];

    protected $extensions;

    protected $files;

    public function __construct(Application $app, ExtensionFactory $extensions, array $attributes)
    {
        parent::__construct($app);
        $this->extensions = $extensions;
        $this->attributes = $attributes;
        $this->files      = $extensions->getFiles();
    }

    public function isInstalled()
    {
        return $this->extensions->isInstalled($this[ 'slug' ]);
    }

    public function getSlug()
    {
        return $this[ 'slug' ];
    }

    public function getName()
    {
        return $this[ 'name' ];
    }

    public function getPath()
    {
        return $this[ 'path' ];
    }

    /**
     * get item key/identifier
     *
     * @return string|mixed
     */
    public function getHandle()
    {
        return $this->getSlug();
    }

    public function getDependencies()
    {
        return $this->dependencies;
    }

    public function getVersion()
    {
        return new version($this->version);
    }

    /**
     * @var array
     */
    protected $dependencies = [ ];

    protected $version;

    /**
     * @var array
     */
    protected $migrations = [ ];

    /**
     * @var array
     */
    protected $configurations = [ ];

    /**
     * @var array
     */
    protected $seeds = [ ];

    /**
     * Path to resources folder, relative to $dir
     *
     * @var string
     */
    protected $resourcesPath = '../resources';

    /**
     * @var array
     */
    protected $providers = [ ];

    /**
     * @var array
     */
    protected $aliases = [ ];

    /**
     * @var array
     */
    protected $middlewares = [ ];

    /**
     * @var array
     */
    protected $prependMiddlewares = [ ];

    /**
     * @var array
     */
    protected $routeMiddlewares = [ ];

    /**
     * @var array
     */
    protected $provides = [ ];


    /**
     * Boots the service provider.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function boot()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        if ( isset($this->dir) and isset($this->configFiles) and is_array($this->configFiles) )
        {
            foreach ( $this->configFiles as $fileName )
            {
                $configPath = $this->dir . '/' . $this->resourcesPath . '/config/' . $fileName . '.php';
                $this->publishes([ $configPath => config_path($fileName . '.php') ], 'config');
            }
        }

        return $app;
    }

    /**
     * Register the service provider.
     *
     * @return \Illuminate\Foundation\Application
     * @final
     */
    public function register()
    {

        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;


        $router = $app->make('router');
        $kernel = $app->make('Illuminate\Contracts\Http\Kernel');

        # CONFIG
        if ( count($this->configurations) === 0 )
        {
            $this->addConfigComponent($this->getSlug(), $this->getSlug(), path_join($this->getPath(), 'resources/config'));
        }
        else
        {
            foreach ( $this->configurations as $ns => $path )
            {
                $this->addConfigComponent($ns, $ns, $path);
            }
        }

        foreach ( $this->migrations as $path => $autoManage )
        {
            if ( $autoManage === false )
            {
                $this->publishes([ $path => base_path('/database/migrations') ], 'migrations');
            }
        }

        foreach ( $this->prependMiddlewares as $middleware )
        {
            $kernel->prependMiddleware($middleware);
        }

        foreach ( $this->middlewares as $middleware )
        {
            $kernel->pushMiddleware($middleware);
        }

        foreach ( $this->routeMiddlewares as $key => $middleware )
        {
            $router->middleware($key, $middleware);
        }

        foreach ( $this->providers as $provider )
        {
            $app->register($provider);
        }

        foreach ( $this->aliases as $alias => $full )
        {
            $this->alias($alias, $full);
        }


        return $app;
    }

    /**
     * alias
     *
     * @param $name
     * @param $fullyQualifiedName
     */
    protected function alias($name, $fullyQualifiedName)
    {
        AliasLoader::getInstance()->alias($name, $fullyQualifiedName);
    }

    /**
     * {@inheritDoc}
     */
    public function provides()
    {
        return $this->provides;
    }


    public function onInstall()
    {
    }

    public function onInstalled()
    {
    }

    public function onUninstall()
    {
    }

    public function onUninstalled()
    {
    }


    /**
     * get attributes value
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * get extensions value
     *
     * @return \Laradic\Extensions\ExtensionFactory
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * get files value
     *
     * @return mixed
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * get migrations value
     *
     * @return array
     */
    public function getMigrations()
    {
        return $this->migrations;
    }

    /**
     * get configurations value
     *
     * @return array
     */
    public function getConfigurations()
    {
        return $this->configurations;
    }

    /**
     * get providers value
     *
     * @return array
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * get aliases value
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * get middlewares value
     *
     * @return array
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }

    /**
     * get prependMiddlewares value
     *
     * @return array
     */
    public function getPrependMiddlewares()
    {
        return $this->prependMiddlewares;
    }

    /**
     * get routeMiddlewares value
     *
     * @return array
     */
    public function getRouteMiddlewares()
    {
        return $this->routeMiddlewares;
    }

    /**
     * get provides value
     *
     * @return array
     */
    public function getProvides()
    {
        return $this->provides;
    }

    /**
     * get publishes value
     *
     * @return array
     */
    public static function getPublishes()
    {
        return self::$publishes;
    }

    /**
     * get publishGroups value
     *
     * @return array
     */
    public static function getPublishGroups()
    {
        return self::$publishGroups;
    }

    /**
     * get seeds value
     *
     * @return array
     */
    public function getSeeds()
    {
        return $this->seeds;
    }


}
