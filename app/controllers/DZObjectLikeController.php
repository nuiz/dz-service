<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 21/9/2556
 * Time: 16:40 น.
 * To change this template use File | Settings | File Templates.
 */

class DZObjectLikeController extends BaseController {
    public function index($object_id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $object_id){
                $object = DzObject::findOrFail($object_id);
                $objectLike = $this->getLikeObject($object_id);
                $users_likes = UserLike::where('object_id', '=', $object_id);

                $data = array();
                if($users_likes->count()>0){
                    $users = User::whereIn('id', $users_likes->lists('user_id'))->get();
                    $data = $users->toArray();
                }
                $res = array(
                    'length'=> count($data),
                    'data'=> $data,
                    'is_liked'=> false
                );
                if(Auth::getUser()){
                    $c = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $object_id)->count();
                    if($c > 0){
                        $res['is_liked'] = true;
                    }
                }
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function store($object_id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $object_id){
                $user = Auth::getUser();
                $object = DzObject::findOrFail($object_id);
                if(!$user){
                    throw new Exception('this action require authenticate');
                }
                $c = UserLike::where('user_id', '=', $user->id)->where('object_id', '=', $object_id)->count();
                if($c == 0){
                    $likeObject = $this->getLikeObject($object_id);
                    $userLike = new UserLike();
                    $userLike->user_id = $user->id;
                    $userLike->object_id = $object_id;
                    $userLike->save();

                    $likeObject->length = UserLike::where('object_id', '=', $object_id)->count();
                    $likeObject->save();

                    $res = $likeObject->toArray();
                }
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function delete($object_id, $user_like_id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $object_id){
                $user = Auth::getUser();
                $object = DzObject::findOrFail($object_id);
                if(!$user){
                    throw new Exception('this action require authenticate');
                }
                $c = UserLike::where('user_id', '=', $user->id)->where('object_id', '=', $object_id)->count();
                if($c == 0){
                    $likeObject = $this->getLikeObject($object_id);
                    $userLike = new UserLike();
                    $userLike->user_id = $user->id;
                    $userLike->object_id = $object_id;
                    $userLike->save();

                    $likeObject->length = UserLike::where('object_id', '=', $object_id)->count();
                    $likeObject->save();

                    $res = $likeObject->toArray();
                }
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    protected function getLikeObject($object_id)
    {
        $likeObject = Like::find($object_id);
        if($likeObject==null){
            $likeObject = new Like();
            $likeObject->id = $object_id;
            $likeObject->save();
        }
        return $likeObject;
    }
}