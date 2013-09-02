<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 7/8/2556
 * Time: 15:26 น.
 * To change this template use File | Settings | File Templates.
 */

use Extend\Laravel;

class UserController extends BaseController implements \Extend\Laravel\ResourceInterface {

    public function _rules()
    {
        return array(
            'user'=> array(
                'update'=> array('owner', 'admin'),
            ),
            'user.setting'=> array(
                'get'=> array('owner', 'admin'),
                'update'=> array('owner', 'admin')
            ),
            'user.type'=> array(
                'update'=> array('admin')
            )
        );
    }

    public function show($id){
        try {
            $user = User::findOrFail($id);
            $response = $user->attributesToArray();

            $fields = $this->_fields();

            //owner or admin can access
            if($this->_isset_field('setting')){
                $this->_validate_permission($id, 'user.setting', 'get');

                $user_setting = UserSetting::find($id);
                if(is_null($user_setting)){
                    $user_setting = new UserSetting();
                    $user_setting->id = $id;
                    $user_setting->save();
                    $user_setting = UserSetting::find($id);
                }
                $response['setting'] = $user_setting->attributesToArray();
            }

            return Response::json($response);
        }
        catch (Exception $e){
            return Response::exception($e);
        }
    }

    //admin only can store user
    public function store(){
        try {
            $response = null;
            DB::transaction(function() use (&$response){
                $validator = Validator::make(Input::all(), array(
                    'email'=> array('email', 'required'),
                    'password'=> array('min: 4', 'max: 16', 'required'),
                ));

                $attributes = Input::all();
                $attributes['password'] = Hash::make($attributes['password']);
                $attributes['type'] = 'normal';
                $user = new User();
                $user->setRawAttributes($attributes);
                $user->save();

                $response = $user->attributesToArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    //admin only can update 'type' field
    //user update 'type' response exception
    public function update($id){
        try {
            $response = null;
            DB::transaction(function() use ($id, &$response){
                $validator = Validator::make(Input::all(), array(
                    'type'=> 'in:normal,member'
                ));

                if($validator->fails())
                    throw new Exception($validator->errors());

                $user = User::findOrFail($id);

                $this->_validate_permission($user, 'user', 'update');
                if(Input::has('type')){
                    $this->_validate_permission($user, 'type', 'update');
                    $user->type = Input::get('type');
                }

                $attributes = Input::all();

                $user->first_name = $attributes['first_name'];
                $user->last_name = $attributes['last_name'];

                if(isset($attributes['password'])) unset($attributes['password']);

                $user->save();

                $response = $user->attributesToArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function postRegister()
    {
        try {
            $data = array();
            DB::transaction(function() use (&$data){
                $validator = Validator::make(Input::all(), array(
                    'email'=> array('email', 'required'),
                    'password'=> array('min: 4', 'max: 16', 'required'),
                ));
                if ($validator->fails())
                    throw new Exception($validator->errors());

                $email = $_POST['email'];
                $password = $_POST['password'];

                $md5_password = md5($password);
                $user = new User();
                $user->email = $email;
                $user->password = $md5_password;
                $user->type = 'normal';
                $user->save();
            });
            return Response::json($data);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}
