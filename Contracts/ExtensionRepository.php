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
namespace Laradic\Extensions\Contracts;

/**
 * Class ExtensionRepository
 *
 * @package     Extensions\Contracts
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
interface ExtensionRepository
{

    /**
     * Get extension by usint the slug
     *
     * @param $slug
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function getBySlug($slug);

    /**
     * Get installed extensions
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getInstalled();

    /**
     * Get uninstalled extensions
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getUninstalled();

    /**
     * create
     *
     * @param $slug
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create($slug);

    /**
     * install
     *
     * @param $slug
     * @return mixed
     */
    public function install($slug);

    /**
     * uninstall
     *
     * @param $slug
     * @return mixed
     */
    public function uninstall($slug);
}
