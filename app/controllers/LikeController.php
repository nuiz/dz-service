<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 2/9/2556
 * Time: 13:20 น.
 * To change this template use File | Settings | File Templates.
 */

class LikeController extends BaseController implements ResourceInterface {
    public function _rules()
    {
        return array();
    }

    public function index($object_id)
    {
        try {
            $response = array();
            DB::transaction(function() use($object_id, &$response){
                $dz_object = DzObject::findOrFail($object_id);

                $likes = Like::findOrFail($object_id);

                if (is_null($likes)) {
                    $likes = new Like();
                    $likes->id = $object_id;
                    $likes->length = Like::where('object_id', '=', $object_id)->count();
                    $likes->save();
                }
                $users_likes = UserLike::where('object_id', '=', $object_id)->get();
                $response = $likes->attributesToArray();
                $response['data'] = $users_likes->toArray();

                $user = Auth::getUser();
                if(!is_null($user))
                    $response['is_like'] = Like::where('object_id', '=', $object_id)->where('user_id', '=', $user->id)->count() > 0;
            });

            return Response::json($response);
        } catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($object_id)
    {
        try {
            $response = null;
            DB::transaction(function() use ($object_id, &$response){
                $user = Auth::getUser();
                if(is_null($user)){
                    throw new Exception('this action is required authenticate');
                }

                $user_like = new UserLike();
                $user_like->object_id = $object_id;

                $likes = Like::findOrFail($object_id);
                if(is_null($likes)){
                    $likes = new UserLike();
                    $likes->id = $object_id;
                    $likes->save();
                }
                $user_like->save();

                $likes->length = UserLike::where('object_id', '=', $object_id)->count();
                $likes->save();

                $response = $likes->toArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function unlike($object_id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $object_id){
                $user = Auth::getUser();
                if(is_null($user)){
                    throw new Exception('this action is required authenticate');
                }

                $like = Like::findOrFail($object_id);

                UserLike::where('object_id', '=', $object_id)->where('user_id', '=', $user->id)->delete();

                $like->length = UserLike::where('object_id', '=', $object_id)->count();
                $like->save();

                $res = $like->toArray();
            });
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}