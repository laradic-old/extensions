<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions;

use ArrayAccess;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laradic\Support\String;
use Laradic\Support\Traits\DotArrayAccess;
use Laradic\Support\Traits\DotArrayObjectAccess;

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
class BaseExtension extends ServiceProvider implements ArrayAccess
{
    use DotArrayAccess, DotArrayObjectAccess;

    /**
     * {@inheritDoc}
     */
    protected function getArrayAccessor()
    {
        return 'attributes';
    }

    /**
     * @var array
     */
    protected $attributes = [ ];

    protected $sorter;

    protected $extensions;


    /**
     * {@inheritDoc}
     */
    public function __construct($app, ExtensionFactory $extensions, array $attributes)
    {
        parent::__construct($app);
        $this->attributes = $attributes;
        $this->extensions = $extensions;
        $this->sorter =& $attributes['sorter'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    public function isInstalled()
    {
        return $this->extensions->getInstaller()->isInstalled($this);
    }

    /**
     * make
     *
     * @param \Illuminate\Foundation\Application   $application
     * @param \Laradic\Extensions\ExtensionFactory $extensions
     * @param array                                $attributes
     * @return static
     */
    public static function make(Application $application, ExtensionFactory $extensions, array $attributes)
    {
        return new static($application, $extensions, $attributes);
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
     * get sorter value
     *
     * @return mixed
     */
    public function getSorter()
    {
        return $this->sorter;
    }

    /**
     * get app value
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Set the app value
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return ServiceProvider
     */
    public function setApp($app)
    {
        $this->app = $app;

        return $this;
    }

    public function __call($method, $parameters)
    {
        $key = (string) String::removeLeft(strtolower($method), 'get');
        if(isset($this->attributes[$key]))
        {
            return $this->attributes[$key];
        }
        return parent::__call($method, $parameters);
    }
}
