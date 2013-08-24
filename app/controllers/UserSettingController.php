<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 23/8/2556
 * Time: 12:41 น.
 * To change this template use File | Settings | File Templates.
 */

class UserSettingController extends Controller {
    public function index($user_id)
    {
        $setting = UserSetting::find($user_id);
        return Response::json($setting);
    }

    public function store($user_id){
        try {
            $setting = UserSetting::find($user_id);
            if(Input::has('new_update'))
                $setting->new_update = Input::get('new_update');
            if(Input::has('new_showcase'))
                $setting->new_showcase = Input::get('new_showcase');
            if(Input::has('new_lesson'))
                $setting->new_lesson = Input::get('new_lesson');
            if(Input::has('news_from_dancezone'))
                $setting->news_from_dancezone = Input::has('news_from_dancezone');

            if(!$setting->save())
                throw new Exception('setting update error');

            return Response::json($setting);
        } catch (Exception $e) {
            return Response::exception($e);
        }

    }
}