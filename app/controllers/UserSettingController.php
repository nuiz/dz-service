<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 23/8/2556
 * Time: 12:41 น.
 * To change this template use File | Settings | File Templates.
 */

class UserSettingController extends BaseController implements ResourceInterface {
    public function _rules()
    {
        return array(
            'user.setting'=> array(
                'get'=> array('owner', 'admin'),
                'update'=> array('owner', 'admin'),
            ),
        );
    }

    public function _validate_permission($user_id, $resource, $action)
    {
        $rules = $this->_rules();

        if(!isset($rules[$resource]))
            return true;
        if(!isset($rules[$resource][$action]))
            return true;

        $rule = $rules[$resource][$action];
        if(array_search('owner', $rule)!==false){
            if(!$this->_auth_owner($user_id))
                throw new Exception("You not have permission for this action");
        }
        if(array_search('admin', $rule)!==false){
            if(!$this->_auth_admin())
                throw new Exception("You not have permission for this action");
        }
    }

    public function index($user_id)
    {
        try {
            $user = User::findOrFail($user_id);
            $setting = UserSetting::find($user_id);
            if(is_null($setting)){
                $setting = new UserSetting();
                $setting->id = $user->id;
                $setting->save();
            }
            return Response::json($setting);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function store($user_id){
        try {
            $this->_validate_permission($user_id, 'user.setting', 'get');
            $this->_validate_permission($user_id, 'user.setting', 'update');

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