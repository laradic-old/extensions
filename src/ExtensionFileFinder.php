<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use Config;
use Illuminate\Filesystem\Filesystem;
use Laradic\Extensions\Exceptions\ExtensionNotFoundException;
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

    protected $paths = [ ];

    protected $found = [ ];


    /**
     * @var \Laradic\Support\Sorter
     */
    protected $sorter;

    public function __construct(Filesystem $files, array $paths = array())
    {
        $this->files             = $files;
        $this->paths             = $paths;
    }

    protected function findInPath($path)
    {
        if ( ! $filePaths = $this->files->glob(Path::join($path, '*/*/extension.php')) )
        {
            return [ ];
        }

        return $filePaths;
    }

    protected function createExtensionFromFile($filePath)
    {
    }

    public function find($slug)
    {
        $found = $this->findAll();
        if ( isset($found[ $slug ]) )
        {
            return $found[ $slug ];
        }
        throw new ExtensionNotFoundException($slug);
    }

    public function findAll()
    {
        $found = [ ];
        foreach ( $this->paths as $path )
        {
            foreach ( $this->findInPath($path) as $filePath )
            {
                $attributes = $this->files->getRequire($filePath);
                if ( isset($found[ $attributes[ 'slug' ] ]) )
                {
                    throw new \Exception('Duplicate slug for extension [' . $attributes[ 'slug' ] . '] found');
                }
                else
                {
                    $attributes[ 'path' ]           = path_get_directory($filePath);
                    $found[ $attributes[ 'slug' ] ] = $attributes;
                }
            }
        }

        return $found;
    }

    public function addPath($path)
    {
        if ( is_array($path) )
        {
            foreach ( $path as $p )
            {
                $this->addPath($p);
            }
        }
        else
        {
            $this->paths[ ] = $path;
        }

        return $this;
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
