<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 30/8/2556
 * Time: 10:43 น.
 * To change this template use File | Settings | File Templates.
 */

class SSLException extends Exception {}

class ShowcaseController extends BaseController implements ResourceInterface {
    public function _rules()
    {
        return array(
            'user.setting'=> array(
                'get'=> array('owner', 'admin'),
                'update'=> array('owner', 'admin'),
            ),
        );
    }

    public function index()
    {
        try {
            $showcases = Showcase::orderBy('sort_seq', 'asc')->get();
            $data = $showcases->toArray();

            foreach($data as $key => $value){
                $dateTime = new DateTime($value['created_at']);
                $data[$key]['created_text'] = $dateTime->format("j F Y");
            }
            foreach($data as $key => $value){
                if($this->_isset_field('like')){
                    $data[$key]['like'] = Like::find($value['id'])->toArray();
                    if(!is_null(Auth::getUser())){
                        $data[$key]['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $value['id'])->count() > 0;
                    }
                }
                if($this->_isset_field('comment')){
                    $data[$key]['comment'] = Comment::find($value['id'])->toArray();
                }
            }

            return Response::json(array(
                'length'=> count($data),
                'data'=> $data
            ));
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function show($id){
        try {
            $showcase = Showcase::findOrFail($id);
            $data = $showcase->toArray();
            $dateTime = new DateTime($data['created_at']);
            $data['created_text'] = $dateTime->format("j F Y");
            if($this->_isset_field('like')){
                $data['like'] = Like::find($id)->toArray();
                if(!is_null(Auth::getUser())){
                    $data['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $id)->count() > 0;
                }
            }
            if($this->_isset_field('comment')){
                $data['comment'] = Comment::find($id)->toArray();
            }

            return Response::json($data);
        }
        catch (Exception $e){
            return Response::exception($e);
        }
    }

    //admin only can store showcase
    public function store(){
        $response = array();
        $showcase = array();
        try {
            $validator = Validator::make(Input::all(), array(
                'youtube_id'=> array('required'),
                'name'=> array('required'),
                'thumb'=> array('required'),
                'duration'=> array('required'),
                'like_count'=> array('required'),
                'view_count'=> array('required'),
                'comment_count'=> array('required')
            ));
            if($validator->fails()){
                throw new Exception($validator->errors()->first());
            }
            DB::transaction(function() use (&$response, &$showcase){
                $showcase = new Showcase();
                $showcase->youtube_id = Input::get('youtube_id');

                $showcase->name = Input::get("name");
                if(Input::has('description')){
                    $showcase->description = Input::get("description");
                }
                $showcase->thumb = Input::get("thumb");
                $showcase->duration = Input::get("duration");
                $showcase->like_count = Input::get("like_count");
                $showcase->view_count = Input::get("view_count");
                $showcase->comment_count = Input::get("comment_count");
                $showcase->sort_seq = 0;

                $items = Showcase::orderBy('sort_seq', 'asc')->get();
                $i = 0;
                $items->each(function($item) use(&$i){
                    $i++;
                    $item->sort_seq = $i;
                    $item->save();
                });

                $showcase->save();

                if(Input::has("to_feed") && Input::get("to_feed")=="true"){
                    $update = new Update();
                    $update->id = $showcase->id;
                    $update->type = "showcase";
                    $update->save();
                }
                $response = $showcase->getAttributes();
            });
            $res = Response::json($response);
            $res->send();

            $users_setting = UserSetting::where("new_showcase", "=", "1")->get();
            if($users_setting->count() > 0){
                $users_id = $users_setting->lists("id");
                $users = User::whereIn("id", $users_id)->get();

                $users->each(function($user) use($response){
                    $notification = new Notification();
                    $notification->object_id = $response['id'];
                    $notification->user_id = $user->id;
                    $notification->type = "showcase";
                    $notification->message = "Update: added showcase";
                    $notification->save();
                    $nfData = array(
                        'id'=> $notification->id,
                        'object_id'=> $response['id'],
                        'type'=> "showcase"
                    );
                    if(!empty($user->ios_device_token)){
                        IOSPush::push($user->ios_device_token, "Update: added showcase", $nfData);
                    }
                });
            }
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    //admin only can update showcase
    public function update($id){
        $response = array();
        try {
            DB::transaction(function() use($id, &$response){
                $showcase = Showcase::findOrFail($id);
                if(Input::has('youtube_id'))
                    $showcase->youtube_id = Input::get('youtube_id');
                $showcase->save();

                $response = $showcase->toArray();
            });
            Response::json($response);
        } catch(Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function() use($id){
                $update = Update::find($id);
                if(!is_null($update)){
                    $update->delete();
                }
                $showcase = Showcase::find($id);
                $showcase->delete();
            });
        } catch(Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function postSort()
    {
        try {
            DB::transaction(function(){
                $sortData = Input::get("sortData");
                foreach ($sortData as $key => $value){
                    $item = Showcase::findOrFail($value);
                    $item->sort_seq = $key;
                    $item->save();
                }
            });
            return Response::json(array('sort'=> Input::get('sortData')));
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}