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
namespace Laradic\Extensions\Repositories;

use ErrorException;
use Laradic\Extensions\Contracts\ExtensionRepository;
use Laradic\Support\AbstractEloquentRepository;

/**
 * Class EloquentExtensionRepository
 *
 * @package     Extensions\Repositories
 */
class EloquentExtensionRepository extends AbstractEloquentRepository implements ExtensionRepository
{

    /** @var string */
    protected $model = 'Laradic\Extensions\Models\Extension';

    public function createModel()
    {
        return parent::createModel();
    }


    /**
     * Get extension by usint the slug
     *
     * @param $slug
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public function getBySlug($slug)
    {
        return $this->getFirstBy('slug', $slug);
    }

    /**
     * Get installed extensions
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getInstalled()
    {
        return $this->getManyBy('installed', 1);
    }

    /**
     * Get uninstalled extensions
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getUninstalled()
    {
        return $this->getManyBy('installed', 0);
    }

    public function create($slug)
    {
        if ( $this->getBySlug($slug) )
        {
            throw new ErrorException("Could not create extension record, slug [${slug}] already exists");
        }

        return $this->createModel()->create(['slug' => $slug]);
    }

    public function install($slug)
    {
        if ( $model = $this->getBySlug($slug) )
        {
            $model->update(['installed' => 1]);
        }
    }

    public function uninstall($slug)
    {
        if ( $model = $this->getBySlug($slug) )
        {
            $model->update(['installed' => 0]);
        }
    }
}
