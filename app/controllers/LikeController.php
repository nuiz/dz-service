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
                try {
                    $likes = Comment::findOrFail($object_id);
                }
                catch (Exception $e) {
                    $likes = new Comment();
                    $likes->id = $object_id;
                    $likes->length = Like::where('object_id', '=', $object_id)->count();
                    $likes->save();
                }
                $users_likes = UserLike::where('object_id', '=', $object_id)->get();
                $response = $likes->attributesToArray();
                $response['data'] = $users_likes->all();
                //$response['data'] = $users_comments->toArray();
            });

            //print_r($response);
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
                $user_like = new UserComment();
                $user_like->object_id = $object_id;

                try {
                    $comment = Comment::findOrFail($object_id);
                }
                catch (Exception $e) {
                    $comment = new Comment();
                    $comment->id = $object_id;
                    $comment->save();
                }
                $user_comment->save();

                $comment->length++;
                $comment->save();

                $response = $user_comment->attributesToArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}