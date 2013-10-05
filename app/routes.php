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

App::error(function(Exception $exception){
    return Response::exception($exception);
});

App::error(function(AuthTokenNotAuthorizedException $exception) {
    return Response::exception($exception);
});

Route::resource('test', 'TestController');

Route::get('auth', 'AuthTokenController@index');
//Route::post('auth', 'AuthTokenController@store');
Route::post('auth', function(){
    $user = User::where('username', '=', Input::get('username'))->get();
    $token = AuthToken::attempt(array('username'=> input::get('username'), 'password'=> Input::get('password')));

    if(!$token)
        $token = AuthToken::attempt(array('email'=> input::get('username'), 'password'=> Input::get('password')));

    if(!$token)
        throw new \Tappleby\AuthToken\Exceptions\NotAuthorizedException();

    $serializedToken = AuthToken::publicToken($token);
    $user = AuthToken::user($token);

    if(Input::has("deviceToken")){
        $user->ios_device_token = str_replace(array("<",">"," "), array("", "", ""), Input::get("deviceToken"));
        $user->save();
    }

    return Response::json(array('token' => $serializedToken, 'user' => $user->toArray()));
});
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
Route::resource('class.group.register', 'ClassesGroupRegisterController');

Route::resource('news', 'NewsController');
Route::post('news/{news_id}/editMedia', 'NewsController@editMedia');

Route::resource('lesson', 'LessonController');
Route::resource('lesson.chapter', 'LessonChapterController');
Route::post('lesson/{lesson_id}/chapter/{chapter_id}/editPicture', 'LessonChapterController@editPicture');
Route::resource('lesson.chapter.video', 'LessonChapterVideoController');
Route::post('lesson/{lesson_id}/chapter/{chapter_id}/video/{video_id}/editVideo', 'LessonChapterVideoController@editVideo');

Route::resource('activity', 'ActivityController');
Route::post('activity/{activity_id}/editPicture', 'ActivityController@editPicture');
Route::resource('activity.user', 'ActivityUserController');
Route::delete('activity/{id}/user', 'ActivityUserController@delete');

Route::resource('dz_object.comment', 'CommentController');
Route::resource('dz_object.like', 'LikeController');
Route::delete('dz_object/{dz_object}/like', 'LikeController@delete');

Route::controller('facebook', 'FacebookController');