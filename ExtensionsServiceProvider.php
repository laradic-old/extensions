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

   # protected $configFiles = ['laradic_extensions'];

   # protected $dir = __DIR__;

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

        $this->publishes([
            __DIR__.'/resources/migrations/' => base_path('/database/migrations')
        ], 'migrations');

        #$app->make('config')->get

        $app->bind('extensions.finder', function(Application $app){
            $finder = new ExtensionFileFinder($app->make('files'));
            $finder->addPath($app->make('config')->get('laradic/extensions::paths'));
            return $finder;
        });

        $app->singleton('extensions', function(Application $app){
            return new ExtensionCollection($app, $app->make('files'), $app->make('extensions.finder'), $app->make('db')->connection());
        });
        $this->alias('extensions', 'Laradic\Extensions\Contracts\Extensions');
        $this->alias('Extensions', 'Laradic\Extensions\Facades\Extensions');
        $app->make('extensions')->locateAndRegisterAll()->sortByDependencies();


        $app->bind('extensions.generator', function(Application $app){
            $parser = new TemplateParser($app->make('files'), realpath(__DIR__ . '/resources/stubs'));
            return $parser;
        });

        if($app->runningInConsole())
        {
            $app->register('Laradic\Extensions\Providers\ConsoleServiceProvider');
        }

    }
}
