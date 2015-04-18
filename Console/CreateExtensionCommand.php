<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Illuminate\Filesystem\Filesystem;
use Laradic\Support\Path;
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
        $packageName = ucfirst($package);
        $namespace = ucfirst($vendor) . '\\' . $packageName;
        $autoloadPsr4 = ucfirst($vendor) . '\\\\' . $packageName . '\\\\';
        $basePath = is_null($path) ? head($extensions->getFinder()->getPaths()) : base_path($path);
        $path = Path::join($basePath, $vendor, $package);

        $parser = $extensions->getTemplateParser();
        $vars = compact('vendor', 'package', 'packageName', 'basePath', 'path', 'namespace', 'autoloadPsr4');
        $parser->setVar($vars);
        $this->dump($vars);

        $this->createDirStructure($files, $path);
        $parser->copy($this->copyFiles, $path);
        $files->move(Path::join($path, 'src/ServiceProvider.php'), Path::join($path, "src/{$packageName}ServiceProvider.php"));
    }

    protected function createDirStructure(Filesystem $files, $path)
    {
        $files->deleteDirectory($path);
        foreach($this->createDirs as $dir)
        {
            $files->makeDirectory(Path::join($path, $dir), 0755, true);
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
