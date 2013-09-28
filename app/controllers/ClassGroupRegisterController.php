<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 28/9/2556
 * Time: 10:45 น.
 * To change this template use File | Settings | File Templates.
 */

class ClassGroupRegisterController extends BaseController {
    public function store($class_id, $group_id)
    {
        try {
            if(is_null(Auth::getUser())){
                throw new Exception('this action is required authenticate');
            }
            $res = array();
            DB::transaction(function() use(&$res, $class_id, $group_id){
                $group = Group::findOrFail($group_id);
                // $register = Register::where();
                if(Input::has('email')){

                }
            });
        }
        catch (Exception $e) {

        }
    }
}