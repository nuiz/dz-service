<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 28/9/2556
 * Time: 10:45 น.
 * To change this template use File | Settings | File Templates.
 */

class ClassesGroupRegisterController extends BaseController {
    public function store($class_id, $group_id)
    {
        try {
            if(is_null(Auth::getUser())){
                throw new Exception('this action is required authenticate');
            }
            $res = array();
            DB::transaction(function() use(&$res, $class_id, $group_id){
                $group = Group::findOrFail($group_id);

                $validator = Validator::make(Input::all(), array(
                    'email'=> array('required'),
                    'phone_number'=> array('required'),
                    'name'=> array('required')
                ));
                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }

                $registers_groups = RegisterGroup::where("user_id", "=", Auth::getUser()->id)->
                    where("group_id", "=", $group_id)->get();
                if($registers_groups->count() == 0){
                    $register_group = new RegisterGroup();
                    $register_group->user_id = Auth::getUser()->id;
                    $register_group->group_id = $group_id;
                }
                else {
                    $register_group = $registers_groups->first();
                }

                $register_group->email = Input::get('email');
                $register_group->phone_number = Input::get('phone_number');
                $register_group->name = Input::get('name');
                $register_group->save();

                $group->register_length = RegisterGroup::where("group_id", "=", $group_id)->count();
                $group->save();

                $res = $register_group->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}