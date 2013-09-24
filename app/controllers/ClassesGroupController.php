<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 20/8/2556
 * Time: 14:04 น.
 * To change this template use File | Settings | File Templates.
 */

class ClassesGroupController extends BaseController {
    public function _rules()
    {
        return array(
            'group'=> array(
                'post'=> array('admin'),
                'update'=> array('admin'),
                'delete'=> array('admin')
            )
        );
    }

    public function index($class_id)
    {
        $groups = Group::where('class_id', '=', $class_id)->get();
        $groupsData = $groups->toArray();

        foreach($groupsData as $key => $groupData){
            $users_groups = UserGroup::where('group_id', '=', $groupData['id'])->get();
            if($users_groups->count() > 0){
                $users_id = $users_groups->lists('id');
                $users = User::whereIn('id', $users_id)->get();

                $usersData = $users->toArray();
            }
            else {
                $usersData = array();
            }

            $groupsData[$key]['users'] = array(
                'length'=> count($usersData),
                'data'=> $usersData
            );
        }

        return Response::json(array(
            'length'=> $groups->count(),
            'data'=> $groupsData
        ));
    }

    public function show($class_id, $group_id)
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

    public function store($class_id)
    {
        try {
            $response = array();
            DB::transaction(function() use(&$response, $class_id){
                $validator = Validator::make(Input::all(), array(
                    'name'=> array('required'),
                    'description'=> array('required'),
                ));
                if($validator->fails())
                    throw new Exception($validator->messages()->first());

                $group = new Group();
                $group->name = Input::get('name');
                $group->description = Input::get('description');
                $group->class_id = $class_id;

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

    public function update($class_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $class_id, $id){
                $item = Group::findOrFail($id);

                if(Input::has('name')){
                    $item = Input::get('name');
                }

                if(Input::has('description')){
                    $item->name = Input::get('description');
                }

                $item->save();
                $res = $item->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e){
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($class_id, $group_id)
    {
        try {
            $response = array();
            DB::transaction(function() use($group_id, &$response){
                $group = Group::findOrFail($group_id);
                $response = $group->toArray();
                $group->delete();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}