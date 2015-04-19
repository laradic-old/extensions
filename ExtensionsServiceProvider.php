<?php
 /**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;


use Illuminate\Contracts\Foundation\Application;
use Laradic\Config\Traits\ConfigProviderTrait;
use Laradic\Extensions\Models\Extension as ExtensionModel;
use Laradic\Extensions\Repositories\EloquentExtensionRepository;
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

    protected $migrationDirs = ['migrations'];

    public function boot()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = parent::boot();

        foreach($app->make('extensions')->sortByDependencies()->all() as $extension)
        {
            if($extension->isInstalled())
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

        $this->addConfigComponent('laradic/extensions', 'laradic/extensions', realpath(__DIR__.'/resources/config'));
        #$this->publishes([__DIR__.'/resources/migrations/' => base_path('/database/migrations')], 'migrations');

        $this->registerEloquentExtensions();
        $this->registerExtensions();
        $this->registerGenerator();

        if($app->runningInConsole())
        {
            $app->register('Laradic\Extensions\Providers\ConsoleServiceProvider');
        }

    }

    protected function registerEloquentExtensions()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        $app->singleton('extensions.repository', function(Application $app)
        {
            return new EloquentExtensionRepository('Laradic\Extensions\Models\Extension');
        });
        $this->alias('extensions.repository', 'Laradic\Extensions\Contracts\ExtensionRepository');
    }

    protected function registerExtensions()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        $app->bind('extensions.finder', function(Application $app){
            $finder = new ExtensionFileFinder($app->make('files'));
            $finder->addPath($app->make('config')->get('laradic/extensions::paths'));
            return $finder;
        });

        $app->singleton('extensions', function(Application $app){
            return new ExtensionCollection($app, $app->make('files'), $app->make('extensions.finder'), $app->make('extensions.repository'));
        });
        $this->alias('extensions', 'Laradic\Extensions\Contracts\Extensions');
        $this->alias('Extensions', 'Laradic\Extensions\Facades\Extensions');
        $app->make('extensions')->locateAndRegisterAll()->sortByDependencies();

        $app->bind('Laradic\Extensions\Extension', function(Application $app)
        {
            return new Extension($app->make('extensions'), $app->make('extensions.repository'));
        });
    }

    protected function registerGenerator()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        $app->bind('extensions.generator', function(Application $app){
            $parser = new TemplateParser($app->make('files'), realpath(__DIR__ . '/resources/stubs'));
            return $parser;
        });
    }
}
