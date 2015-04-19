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
     * @return \Laradic\Extensions\ExtensionCollection
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

    /**
     * validateSlug
     *
     * @param $slug
     * @return bool
     */
    protected function validateSlug($slug)
    {
        if ( ! preg_match('/([a-z]*)\/([a-z]*)/', $slug, $matches) or count($matches) !== 3 )
        {
            return false;
        }

        return true;
    }

    /**
     * getSlugVendorAndPackage
     *
     * @param $slug
     * @return string[]
     */
    protected function getSlugVendorAndPackage($slug)
    {
        preg_match('/([a-z\-]*)\/([a-z\-]*)/', $slug, $matches);

        return array_slice($matches, 1, 2);
    }

    /**
     * convertSlugToClassName
     *
     * @param $slug
     * @return string
     */
    protected function convertSlugToClassName($slug)
    {
        if ( $this->validateSlug($slug) )
        {
            list($vendor, $package) = $this->getSlugVendorAndPackage($slug);

            return $this->convertSlugToClassName($vendor) . '\\' . $this->convertSlugToClassName($package);
        }
        else
        {
            $className = '';
            if ( stristr($slug, '-') !== false )
            {
                $slugs = preg_split('/\-/', $slug);
            }
            else
            {
                $slugs = [$slug];
            }

            # VarDumper::dump($slugs);

            foreach ($slugs as $_slug)
            {
                $className .= ucfirst($_slug);
            }

            return $className;
        }
    }
}
