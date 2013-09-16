<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 16/9/2556
 * Time: 15:45 น.
 * To change this template use File | Settings | File Templates.
 */

class ClassesGroupUserController extends BaseController {
    public function index($class_id, $group_id)
    {
        $usersGroups = UserGroup::where('group_id', '=', $group_id)->get();
        $arr_id = $usersGroups->fetch('user_id');

        $users = array();
        if($arr_id->count()>0)
            $users = User::whereIn('id', $arr_id->toArray())->get()->toArray();
        else

        return Response::json(array(
            'length'=> count($users),
            'data'=> $users
        ));
    }
}