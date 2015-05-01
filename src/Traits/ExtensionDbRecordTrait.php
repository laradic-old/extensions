<?php
/**
 * Part of the Robin Radic's PHP packages.
 *
 * MIT License and copyright information bundled with this package
 * in the LICENSE file or visit http://radic.mit-license.com
 */
namespace Laradic\Extensions\Traits;

use Config;

/**
 * This is the ExtensionDbRecordTrait.
 *
 * @package        Laradic\Extensions
 * @version        1.0.0
 * @author         Robin Radic
 * @license        MIT License
 * @copyright      2015, Robin Radic
 * @link           https://github.com/robinradic
 */
trait ExtensionDbRecordTrait
{

    /**
     * query on the extension table
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function query()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = null;
        if(isset($this->resolver)){
            $connection = $this->resolver->connection();
        } elseif(isset($this->connection) and $this->connection instanceof \Illuminate\Database\Connection)  {
            $connection = $this->connection;
        }
        if(!$connection instanceof \Illuminate\Database\Connection)
        {
            throw new \Exception("query on extension dbrecord trait filaed. connection not a connection');");
        }
        return $connection->table(Config::get('laradic/extensions::table'));
    }

    public function updateRecords()
    {
        $this->records = [ ];
        foreach ( $this->query()->get() as $record )
        {
            $this->records[ $record->slug ] = (int)$record->installed;
        }
    }

    protected function getRecord($slug)
    {
        return $this->query()->where('slug', '=', $slug)->first();
    }

    protected function recordCreate($slug)
    {
        return $this->query()->insert([ 'slug' => $slug, 'installed' => 0 ]);
    }

    protected function recordInstall($slug)
    {
        $this->query()->where('slug', '=', $slug)->update([ 'installed' => 1 ]);
    }

    protected function recordUninstall($slug)
    {
        $this->query()->where('slug', '=', $slug)->update([ 'installed' => 0 ]);
    }
}
