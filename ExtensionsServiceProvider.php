<?php
 /**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use Illuminate\Contracts\Foundation\Application;
use Laradic\Config\Traits\ConfigProviderTrait;
use Laradic\Support\ServiceProvider;

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

   # protected $configFiles = ['laradic_extensions'];

   # protected $dir = __DIR__;

    public function boot()
    {
        parent::boot();

        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        foreach($app->make('extensions')->all() as $extension)
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
        parent::register();


        $this->addConfigComponent('laradic/extensions', 'laradic/extensions', realpath(__DIR__.'/resources/config'));


        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        $app->bind('extensions.finder', function(Application $app){
            $finder = new ExtensionFileFinder($app->make('files'));
            $finder->addPath($app->make('config')->get('laradic_extensions.paths'));
            return $finder;
        });

        $app->singleton('extensions', function(Application $app){
            return new ExtensionCollection($app, $app->make('files'), $app->make('extensions.finder'), $app->make('db')->connection());
        });

        $app->make('extensions')->locateAndRegisterAll()->sortByDependencies();

        if($app->runningInConsole())
        {
            $app->register('Laradic\Extensions\Providers\ConsoleServiceProvider');
        }

        $this->publishes([
            __DIR__.'/resources/migrations/' => base_path('/database/migrations')
        ], 'migrations');
    }
}
