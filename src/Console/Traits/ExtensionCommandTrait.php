<?php
/**
 * Part of the Laradic packages.
 * MIT License and copyright information bundled with this package in the LICENSE file.
 *
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
namespace Laradic\Extensions\Console\Traits;

use Laradic\Extensions\Console\Create\CreateMigrationCommand;
use Laradic\Support\String;

/**
 * Class ExtensionCommandTrait
 *
 * @package     Extensions\Console\Traits
 * @method \Illuminate\Foundation\Application getLaravel()
 * @method \Illuminate\Console\Application getApplication()
 */
trait ExtensionCommandTrait
{
    /**
     * getExtensions
     *
     * @return \Laradic\Extensions\ExtensionFactory
     */
    public function getExtensions()
    {
        return $this->getLaravel()->make('extensions');
    }

    /**
     * getFiles
     *
     * @return \Laradic\Support\Filesystem
     */
    public function getFiles()
    {
        return $this->getLaravel()->make('files');
    }

    /**
     * getExtension
     *
     * @param $slug
     * @return \Laradic\Extensions\Extension
     */
    public function getExtension($slug)
    {
        return $this->getExtensions()->get($slug);
    }

    public function createExtensionMigration($slug, array $parameters = null)
    {
        $path = $this->getExtension($slug)->getPath('migrations');
        list($vendor, $package) = $this->getSlugVendorAndPackage($slug);
        $parameters = is_null($parameters) ? ["name" => "create_${vendor}_${package}", "table" => "extension_${vendor}_${package}"] : $parameters;

        $migrationCreator = $this->getLaravel()->make('migration.creator');
        $composer = $this->getLaravel()->make('composer');
        $command = new CreateMigrationCommand($migrationCreator, $composer);
        $command->setMigrationPath($path);
        $this->getApplication()->add($command);
        $this->getApplication()->call('extensions:create:migration', $parameters);
    }

    /**
     * Get the date prefix for the migration.
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }
}
