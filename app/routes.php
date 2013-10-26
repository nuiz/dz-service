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

Route::get('/test_nf', function(){
    $data = array(
        "id"=> 1,
        "object_id"=> 10,
        "user_id"=> 20,
        "message"=> "message test na ja",
        "created_at"=> "2013-02-02",
        "updated_at"=> "2013-02-02"
    );
    switch(Input::get('type')) {
        case 'message':
            $data['type'] = "message";
            break;
        case 'news':
            $data['type'] = "news";
            break;
        case 'showcase':
            $data['type'] = "showcase";
            break;
        case 'lesson':
            $data['type'] = "lesson";
            break;
        case 'activity':
            $data['type'] = "activity";
            break;
        default:
            $data['type'] = "message";
            break;
    }
    $res = Response::json($data);
    $res->send();

    $user = User::find(5);
    IOSPush::push($user->ios_device_token, "Update: added activity", $data);
});

Route::get("count_notification", function(){
    $r_upgrade = RegisterUpgrade::where("admin_read", "=", 0)->count();
    $r_group = RegisterGroup::where("admin_read", "=", 0)->count();
    $u_activity = UserActivity::where("admin_read", "=", 0)->count();
    $res = array(
        "all"=> $r_upgrade+$r_group+$u_activity,
        "register_upgrade"=> $r_upgrade,
        "register_group"=> $r_group,
        "user_activity"=> $u_activity
    );
    return Response::json($res);
});

Route::delete('auth', 'AuthTokenController@destroy');

Route::post('/register', 'UserController@postRegister');
Route::controller('/authenticate', 'AuthenticateController');

Route::resource('user', 'UserController');
Route::resource('user.setting', 'UserSettingController');
Route::put('user/{user_id}/setting', 'UserSettingController@update');
Route::resource('user.change_password', 'UserChangePasswordController');
Route::resource('user.picture', 'UserPictureController');

Route::post('showcase/sort', 'ShowcaseController@postSort');
Route::resource('showcase', 'ShowcaseController');
Route::resource('showcase.comments', 'CommentController');

Route::resource('class', 'ClassesController');
Route::resource('class.group', 'ClassesGroupController');
Route::post('class/{class_id}/group/{group_id}/editVideo', 'ClassesGroupController@editVideo');
Route::resource('class.group.user', 'ClassesGroupUserController');
Route::resource('class.group.register', 'ClassesGroupRegisterController');

Route::resource('news', 'NewsController');
Route::post('news/{news_id}/editMedia', 'NewsController@editMedia');

Route::resource('lesson', 'LessonController');
Route::post('lesson/sort', 'LessonController@postSort');

Route::resource('lesson.chapter', 'LessonChapterController');
Route::post('lesson/{lesson_id}/chapter/sort', 'LessonChapterController@postSort');
Route::post('lesson/{lesson_id}/chapter/{chapter_id}/editPicture', 'LessonChapterController@editPicture');

Route::resource('lesson.chapter.video', 'LessonChapterVideoController');
Route::post('lesson/{lesson_id}/chapter/{chapter_id}/video/sort', 'LessonChapterVideoController@postSort');
Route::post('lesson/{lesson_id}/chapter/{chapter_id}/video/{video_id}/editVideo', 'LessonChapterVideoController@editVideo');

Route::resource('activity', 'ActivityController');
Route::post('activity/{activity_id}/editPicture', 'ActivityController@editPicture');
Route::resource('activity.user', 'ActivityUserController');
Route::delete('activity/{id}/user', 'ActivityUserController@delete');

Route::resource('dz_object.comment', 'CommentController');
Route::resource('dz_object.like', 'LikeController');
Route::delete('dz_object/{dz_object}/like', 'LikeController@delete');

Route::controller('facebook', 'FacebookController');

Route::resource('notification', 'NotificationController');
Route::resource('sys_notification', 'SysNotificationController');
Route::resource('admin_notification', 'AdminNotificationController');

Route::get('register_upgrade/read_all', 'RegisterUpgradeController@readAll');
Route::get('register_group/read_all', 'RegisterGroupController@readAll');
Route::get('user_activity/read_all', 'UserActivityController@readAll');

Route::resource('register_upgrade', 'RegisterUpgradeController');
Route::resource('register_group', 'RegisterGroupController');
Route::resource('user_activity', 'UserActivityController');

Route::resource('group', 'GroupController');

Route::resource('pic', 'PicController');

Route::resource('setting', 'SettingController');

Route::resource('feed', "FeedController");