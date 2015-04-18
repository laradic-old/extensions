<?php
 /**
 * Part of the Laradic packages.
 */
namespace {namespace};
use Laradic\Support\ServiceProvider;
use Laradic\Config\Traits\ConfigProviderTrait;
use Laradic\Themes\Traits\ThemeProviderTrait;
/**
 * Class ServiceProvider
 *
 * @package     {namespace}
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class {packageName}ServiceProvider extends ServiceProvider
{

    use ConfigProviderTrait, ThemeProviderTrait;
    public function boot()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = parent::boot();
        $this->addPackagePublisher('{vendor}/{package}', realpath(__DIR__ . '/../resources/theme'));
    }

    public function register()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = parent::register();
        $this->addConfigComponent('{vendor}/{package}', '{vendor}/{package}', realpath(__DIR__.'/../resources/config'));
    }
}
