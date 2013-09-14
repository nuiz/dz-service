<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 4/9/2556
 * Time: 12:26 น.
 * To change this template use File | Settings | File Templates.
 */

class GroupUserController extends BaseController implements ResourceInterface {
    public function _rules()
    {
        return array(
            'group.user'=> array(
                'post'=> array('admin')
            )
        );
    }

    public function index($group_id)
    {
        try {
            $buffer = UserGroup::where('group_id', '=', $group_id)->with('user')->get();
            $response = array('data'=> array());
            foreach($buffer as $key => $value) {
                $response['data'][] = $value->user->toArray();
            }
            $response['length'] = count($response['data']);
            return Response::json($response);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($group_id)
    {
        try {
            $this->_validate_permission('group.user', 'post');

            $validator = Validator::make(Input::all(), array(
                'user_id'=> array('required')
            ));
            if($validator->fails())
                throw new Exception($validator->errors());

            if(!Group::find($group_id))
                throw new Exception("Group id {$group_id} not exist");

            if(!User::find(Input::get('user_id')))
                throw new Exception("User id ".Input::get('user_id')." not exist");

            if(UserGroup::where('user_id','=',Input::get('user_id'))->where('group_id', '=', $group_id)->count() > 0){
                throw new Exception("User id ".Input::get('user_id')." is joined group");
            }

            $userGroup = new UserGroup();
            $userGroup->group_id = $group_id;
            $userGroup->user_id = Input::get('user_id');

            $userGroup->save();
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function delete($group_id, $user_id)
    {
        try {
            DB::transaction(function() use($group_id, $user_id){
                $group = Group::find($group_id);
                $userGroup = UserGroup::where('group_id','=',$group_id)->where('user_id','=',$user_id);
                $userGroup->delete();
                $group->length--;
                $group->save();
            });
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}