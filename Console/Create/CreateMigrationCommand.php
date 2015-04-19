<?php
 /**
 * Part of the Laradic packages.
 * MIT License and copyright information bundled with this package in the LICENSE file.
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
namespace Laradic\Extensions\Console\Create;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Foundation\Composer;
use Laradic\Extensions\Console\Traits\ExtensionCommandTrait;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;

/**
 * Class CreateMigrationCommand
 *
 * @package     Extensions\Console\Create
 */
class CreateMigrationCommand extends MigrateMakeCommand
{
    use ExtensionCommandTrait;

    protected $name = 'extensions:create:migration';

    protected $migrationPath;

    public function getMigrationPath()
    {
        return $this->migrationPath;
    }

    /**
     * Sets the value of migrationPath
     *
     * @param mixed $migrationPath
     * @return mixed
     */
    public function setMigrationPath($migrationPath)
    {
        $this->migrationPath = $migrationPath;

        return $this;
    }


}
