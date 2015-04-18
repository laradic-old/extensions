<?php
/**
 * Part of the Radic packages.
 */
namespace Laradic\Extensions\Providers;

use Laradic\Support\AbstractConsoleProvider;

/**
 * Class ConsoleServiceProvider
 *
 * @package     Laradic\Extensions\Providers
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class ConsoleServiceProvider extends AbstractConsoleProvider
{

    /**
     * The namespace where the commands are
     *
     * @var string
     */
    protected $namespace = 'Laradic\Extensions\Console';

    protected $commands = [
        'ListExtensions' => 'command.extensions.list',
        'InstallExtensions' => 'command.extensions.install',
        'UninstallExtensions' => 'command.extensions.uninstall',
        'CreateExtension' => 'command.extensions.create'
    ];


}
