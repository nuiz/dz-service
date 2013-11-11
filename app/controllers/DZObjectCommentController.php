<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 2/9/2556
 * Time: 12:01 น.
 * To change this template use File | Settings | File Templates.
 */

class DZObjectCommentController extends BaseController implements ResourceInterface {
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
                $comments = Comment::findOrFail($object_id);

                $limit = null;
                if(isset($_GET['limit'])){
                    $limit = $_GET['limit'];
                }

                $paging = UserComment::where('object_id', '=', $object_id)->orderBy("created_at", "desc")->paginate($limit);
                $users_comments = $paging->getCollection();
                $users_id = $users_comments->lists('user_id');
                if(count($users_id)>0){
                    $users = User::whereIn('id', $users_id)->get();
                }
                $res = $comments->toArray();
                $data = $users_comments->toArray();
                if(count($users_id)>0){
                    foreach($data as $key => $value){
                        if($users->count() > 0){
                            $users_filter = $users->filter(function($item) use($value){
                                if($item->id==$value['user_id']){
                                    return true;
                                }
                            });
                            $data[$key]['from'] = $users_filter->first()->toArray();
                            $created = new DateTime($value['created_at']);
                            $data[$key]['created_text'] = $created->format("F d");
                        }
                    }
                }

                $res = array(
                    'length'=> $paging->getTotal(),
                    'data'=> $data,
                    'paging'=> array(
                        'length'=> $paging->getLastPage(),
                        'current'=> $paging->getCurrentPage(),
                        'limit'=> $paging->getPerPage()
                    ),
                );
                if($paging->getCurrentPage() < $paging->getLastPage()){
                    $query_string = http_build_query(array_merge($_GET, array(
                        "page"=> $paging->getCurrentPage()+1,
                        "limit"=> $paging->getPerPage()
                    )));
                    $res['paging']['next'] = sprintf("%s?%s", URL::to("dz_object/{$object_id}/comment"), $query_string);
                }
                if($paging->getCurrentPage() > 1){
                    $query_string = http_build_query(array_merge($_GET, array(
                        "page"=> $paging->getCurrentPage()-1,
                        "limit"=> $paging->getPerPage()
                    )));
                    $res['paging']['previous'] = sprintf("%s?%s", URL::to("dz_object/{$object_id}/comment"), $query_string);
                }
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

                $user_comment = UserComment::find($user_comment->id);
                $res = $user_comment->toArray();
                $created = new DateTime($res['created_at']);
                $res['created_text'] = $created->format("F d");
                $res['from'] = $user->toArray();
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