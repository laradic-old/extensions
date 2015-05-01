<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions;

use Config;
use Illuminate\Database\ConnectionResolverInterface;
use Laradic\Support\Filesystem;
use Laradic\Support\Path;

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
    protected $resolver;
    protected $finder;
    protected $builder;
    protected $records;

    /** Instantiates the class
     *
     * @param \Laradic\Extensions\ExtensionFactory             $extensions
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param \Laradic\Support\Filesystem                      $files
     * @param \Laradic\Extensions\ExtensionFileFinder          $finder
     */
    public function __construct(ExtensionFileFinder $finder, ConnectionResolverInterface $resolver)
    {
        $this->finder = $finder;
        $this->resolver = $resolver;
        $this->updateAllRecords();
    }




}
