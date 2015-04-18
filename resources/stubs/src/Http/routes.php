<?php
Route::group(['prefix' => '{package}'], function ()
{
    Route::get('/', ['as' => '{vendor}.{package}.index', 'uses' => '{packageName}Controller@index']);
});
