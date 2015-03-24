<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Providers;

use Illuminate\Contracts\Foundation\Application;
use Laradic\Support\ServiceProvider;

/**
 * Class ConsoleServiceProvider
 *
 * @package     Laradic\Extensions\Providers
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class ConsoleServiceProvider extends ServiceProvider
{

    protected $commands = [
        'command.extensions.list' => 'ListExtensionsCommand',
        'command.extensions.install' => 'InstallExtensionsCommand',
        'command.extensions.uninstall' => 'UninstallExtensionsCommand'
    ];

    public function register()
    {
        parent::register();
        /** @var \Illuminate\Contracts\Foundation\Application $app */
        $app = $this->app;

        foreach ($this->commands as $abstract => $class)
        {
            $class = "Laradic\\Extensions\\Console\\$class";
            $app->singleton($abstract, function (Application $app) use ($class)
            {
                return new $class();
            });
        }
        $this->commands(array_keys($this->commands));
    }

    public function provides()
    {
        return array_keys($this->commands);
    }
}
