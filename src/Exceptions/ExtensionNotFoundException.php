<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions\Exceptions;
use Exception;
use FileNotFoundException;
/**
 * This is the ExtensionNotFoundException.
 *
 * @package        Laradic\Extensions
 * @version        1.0.0
 * @author         Robin Radic
 * @license        MIT License
 * @copyright      2015, Robin Radic
 * @link           https://github.com/robinradic
 */
class ExtensionNotFoundException extends FileNotFoundException
{
    public function __construct($slug, $code = 0, Exception $previous = null)
    {
        parent::__construct("Could not find extension [$slug].", $code, $previous);
    }
}
