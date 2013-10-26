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
        $not = false;
        if(Input::has('import') && Input::get('import')=='false')
            $not = true;

        $usersGroups = UserGroup::where('group_id', '=', $group_id)->get();
        $users_id = $usersGroups->lists('user_id');

        $users = array();
        if(count($users_id)>0)
            if(!$not) $users = User::whereNotIn('id', $users_id, 'and')->get()->toArray();
            else $users = User::whereIn('id', $users_id, 'and')->get()->toArray();
        else
            if(!$not) $users = User::all()->toArray();

        foreach($users as $key => $value){
            if($value['type']=="admin"){
                unset($users[$key]);
            }
        }
        return Response::json(array(
            'length'=> count($users),
            'data'=> $users
        ));
    }

    /*
    public function show($class_id, $group_id, $id)
    {
        try {
            $item = Activity::findOrFail($id);
            return Response::json($item);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
    */

    public function store($class_id, $group_id)
    {
        try {
            $response = array();
            DB::transaction(function() use ($class_id, $group_id, &$response){
                $validator = Validator::make(Input::all(), array(
                    'user_id'=> array('required')
                ));
                if($validator->fails()){
                    throw new Exception($validator->errors());
                }
                $group = Group::findOrFail($group_id);
                $user_group = UserGroup::where('group_id', '=', $group_id)->where('user_id', '=', Input::get('user_id'))->get();
                $joined = $user_group->count() > 0?
                    true:
                    false;

                if(!$joined){
                    $user_group = new UserGroup();
                    $user_group->user_id = Input::get('user_id');
                    $user_group->group_id = $group_id;
                    $user_group->save();

                    $group->user_length = UserGroup::where('group_id', '=', $group_id)->count();
                    $group->save();
                }

                $response = $group->toArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($class_id, $group_id, $user_id)
    {
        try {
            $response = array();
            DB::transaction(function() use ($class_id, $group_id, $user_id, &$response){
                $group = Group::findOrFail($group_id);
                $user_group = UserGroup::where('user_id', '=', $user_id)->where('group_id', '=', $group_id)->get();
                $user_group = $user_group->first();
                $user_group->delete();

                $group->user_length = UserGroup::where('group_id', '=', $group_id)->count();
                $group->save();

                $response = $group->toArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}