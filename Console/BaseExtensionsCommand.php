<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Laradic\Support\AbstractConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;

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
    /** @var \Laradic\Extensions\ExtensionCollection  */
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
        preg_match('/([a-z]*)\/([a-z]*)/', $slug, $matches);
        return array_slice($matches, 1, 2);
    }

}
