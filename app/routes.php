<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
	return View::make('hello');
});

Route::get('auth', 'AuthTokenController@index');
Route::post('auth', 'AuthTokenController@store');
Route::delete('auth', 'AuthTokenController@destroy');

Route::post('/register', 'UserController@postRegister');
Route::controller('/authenticate', 'AuthenticateController');

Route::resource('user', 'UserController');
Route::resource('user.setting', 'UserSettingController');
Route::resource('user.change_password', 'UserChangePasswordController');

Route::resource('showcase', 'ShowcaseController');