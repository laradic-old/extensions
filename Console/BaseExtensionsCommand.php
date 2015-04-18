<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Laradic\Support\AbstractConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class CreateExtensionCommand
 *
 * @package     Laradic\Extensions\Console
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
abstract class BaseExtensionsCommand extends AbstractConsoleCommand
{

    /** @var \Laradic\Extensions\ExtensionCollection */
    protected $extensions;

    /**
     * getExtensions
     *
     * @return \Laradic\Extensions\ExtensionCollection
     */
    protected function getExtensions()
    {
        return $this->getLaravel()->make('extensions');
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
