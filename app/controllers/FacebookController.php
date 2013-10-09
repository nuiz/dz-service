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

            DB::transaction(function() use(&$data){

                $validator = Validator::make(Input::all(), array(
                    'facebook_id'=> array('required')
                ));

                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }

                $result = User::where('facebook_id', '=', Input::get('facebook_id'))->get();
                if($result->count()==0){
                    $user = array();
                    try {
                        DB::transaction(function() use(&$user){
                            $validator = Validator::make(Input::all(), array(
                                'facebook_id'=> array('required'),
                                'email'=> array('required')
                            ));

                            if($validator->fails()){
                                throw new Exception($validator->errors()->first());
                            }
                            $buffer = User::where('email', '=', Input::get('email'))->get();
                            if($buffer->count() == 0)
                                $user = new User();
                            else
                                $user = $buffer->first();

                            $user->facebook_id = Input::get('facebook_id');
                            $user->username = Input::get('username');
                            if(Input::has('first_name'))
                                $user->first_name = Input::get('first_name');
                            if(Input::has('last_name'))
                                $user->last_name = Input::get('last_name');
                            if(Input::has('birth_date'))
                                $user->birth_date = Input::get('birth_date');

                            if(Input::has('phone_number')){
                                $user->phone_number = Input::get('phone_number');
                                $user->phone_show = Input::get('phone_show');
                            }

                            $user->email = Input::get('email');
                            $user->email_show = Input::get('email');

                            $user->type = 'normal';
                            $user->save();
                        });
                    }
                    catch (Exception $e){
                        DB::rollBack();
                        throw $e;
                    }
                }
                else {
                    $user = $result->first();
                }
                if(Input::has("deviceToken")){
                    $user->ios_device_token = str_replace(array("<",">"," "), array("", "", ""), Input::get("deviceToken"));
                    $user->save();
                }

                $authToken = AuthToken::create($user);
                $publicToken = AuthToken::publicToken($authToken);

                $data['user'] = $user->toArray();
                $data['token'] = $publicToken;

                Log::info("user id {$data['user']['id']} token: {$publicToken}");
            });
            return Response::json($data);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}