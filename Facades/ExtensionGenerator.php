<?php
/**
 * Part of the Laradic packages.
 */
namespace Laradic\Extensions\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class ExtensionGenerator
 *
 * @package     ${NAMESPACE}
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
class ExtensionGenerator extends Facade
{
    /**
     * {@inheritDoc}
     */
    public static function getFacadeAccessor()
    {
        return 'extensions.generator';
    }
}
