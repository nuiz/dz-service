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
            $response = array();
            DB::transaction(function() use($object_id, &$response){
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
                $response = $comments->attributesToArray();
                $response['data'] = $users_comments->all();
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
                $this->_require_authenticate();
                $user = Auth::getUser();

                $user_comment = new UserComment();
                $user_comment->user_id = $user->id;
                $user_comment->object_id = $object_id;
                $user_comment->message = Input::get('message');

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

    public function destroy($id)
    {
        try {
            $this->_validate_permission('comment.destroy', 'delete');
            $comment = Comment::find($id);
            $comment->delete();
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}