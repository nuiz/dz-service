<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 7/8/2556
 * Time: 15:26 น.
 * To change this template use File | Settings | File Templates.
 */

use Extend\Laravel;

class UserController extends BaseController {

    public function index($id=null)
    {
        return Response::json(User::find(1));
    }

    public function show($id){
        try {
            $user = User::findOrFail($id);
            return Response::json($user);
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return Response::exception($e);
        }
    }

    //admin only can store user
    public function store(){
    }

    //admin only can update 'type' field
    //user update 'type' response exception
    public function update($id){
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

                $token = new AccessToken();
                $token->user_id = $user->id;
                $token->access_token = Helper::genToken();
                $token->expire = date('Y-m-d H:i:s', time()+(60*60*2));
                $token->save();

                $data = $user->getAttributes();
                $data['access_token'] = $token->getAttributes();
            });
            return Response::json($data);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}
