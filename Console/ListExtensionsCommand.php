<?php
 /**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Console;

use Laradic\Support\AbstractConsoleCommand;

/**
 * Class ListExtensionsCommand
 *
 * @package     Laradic\Extensions\Console
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class ListExtensionsCommand extends AbstractConsoleCommand
{
    protected $name = 'extensions:list';
    protected $description = 'List all extensions.';

    public function fire()
    {
        $extensions = app('extensions');

        $rows = [];
        foreach($extensions->all() as $extension)
        {
            /** @var \Laradic\Extensions\Extension $extension */
            $rows[] = [$extension->getName(), $extension->getSlug(), $extension->getPath(), $extension->isInstalled() ? $this->colorize('green', 'Y') : $this->colorize('yellow', 'N')];
        }

        $this->table(['Name', 'Slug', 'Path', 'Installed'], $rows);

    }
}
