<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 26/9/2556
 * Time: 15:58 น.
 * To change this template use File | Settings | File Templates.
 */

class ActivityUserController extends BaseController {
    public function index($activity_id)
    {
        try {
            $users_activities = UserActivity::where("activity_id", "=", $activity_id)->get();
            $users_id = $users_activities->lists('user_id');
            $res = array();
            $data = array();
            if(count($users_id)>0){
                $users = User::whereIn('id', $users_id);
                if($users->count() > 0){
                    $data = $users->get()->toArray();
                }
            }
            $res['length'] = count($data);
            $res['data'] = $data;
            if(!is_null(Auth::getUser())){
                $res['is_joined'] = $users_activities->filter(function($item){
                        if($item->user_id == Auth::getUser()->id)
                            return true;
                    })->count() > 0;
            }
            return Response::json($res);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($activity_id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $activity_id){
                $user = Auth::getUser();
                if(!$user){
                    throw new \Tappleby\AuthToken\Exceptions\NotAuthorizedException();
                }

                $activity = Activity::findOrFail($activity_id);
                if(UserActivity::where("user_id", "=", Auth::getUser()->id)
                    ->where("activity_id", "=", $activity_id)->count() == 0){
                    $user_activity = new UserActivity();
                    $user_activity->user_id = $user->id;
                    $user_activity->activity_id = $activity_id;
                    $user_activity->save();
                }

                $activity->user_length = UserActivity::where("activity_id", "=", $activity_id)->count();
                $activity->save();

                $res = $activity->toArray();

                if(!is_null(Auth::getUser())){
                    $res['is_joined'] = UserActivity::where("user_id", "=", Auth::getUser()->id)
                        ->where("activity_id", "=", $activity_id)->count() > 0;
                }
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function delete($activity_id)
    {
        try {
            $user = Auth::getUser();
            if(is_null($user)){
                throw new Exception("this actoin is required authenticate");
            }
            $res = array();
            DB::transaction(function() use(&$res, $activity_id, $user){
                $activity = Activity::findOrFail($activity_id);
                $buffer = UserActivity::where("user_id", "=", $user->id)->where("activity_id", "=", $activity_id)->get();
                if($buffer->count() > 0){
                    $user_activity = $buffer->first();
                    $user_activity->delete();
                }

                $activity->user_length = UserActivity::where("activity_id", "=", $activity_id)->count();
                $activity->save();

                $res = $activity->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}