<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nuiz
 * Date: 24/9/2556
 * Time: 9:02 น.
 * To change this template use File | Settings | File Templates.
 */

class FacebookController extends BaseController {
    public function postLogin()
    {
        try {
            $data = array();

            $validator = Validator::make(Input::all(), array(
                'facebook_id'=> array('required')
            ));

            if($validator->fails()){
                throw new Exception($validator->errors()->first());
            }

            $result = User::where('facebook_id', '=', Input::get('facebook_id'))->get();
            if($result->count()==0){
                throw new Exception("not found facebook_id");
            }
            $user = $result->first();
            $authToken = AuthToken::create($user);
            $publicToken = AuthToken::publicToken($authToken);

            $data['user'] = $user->toArray();
            $data['token'] = $publicToken;

            return Response::json($data);

        } catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function postRegister()
    {
        try {
            $data = array();
            DB::transaction(function() use(&$data){
                $validator = Validator::make(Input::all(), array(
                    'facebook_id'=> array('required'),
                    'email'=> array('required'),
                    'first_name'=> array('required'),
                    'last_name'=> array('required'),
                ));

                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }

                if(User::where('facebook_id', '=', Input::get('facebook_id'))->count() > 0){
                    throw new Exception('facebook id duplicate');
                }

                if(User::where('email', '=', Input::get('email'))->count() > 0){
                    throw new Exception('email duplicate');
                }

                $user = new User();
                $user->facebook_id = Input::get('facebook_id');
                $user->email = Input::get('email');
                $user->first_name = Input::get('first_name');
                $user->last_name = Input::get('last_name');
                $user->type = 'normal';

                if(Input::has('phone_number')){
                    $user->phone_number = Input::get('phone_number');
                }
                $user->save();

                $authToken = AuthToken::create($user);
                $publicToken = AuthToken::publicToken($authToken);

                $data['user'] = $user->toArray();
                $data['token'] = $publicToken;
            });
            return Response::json($data);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}