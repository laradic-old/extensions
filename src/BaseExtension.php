<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions;

use Illuminate\Support\ServiceProvider;

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
class BaseExtension extends ServiceProvider
{
    protected $name = '';
    protected $slug = '';
    protected $dependencies = [];
    protected $migrations = [];

    public function __construct($app)
    {
        parent::__construct($app);
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
