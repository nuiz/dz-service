<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 2/9/2556
 * Time: 12:01 น.
 * To change this template use File | Settings | File Templates.
 */

class CommentController extends BaseController implements ResourceInterface {
    public function _rules()
    {
        return array();
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function index($object_id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $object_id, &$response){
                $dz_object = DzObject::findOrFail($object_id);
                try {
                    $comments = Comment::findOrFail($object_id);
                }
                catch (Exception $e) {
                    $comments = new Comment();
                    $comments->id = $object_id;
                    $comments->length = UserComment::where('object_id', '=', $object_id)->count();
                    $comments->save();
                }
                $users_comments = UserComment::where('object_id', '=', $object_id)->get();
                $res = $comments->toArray();
                $res['data'] = $users_comments->toArray();
            });

            return Response::json($res);
        } catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($object_id)
    {
        try {
            $res = null;
            DB::transaction(function() use ($object_id, &$res){
                //$this->_require_authenticate();
                $user = Auth::getUser();

                if(is_null($user)){
                    throw new Exception('this action require authenticate');
                }
                $user_comment = new UserComment();
                $user_comment->user_id = $user->id;
                $user_comment->object_id = $object_id;
                $user_comment->message = Input::get('message');

                $comment = Comment::find($object_id);

                if (is_null($comment)) {
                    $comment = new Comment();
                    $comment->id = $object_id;
                }
                $user_comment->save();

                $comment->length = UserComment::where('object_id', '=', $object_id)->count();
                $comment->save();

                $res = $user_comment->attributesToArray();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($object_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $object_id, $id){
                $comment = Comment::findOrFail($object_id);
                $user_comment = UserComment::findOrFail($id);

                $user_comment->delete();
                $comment->length = UserComment::where('object_id', '=', $object_id)->count();
                $comment->save();

                $res = $comment->toArray();
            });
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}