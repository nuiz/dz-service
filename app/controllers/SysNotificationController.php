<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 11/10/2556
 * Time: 10:43 น.
 * To change this template use File | Settings | File Templates.
 */

class SysNotificationController extends BaseController {
    public function index()
    {
        $type = "all";
        if(isset($_GET['type'])){
            $type = $_GET['type'];
        }
        $limit = Input::has("limit")? Input::get("limit"): null;
        $page = Input::has("page")? Input::get("page"): 0;
        $paging = SysNotification::orderBy('created_at', 'desc')
            ->paginate($limit);

        $collection = $paging->getCollection();
        $data = array();

        $classes_id = array();
        $groups_id = array();
        $collection->each(function($item) use(&$classes_id, &$groups_id){
            if($item->object_type=="class"){
                $classes_id[] = $item->object_id;
            }
            else if($item->object_type=="group"){
                $groups_id[] = $item->object_id;
            }
        });

        if(count($classes_id)>0){
            $classes = Classes::whereIn("id", $classes_id)->get();
        }
        if(count($groups_id)>0){
            $groups = Group::whereIn("id", $groups_id)->get();
        }
        $collection->each(function($item) use(&$data){
            $buffer = $item->toArray();
        });
        return Response::json();
    }

    public function show($id)
    {
        try {
            $message = SysNotification::findOrFail($id);
            return Response::json($message->toArray());
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store()
    {
        try {
            $res = array();

            $validator = Validator::make(Input::all(), array(
                'message'=> array('required'),
                'contact'=> array('required')
            ));
            if($validator->fails()){
                throw new Exception($validator->errors()->first());
            }
            DB::transaction(function() use(&$res){
                $sysNf = new SysNotification();
                $sysNf->message = Input::get("message");
                $sysNf->save();
                $res = $sysNf->toArray();
            });

            $resp = Response::json($res);
            $resp->send();

            $users_id = array();
            $contact = Input::get("contact");

            if(isset($contact['class'])){
                $classes_id = array();
                foreach($contact['class'] as $key => $value){
                    $classes_id[] = $value;
                }
                if(count($classes_id) > 0){
                    $groups = Group::whereIn("class_id", $classes_id)->get();
                    if($groups->count() > 0){
                        $users_groups = UserGroup::whereIn("group_id", $groups->lists('id'))->get();
                        $users_id = array_merge($users_id, $users_groups->lists('user_id'));
                    }
                }
            }
            if(isset($contact['group'])){
                $groups_id = array();
                foreach($contact['group'] as $key => $value){
                    $groups_id[] = $value;
                }
                $users_groups = UserGroup::whereIn("group_id", $groups_id)->get();
                $users_id = array_merge($users_id, $users_groups->lists('user_id'));
            }
            if(isset($contact['activity'])){
                $activities_id = array();
                foreach($contact['activity'] as $key => $value){
                    $activities_id[] = $value;
                }
                $users_activities = UserActivity::whereIn("activity_id", $activities_id)->get();
                $users_id = array_merge($users_id, $users_activities->lists('user_id'));
            }
            if(isset($contact['user'])){
                $buffer = array();
                foreach($contact['user'] as $key => $value){
                    $buffer[] = $value;
                }
                $users_id = array_merge($users_id, $buffer);
            }


            if(count($users_id)> 0 || $contact == "all"){
                if($contact == "all"){
                    $users = User::all();
                }
                else {
                    $users = User::whereIn("id", $users_id)->get();//$users_setting = UserSetting::where("news_from_dancezone", "=", "1")->get();
                    $notify_text = strlen($res["message"]) > 28? iconv_substr($res['message'], 0, 25, "UTF-8")."...": $res["message"];
                }
                if($users->count() > 0){
                    /*
                    $users_id = $users_setting->lists("id");
                    $users = User::whereIn("id", $users_id)->get();
                    */
                    $users->each(function($user) use($res, $notify_text){
                        $notification = new Notification();
                        $notification->object_id = $res['id'];
                        $notification->user_id = $user->id;
                        $notification->type = "message";
                        $notification->message = $notify_text;
                        $notification->save();

                        $nfData = array(
                            'id'=> $notification->id,
                            'object_id'=> $res['id'],
                            'type'=> "message"
                        );
                        if(!empty($user->ios_device_token)){
                            IOSPush::push($user->ios_device_token, $notify_text, $nfData);
                        }
                    });
                }
            }
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}