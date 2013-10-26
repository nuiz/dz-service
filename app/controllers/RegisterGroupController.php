<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 17/10/2556
 * Time: 10:47 น.
 * To change this template use File | Settings | File Templates.
 */

class RegisterGroupController extends BaseController {
    public function index(){
        $nf = RegisterGroup::orderBy("created_at", "desc")->get();
        $not_read = $nf->filter(function($item){
            if($item->admin_read==0){
                return true;
            }
        })->count();
        $data = array();
        if($nf->count() > 0){
            $users_id = $nf->lists("user_id");
            $groups_id = $nf->lists("group_id");
            $users = User::whereIn("id", $users_id)->get();
            $groups = Group::whereIn("id", $groups_id)->get();
            $classes_id = $groups->lists("class_id");
            $classes = Classes::whereIn("id", $classes_id)->get();

            $data = $nf->toArray();
            foreach($data as $key => $value){
                $buffer = $users->filter(function($item) use($value){
                    if($value['user_id']==$item->id)
                        return true;
                });
                $buffer2 = $groups->filter(function($item) use($value){
                    if($value['group_id']==$item->id)
                        return true;
                });
                $data[$key]['user'] = $buffer->first()->toArray();
                $data[$key]['group'] = $buffer2->first()->toArray();

                $cid = $data[$key]['group']['class_id'];
                $buffer3 = $classes->filter(function($item) use($cid){
                    if($item->id == $cid)
                        return true;
                });

                $data[$key]['group']['class'] = $buffer3->first()->toArray();
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
            $item = RegisterGroup::findOrFail($id);
            return Response::json($item);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function update($id)
    {
        try {
            $reg = RegisterGroup::findOrFail($id);
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

    public function readAll()
    {
        try {
            RegisterGroup::where("admin_read", "=", 0)->update(array("admin_read"=> 1));
            return Response::json(array("success"=> true));
        }
        catch (Exception $e) {
            return Response::json($e);
        }
    }
}