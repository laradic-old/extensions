<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;


use Illuminate\Contracts\Foundation\Application;
use Laradic\Config\Traits\ConfigProviderTrait;
use Laradic\Support\ServiceProvider;
use Laradic\Support\TemplateParser;

/**
 * Class ExtensionsServiceProvider
 *
 * @package     Laradic\Extensions
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class ExtensionsServiceProvider extends ServiceProvider
{
    use ConfigProviderTrait;

    protected $dir = __DIR__;

    protected $migrationDirs = [ 'migrations' ];

    protected $providers = [ 'Laradic\Extensions\Providers\ConsoleServiceProvider' ];

    protected $provides = [ 'extensions', 'extensions.finder', 'extensions.generator' ];

    /**
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    protected $installed = false;

    public function boot2()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = parent::boot();
        if ( ! $this->installed )
        {
            return;
        }
        $extensions = $app->make('extensions')->getSortedByDependency()->all();

        foreach ( $extensions as $extension )
        {
            if ( $extension->isInstalled() )
            {
                $extension->boot();
            }
        }
    }

    /**
     * Instanciates the class
     */
    public function register()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = parent::register();

        $config = $this->addConfigComponent('laradic/extensions', 'laradic/extensions', realpath(__DIR__ . '/../resources/config'));

        $db               = $app->make('db');
        $this->connection = $db->connection($db->getDefaultConnection());
        $this->installed  = \Schema::setConnection($this->connection)->hasTable($config['table']);

        if ( ! $this->installed )
        {
            return;
        }

        $this->registerExtensions();
        $this->registerGenerator();


        $app->make('extensions')->findAndRegisterAll();
    }

    protected function registerExtensions()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        $app->bind('extensions.finder', function (Application $app)
        {
            $finder = new ExtensionFileFinder($app->make('files'));
            $finder->addPath($app->make('config')->get('laradic/extensions::paths'));

            return $finder;
        });


        $app->singleton('extensions', function (Application $app)
        {
            return new ExtensionFactory($app, $app->make('extensions.finder'), $app->make('db'));
        });
        $app->bind('Laradic\Extensions\Contracts\Extensions', 'extensions');
        $this->alias('Extensions', 'Laradic\Extensions\Facades\Extensions');

    }

    protected function registerGenerator()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        $app->bind('extensions.generator', function (Application $app)
        {
            $parser = new TemplateParser($app->make('files'), realpath(__DIR__ . '/../resources/stubs'));

            return $parser;
        });
    }
}
