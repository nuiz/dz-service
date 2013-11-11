<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 8/11/2556
 * Time: 12:53 น.
 * To change this template use File | Settings | File Templates.
 */

class CommentController extends BaseController {
    public function destroy($id){
        try {
            $res = array();
            DB::transaction(function() use($id, &$res){
                $user_comment = UserComment::findOrFail($id);
                $comment = Comment::findOrFail($user_comment->object_id);
                $user_comment->delete();
                $comment->length = UserComment::where("object_id", "=", $user_comment->object_id)->count();
                $comment->save();

                $res = array("success"=> true);
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}