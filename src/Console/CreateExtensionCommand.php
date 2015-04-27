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
namespace Laradic\Extensions\Console;

use Laradic\Extensions\Console\Traits\ExtensionCommandTrait;
use Laradic\Console\Command;
use Laradic\Support\Path;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CreateExtensionCommand
 *
 * @package     Laradic\Extensions\Console
 */
class CreateExtensionCommand extends Command
{

    use ExtensionCommandTrait;

    protected $name = 'extensions:create';

    protected $description = 'Create an extensions.';


    public function fire2()
    {
        $slug = $this->argument('slug');
        $path = $this->argument('path');

        if ( ! $this->validateSlug($slug) )
        {
            return $this->error("Extension slug [$slug] is not valid");
        }

        $this->createExtensionMigration($slug);
        #$attr = $this->getExtension($slug)->getAttributes();
        #$this->dump($this->getExtension($slug)->getPath('migrations'));
    }

    public function fire()
    {
        $slug = $this->argument('slug');
        $path = $this->argument('path');

        if ( ! $this->validateSlug($slug) )
        {
            return $this->error("Extension slug [$slug] is not valid");
        }

        $files      = $this->getFiles();
        $extensions = $this->getExtensions();

        list($vendor, $package) = $this->getSlugVendorAndPackage($slug);
        $packageName  = studly_case($package);
        $vendorName   = studly_case($vendor);
        $namespace    = $vendorName . '\\' . $packageName;
        $autoloadPsr4 = $vendorName . '\\\\' . $packageName . '\\\\';

        $basePath = is_null($path) ? head($extensions->getFinder()->getPaths()) : base_path($path);
        $path     = Path::join($basePath, $vendor, $package);

        $parser = $extensions->getTemplateParser();
        $vars   = compact('vendor', 'package', 'packageName', 'basePath', 'path', 'namespace', 'autoloadPsr4');
        $parser->setVar($vars);

        $files->deleteDirectory($path);
        $parser->setSourceDir(Path::join(__DIR__, '../resources/stubs'));
        $parser->copyDirectory($path);

        $renameFiles = [
            "src/ServiceProvider.php"             => "src/{$packageName}ServiceProvider.php",
            "src/Http/Controllers/Controller.php" => "src/Http/Controllers/{$packageName}Controller.php"
        ];

        foreach ( $renameFiles as $renameFromPath => $renameToPath )
        {
            $files->move(Path::join($path, $renameFromPath), Path::join($path, $renameToPath));
        }

        $this->createExtensionMigration($slug);

        $this->info("Extension [${vendor}/${namespace}] created");
    }

    public function getArguments()
    {
        return [
            [ 'slug', InputArgument::REQUIRED, 'The extension slug' ],
            [ 'path', InputArgument::OPTIONAL, 'The extension base path' ]
        ];
    }
}
