<?php
 /**
 * Part of the Laradic packages.
 * MIT License and copyright information bundled with this package in the LICENSE file.
 * @author      Robin Radic
 * @license     MIT
 * @copyright   2011-2015, Robin Radic
 * @link        http://radic.mit-license.org
 */
use Illuminate\Database\Seeder;
use Laradic\Support\Str;

/**
 * Class StubSeeder
 *
 * @package     ${NAMESPACE}
 */
class StubSeeder extends Seeder
{
    public function run()
    {
        for($i = 0; $i < 20; $i++)
        {
            DB::table('extension_{vendor}_{werkbon}')->insert([
                'random' => Str::random()
            ]);
        }
    }
}
