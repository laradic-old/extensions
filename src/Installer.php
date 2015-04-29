<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions;

/**
 * This is the Installer.
 *
 * @package        Laradic\Extensions
 * @version        1.0.0
 * @author         Robin Radic
 * @license        MIT License
 * @copyright      2015, Robin Radic
 * @link           https://github.com/robinradic
 */
class Installer
{
    protected $extensions;
    protected $finder;

    /** Instantiates the class
     *
     * @param \Laradic\Extensions\ExtensionFactory    $extensions
     * @param \Laradic\Extensions\ExtensionFileFinder $finder
     */
    public function __construct(ExtensionFactory $extensions)
    {
        $this->extensions = $extensions;
    }

    protected function resolveExtension($extension)
    {
        if(is_string($extension) and !$this->extensions->has($extension))
        {
            $this->extensions->locateAndRegisterAll();
        }

        if(is_string($extension))
        {
            $extension = $this->extensions->get($extension);
        }

        if(!$extension instanceof Extension)
        {
            throw new \Exception("Could not resolve extension [$extension]");
        }

        return $extension;
    }
}
