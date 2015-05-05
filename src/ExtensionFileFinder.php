<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions;

use Illuminate\Filesystem\Filesystem;
use Laradic\Extensions\Exceptions\ExtensionNotFoundException;
use Laradic\Support\Path;
use Laradic\Support\String;
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

    public function __construct(Filesystem $files, array $paths = array())
    {
        $this->files = $files;
        $this->paths = $paths;
    }

    public function findInPath($path)
    {
        if ( ! $filePaths = $this->files->glob(Path::join($path, '*/*/*Extension.php')) )
        {
            return [ ];
        }

        return $filePaths;
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
                $this->files->requireOnce($filePath);
                $classPath = (string)String::removeLeft($filePath, $path . '/')->removeRight('.php')->namespacedStudly();
                $info      = forward_static_call([ $classPath, 'getInfo' ]);
                if ( isset($found[ $info[ 'slug' ] ]) )
                {
                    throw new \Exception('Duplicate slug for extension [' . $info[ 'slug' ] . '] found');
                }
                else
                {
                    // $attributes[ 'namespace' ] = String::namespacedStudly($attributes['slug']);
                    $info[ 'path' ]           = Path::getDirectory($filePath);
                    $info[ 'class' ]          = $classPath;
                    $found[ $info[ 'slug' ] ] = $info;
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
