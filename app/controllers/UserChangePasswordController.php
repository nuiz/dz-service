<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 24/8/2556
 * Time: 14:32 น.
 * To change this template use File | Settings | File Templates.
 */

class UserChangePasswordController extends Controller {
    public function store($user_id)
    {
        try {
            $validate = Validator::make(Input::all(), array(
                'old_password'=> array('required'),
                'new_password'=> array('required', 'min: 4', 'max: 16'),
            ));

            if($validate->fails())
                throw new Exception($validate->messages());

            $user = User::findOrFail($user_id);
            if(!Hash::check(Input::get('old_password'), $user->password))
                throw new Exception('invalid old password');

            $user->password = Hash::make(Input::get('new_password'));
            $user->save();
            return Response::json(true);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}