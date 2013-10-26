<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 17/10/2556
 * Time: 11:18 น.
 * To change this template use File | Settings | File Templates.
 */

class UserActivityController extends BaseController {
    public function index(){
        $nf = UserActivity::orderBy("created_at", "desc")->get();
        $not_read = $nf->filter(function($item){
            if($item->admin_read==0){
                return true;
            }
        })->count();
        $data = array();
        if($nf->count() > 0){
            $users_id = $nf->lists("user_id");
            $activities_id = $nf->lists("activity_id");
            $users = User::whereIn("id", $users_id)->get();
            $activities = Activity::whereIn("id", $activities_id)->get();

            $data = $nf->toArray();
            foreach($data as $key => $value){
                $buffer = $users->filter(function($item) use($value){
                    if($value['user_id']==$item->id)
                        return true;
                });
                $buffer2 = $activities->filter(function($item) use($value){
                    if($value['activity_id']==$item->id)
                        return true;
                });
                $data[$key]['user'] = $buffer->first()->toArray();
                $data[$key]['activity'] = $buffer2->first()->toArray();
            }
        }
        return Response::json(array(
            "length"=> count($data),
            "data"=> $data,
            "not_read"=> $not_read
        ));
    }

    public function readAll()
    {
        try {
            UserActivity::where("admin_read", "=", 0)->update(array("admin_read"=> 1));
            return Response::json(array("success"=> true));
        }
        catch (Exception $e) {
            return Response::json($e);
        }
    }
}