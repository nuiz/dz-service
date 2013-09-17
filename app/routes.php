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

Event::listen('auth.token.valid', function($user)
{
    //Token is valid, set the user on auth system.
    Auth::setUser($user);
});

App::error(function(AuthTokenNotAuthorizedException $exception) {
    return Response::exception($exception);
});

Route::resource('test', 'TestController');

Route::get('auth', 'AuthTokenController@index');
Route::post('auth', 'AuthTokenController@store');
Route::delete('auth', 'AuthTokenController@destroy');

Route::post('/register', 'UserController@postRegister');
Route::controller('/authenticate', 'AuthenticateController');

Route::resource('user', 'UserController');
Route::resource('user.setting', 'UserSettingController');
Route::resource('user.change_password', 'UserChangePasswordController');
Route::resource('user.picture', 'UserPictureController');

Route::resource('showcase', 'ShowcaseController');
Route::resource('showcase.comments', 'CommentController');

Route::resource('class', 'ClassesController');
Route::resource('class.group', 'ClassesGroupController');
Route::resource('class.group.user', 'ClassesGroupUserController');