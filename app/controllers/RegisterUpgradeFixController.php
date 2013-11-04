<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 17/10/2556
 * Time: 5:29 น.
 * To change this template use File | Settings | File Templates.
 */

class RegisterUpgradeFixController extends BaseController {
    public function index(){
        $nf = RegisterUpgrade::orderBy("created_at", "desc")->get();
        $not_read = $nf->filter(function($item){
            if($item->admin_read==0){
                return true;
            }
        })->count();
        $data = array();
        if($nf->count() > 0){
            $users_id = $nf->lists("user_id");
            $users = User::whereIn("id", $users_id)->get();

            $data = $nf->toArray();
            foreach($data as $key => $value){
                $buffer = $users->filter(function($item) use($value){
                    if($value['user_id']==$item->id)
                        return true;
                });
                $data[$key]['user'] = $buffer->first()->toArray();
            }
        }
        return Response::json(array(
            "length"=> count($data),
            "data"=> $data,
            "not_read"=> $not_read
        ));
    }

    public function show($id){
        try {
            $item = RegisterUpgrade::findOrFail($id);
            return Response::json($item);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function update($id)
    {
        try {
            $reg = RegisterUpgrade::findOrFail($id);
            if(Input::has("called")){
                $reg->called = Input::get("called");
            }
            $reg->save();
            return $reg->toArray();
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store(){
        try {
            $validator = Validator::make(Input::all(), array(
                "email"=> array("required"),
                "phone_number"=> array("required"),
                "name"=> array("required")
            ));
            if($validator->fails()){
                throw new Exception($validator->errors()->first());
            }
            $user = Auth::getUser();
            if(is_null($user)){
                throw new Exception("this action is required auth");
            }
            if(RegisterUpgrade::where("user_id", "=", $user->id)->count() > 0){
                throw new Exception("this user is registered");
            }
            $res = array();
            DB::transaction(function() use(&$res, $user){
                $regUpgrade = new RegisterUpgrade();
                $regUpgrade->user_id = $user->id;
                $regUpgrade->email = Input::get("email");
                $regUpgrade->phone_number = Input::get("phone_number");
                $regUpgrade->name = Input::get("name");

                $regUpgrade->save();
                $res = $regUpgrade->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function readAll()
    {
        try {
            RegisterUpgrade::where("admin_read", "=", 0)->update(array("admin_read"=> 1));
            return Response::json(array("success"=> true));
        }
        catch (Exception $e) {
            return Response::json($e);
        }
    }
}