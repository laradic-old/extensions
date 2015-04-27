<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use Illuminate\Filesystem\Filesystem;
use Laradic\Support\Path;
use Symfony\Component\Finder\Finder as BaseFinder;

/**
 * Class Finder
 *
 * @package     Laradic\Extensions
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class ExtensionFileFinder
{

    protected $files;

    protected $paths = [];

    public function __construct(Filesystem $files, array $paths = array())
    {
        $this->files = $files;
        $this->paths = $paths;
    }

    public function findInPath($path)
    {
        if ( ! $filePaths = $this->files->glob(Path::join($path, '*/*/extension.php')) )
        {
            return [];
        }

        return $filePaths;
    }

    public function findAll()
    {
        $filePaths = [];
        foreach($this->paths as $path)
        {
            $filePaths = array_merge($filePaths, $this->findInPath($path));
        }
        return $filePaths;
    }


    public function addPath($path)
    {
        if ( is_array($path) )
        {
            foreach ($path as $p)
            {
                $this->addPath($p);
            }
        }
        else
        {
            $this->paths[] = $path;
        }
    }

    /**
     * Get the value of paths
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }
}
