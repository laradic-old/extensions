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

use Illuminate\Filesystem\Filesystem;
use Laradic\Support\Path;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CreateExtensionCommand
 *
 * @package     Laradic\Extensions\Console
 */
class CreateExtensionCommand extends BaseExtensionsCommand
{

    protected $name = 'extensions:create';

    protected $description = 'Create an extensions.';

    protected $createDirs = [
        'src',
        'resources', 'resources/config',
        'resources/theme', 'resources/theme/views'
    ];

    protected $copyFiles = [
        'extension.php', 'composer.json',
        'resources/config/config.php','resources/theme/views/index.blade.php',
        'src/ServiceProvider.php'
    ];

    public function fire()
    {
        $slug = $this->argument('slug');
        $path = $this->argument('path');

        if ( ! $this->validateSlug($slug) )
        {
            return $this->error("Extension slug [$slug] is not valid");
        }

        /** @var \Illuminate\Filesystem\Filesystem $files */
        $files = $this->getLaravel()->make('files');
        $extensions = $this->getExtensions();

        list($vendor, $package) = $this->getSlugVendorAndPackage($slug);
        $packageName = $this->convertSlugToClassName($package);
        $vendorName = $this->convertSlugToClassName($vendor);
        $namespace = $vendorName . '\\' . $packageName;
        $autoloadPsr4 = $vendorName . '\\\\' . $packageName . '\\\\';

        $basePath = is_null($path) ? head($extensions->getFinder()->getPaths()) : base_path($path);
        $path = Path::join($basePath, $vendor, $package);

        $parser = $extensions->getTemplateParser();
        $vars = compact('vendor', 'package', 'packageName', 'basePath', 'path', 'namespace', 'autoloadPsr4');
        $parser->setVar($vars);
        #$this->dump($vars);

        #$this->createDirStructure($files, $path);
        $files->deleteDirectory($path);
        $parser->setSourceDir(Path::join(__DIR__, '../resources/stubs'));
        $parser->copyDirectory($path);
        $renameFiles = [
            "src/ServiceProvider.php" => "src/{$packageName}ServiceProvider.php",
            "src/Http/Controllers/Controller.php" => "src/Http/Controllers/{$packageName}Controller.php"
        ];
        foreach($renameFiles as $renameFromPath => $renameToPath)
        {
            $files->move(Path::join($path, $renameFromPath), Path::join($path, $renameToPath));
        }
        #$parser->copy($this->copyFiles, $path);
        #$files->move(Path::join($path, 'src/ServiceProvider.php'), Path::join($path, "src/{$packageName}ServiceProvider.php"));
        $this->info("Extension [${vendor}/${namespace}] created");
    }

    protected function createDirStructure(Filesystem $files, $path)
    {
        $files->deleteDirectory($path);
        foreach($this->createDirs as $dir)
        {
            #$files->makeDirectory(Path::join($path, $dir), 0755, true);
        }
    }
    public function getArguments()
    {
        return [
            ['slug', InputArgument::REQUIRED, 'The extension slug'],
            ['path', InputArgument::OPTIONAL, 'The extension base path']
        ];
    }
}
