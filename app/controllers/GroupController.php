<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 20/8/2556
 * Time: 14:04 น.
 * To change this template use File | Settings | File Templates.
 */

class GroupController extends BaseController {
    public function __rules()
    {
        return array(
            'group'=> array(
                'post'=> array('admin'),
                'update'=> array('admin'),
                'delete'=> array('admin')
            )
        );
    }

    public function show($group_id)
    {
        try {
            $group = Group::findOrFail($group_id);
            $response = $group->toArray();

            if($this->_isset_field('users')){
                $users = UserGroup::where('group_id', '=', $group_id)->with('user')->get();
                $response['users'] = array('data'=> array(), 'length'=> 0);
                $response['users']['data'] = $users->toArray();
                $response['users']['length'] = count($response['users']['data']);
            }

            return Response::json($response);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store()
    {
        try {
            $response = array();
            DB::transaction(function() use(&$response){
                $validator = Validator::make(Input::all(), array(
                    'class_id'=> array('required'),
                    'name'=> array('required'),
                    'description'=> array('required'),
                ));
                if($validator->fails())
                    throw new Exception($validator->errors());

                $group = new Group();
                $group->name = Input::get('name');
                $group->description = Input::get('description');
                $group->class_id = Input::get('class_id');


                $group->save();
                $response = $group->toArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function delete($group_id)
    {
        try {
            DB::transaction(function() use($group_id){
                $group = Group::find($group_id);
                $group->delete();

                Group::where('group_id', '=', $group_id)->delete();
            });
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}

/*

$group = Group::findOrFail($group_id);
$user = User::find(Input::get('user_id'));
                $userGroup = new UserGroup();
                $userGroup->user_id;


                    'user_id'=> array('required'),