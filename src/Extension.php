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
use Laradic\Support\Path;
use Laradic\Support\ServiceProvider;
use Laradic\Support\Traits\DotArrayAccess;
use Laradic\Support\Traits\DotArrayObjectAccess;
use vierbergenlars\SemVer\version;

/**
 * This is the abstract Extension class. All extensions should have a class in the root dir extending this class.
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
        ConfigProviderTrait;


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

    /**
     * get item key/identifier
     *
     * @return string|mixed
     */
    public function getHandle()
    {
        return $this['slug'];
    }

    /**
     * Creates an instance of the extension
     *
     * @param \Illuminate\Foundation\Application   $app
     * @param \Laradic\Extensions\ExtensionFactory $extensions
     * @param array                                $attributes
     */
    public function __construct(Application $app, ExtensionFactory $extensions, array $attributes)
    {
        parent::__construct($app);
        $this->extensions = $extensions;
        $this->attributes = $attributes;
        $this->files      = $extensions->getFiles();
    }

    /**
     * Returns true if the extension is installed
     *
     * @return bool
     */
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
     * The (extra) migrations you want to include for auto-management or self-management
     *
     * By default, on install/uninstall the resources/migrations folder will be checked for migrations.
     * If you want to disable the auto install/uninstall behaviour, you can set this to false.
     *
     * Optionally, if you want to specify optional migration folders you can add them in the array.
     * If provided with true, those migrations will be auto installed/uninstalled.
     * If provided false, those migrations will be made available by the vendor:publish command
     *
     * @var array
     * @example
     *  protected $migrations = array(
     *      "path/to/manualy/publish/and/migrate/migrations" => false,
     *      "path/to/automaticly/managed/migrations" => true,
     *  );
     *
     *  protected $migrations = false;
     */
    protected $migrations = [ ];

    /**
     * The (extra) configuration directories you want to include.
     *
     * The configuration makes use of the https://github.com/laradic/config package.
     *
     * These will be available directly inside your *Extension.php file and project source code.
     * Each directory will be bound to a namespace like so:
     *
     * @var array|bool
     * @example
     *  protected $configurations = array(
     *      "vendor/package" => __DIR__ . "/path/to/configurations/files",
     *      "awesome/stuff" => __DIR__ . "/../path/to/other/configurations/files"
     *  );
     *
     *  // Disable handling configuration
     *  protected $configurations = false;
     *
     *  // Then in your code you can access it like
     *  echo Config::get("awesome/stuff::dot.notated.key");
     */
    protected $configurations = [ ];

    /**
     * @var array
     */
    protected $seeds = [ ];

    /**
     * The (extra) migration directories you want to include for auto-management or self-management
     *
     * Setting this to false, will disable automatic theme registration.
     * Otherwise, the extension will automaticly register (if exists) the theme located at
     * resources/theme and bind it to "vendor/package" aka the extensions slug.
     *
     * Optionally, you can define other locations and namespaces.
     *
     * @var array|bool
     * @example
     *  protected $themes = array(
     *      "vendor/package" => __DIR__ . "/path/to/theme/files",
     *      "awesome/stuff" => __DIR__ . "/../path/to/other/theme/files"
     *  );
     *
     *  protected $themes = false;
     */
    protected $themes = [ ];

    /**
     * Path to resources folder, relative to $dir
     *
     * @var string
     */
    protected $resourcesPath = 'resources';

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

        if(is_array($this->themes) and class_exists('Laradic\Themes\ThemeServiceProvider') and $app->bound('themes'))
        {
            $themes = $app->make('themes');
            $themes->addPackagePublisher($this->getSlug(), Path::join($this->getPath(), $this->resourcesPath, 'theme'));

            foreach ( $this->configurations as $ns => $path )
            {
                $themes->addPackagePublisher($ns, $path);
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
        if(is_array($this->configurations))
        {
            $defaultConfigPath = Path::join($this->getPath(), $this->resourcesPath, 'config');
            if($this->files->exists($defaultConfigPath))
            {
                $this->addConfigComponent($this->getSlug(), $this->getSlug(), $defaultConfigPath);
            }
            foreach ( $this->configurations as $ns => $path )
            {
                $this->addConfigComponent($ns, $ns, $path);
            }
        }

        # MIGRATIONs
        if(is_array($this->migrations))
        {
            foreach ( $this->migrations as $path => $autoManage )
            {
                if ( $autoManage === false )
                {
                    $this->publishes([ $path => base_path('/database/migrations') ], 'migrations');
                }
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

    /**
     * get themes value
     *
     * @return array|bool
     */
    public function getThemes()
    {
        return $this->themes;
    }


}
