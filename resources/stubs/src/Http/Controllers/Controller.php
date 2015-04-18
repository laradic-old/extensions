<?php namespace {namespace}\Http\Controllers;

use Illuminate\Routing\Controller;

class {packageName}Controller extends Controller
{
    /**
     * Show the application dashboard to the user.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('{vendor}/{package}::index');
    }
}
