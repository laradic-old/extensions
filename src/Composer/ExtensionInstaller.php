<?php
 /**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;

/**
 * Class ExtensionInstaller
 *
 * @package     Laradic\Extensions\Composer
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class ExtensionInstaller extends LibraryInstaller
{

    /**
     * getPackageBasePath
     *
     * @param \Composer\Package\PackageInterface $package
     * @return string
     */
    public function getPackageBasePath(PackageInterface $package)
    {
        return __DIR__.'/../../../../../extensions/'.$package->getPrettyName();
    }


    /**
     * supports
     *
     * @param $packageType
     * @return bool
     */
    public function supports($packageType)
    {
        return $packageType == 'laradic-extension';
    }
}
