<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nuiz
 * Date: 21/9/2556
 * Time: 10:57 น.
 * To change this template use File | Settings | File Templates.
 */

class LessonChapterVideoController extends BaseController {
    public function index($lesson_id, $chapter_id)
    {
        try {
            $videos = Video::where('chapter_id', '=', $chapter_id)->orderBy('sort_seq', 'asc')->get();
            $data = $videos->toArray();
            $lesson = Lesson::findOrFail($lesson_id);

            foreach($data as $key => $value){
                $data[$key]['thumb'] = URL::to('video/'.$value['id'].'.jpeg');

                if($this->_isset_field('like')){
                    $data[$key]['like'] = Like::find($value['id'])->toArray();
                    if(is_null(Auth::getUser())){
                        $data[$key]['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $value['id'])->count() > 0;
                    }
                }
                if($this->_isset_field('comment')){
                    $data[$key]['comment'] = Comment::find($value['id'])->toArray();
                }
                $user = Auth::user();
                if((!is_null($user) && $user->type != "normal") || $value['is_public']==1){
                    $data[$key]['link'] = URL::to('video/'.$value['video_link']);
                }
                else {
                    unset($data[$key]['video_link']);
                }
                $data[$key]['color'] = $lesson->color;
            }
            return Response::json(array(
                'length'=> $videos->count(),
                'data'=> $data
            ));
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function show($lesson_id, $chapter_id, $id)
    {
        try {
            $lesson = Lesson::findOrFail($lesson_id);
            $item = Video::findOrFail($id);
            $data = $item->toArray();

            $user = Auth::user();
            if((!is_null($user) && $user->type != "normal") || $data['is_public']==1){
                $data['link'] = URL::to('video/'.$data['video_link']);
            }
            else {
                unset($data['video_link']);
            }
            $data['thumb'] = URL::to('video/'.$data['id'].'.jpeg');

            if($this->_isset_field('like')){
                $data['like'] = Like::find($id)->toArray();
                if(is_null(Auth::getUser())){
                    $data['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $id)->count() > 0;
                }
            }
            if($this->_isset_field('comment')){
                $data['comment'] = Comment::find($id)->toArray();
            }
            $data['color'] = $lesson->color;
            return Response::json($data);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($lesson_id, $chapter_id)
    {
        try {
            $res = array();
            DB::transaction(function() use (&$res, $lesson_id, $chapter_id){
                $chapter = Chapter::findOrFail($chapter_id);
                $validator = Validator::make(Input::all(), array(
                    'name'=> array('required'),
                    'description'=> array('required'),
                    'video'=> array('required'),
                    'is_public'=> array('required')
                ));
                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }

                $videoFile = Input::file('video');
                $ext = strtolower($videoFile->getClientOriginalExtension());
                $allows = array('mp4');
                if(!in_array($ext, $allows) ) {
                    throw new Exception('file upload not allowed');
                }

                $items = Video::where('chapter_id', '=', $chapter_id)->orderBy('sort_seq', 'asc')->get();
                $i = 0;
                $items->each(function($item) use(&$i){
                    $i++;
                    $item->sort_seq = $i;
                    $item->save();
                });

                $video = new Video();
                $video->chapter_id = $chapter_id;
                $video->name = Input::get('name');
                $video->description = Input::get('description');
                $video->is_public = Input::get('is_public');
                $video->sort_seq = 0;
                $video->save();

                $name = $video->id.'.'.$ext;
                $videoFile->move('video', $name);
                chmod('video/'.$name, 0777);

                $video_path = 'video/'.$name;
                $thumbnail_path = 'video/'.$video->id.'.jpeg';

                // shell command [highly simplified, please don't run it plain on your script!]
                shell_exec("ffmpeg -i {$video_path} -deinterlace -an -ss 1 -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg {$thumbnail_path} 2>&1");
                chmod($thumbnail_path, 0777);

                $video->video_link = $name;
                $video->save();

                $chapter->video_length = Video::where("chapter_id", "=", $chapter_id)->count();
                $chapter->save();

                $res = $video->toArray();
            });
            $resp = Response::json($res);
            $resp->send();

            $users_setting = UserSetting::where("new_lesson", "=", "1")->get();
            if($users_setting->count() > 0){
                $users_id = $users_setting->lists("id");
                $users = User::whereIn("id", $users_id)->get();

                $users->each(function($user) use($res){
                    $notification = new Notification();
                    $notification->object_id = $res['id'];
                    $notification->user_id = $user->id;
                    $notification->type = "video";
                    $notification->message = "Update: added video";
                    $notification->save();

                    $nfData = array(
                        'id'=> $notification->id,
                        'object_id'=> $res['id'],
                        'type'=> "video"
                    );
                    if(!empty($user->ios_device_token)){
                        IOSPush::push($user->ios_device_token, "Update: added video", $nfData);
                    }
                });
            }
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function update($lesson_id, $chapter_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $lesson_id, $chapter_id, $id){
                $item = Video::findOrFail($id);

                if(Input::has('name')){
                    $item->name = Input::get('name');
                }

                if(Input::has('is_public')){
                    $item->is_public = Input::get('is_public');
                }

                if(Input::has('description')){
                    $item->description = Input::get('description');
                }

                $item->save();
                $res = $item->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e){
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function editVideo($lesson_id, $chapter_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $lesson_id, $chapter_id, $id) {
                if(!Input::hasFile('video')){
                    throw new Exception("this action required video upload");
                }

                $item = Video::findOrFail($id);
                $videoFile = Input::file('video');
                $ext = strtolower($videoFile->getClientOriginalExtension());
                $allows = array('mp4');
                if(!in_array($ext, $allows) ) {
                    throw new Exception('file upload not allowed');
                }

                $name = $item->id.'.'.$ext;
                $video_path = 'video/'.$name;
                $thumbnail_path = 'video/'.$item->id.'.jpeg';

                /*
                if(file_exists($video_path)){
                    unlink($video_path);
                }
                */

                $videoFile->move('video', $name);
                chmod('video/'.$name, 0777);

                /*
                if(file_exists($thumbnail_path)){
                    unlink($thumbnail_path);
                }
                */

                // shell command [highly simplified, please don't run it plain on your script!]
                shell_exec("ffmpeg -i {$video_path} -deinterlace -an -ss 1 -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg {$thumbnail_path} 2>&1");
                chmod($thumbnail_path, 0777);

                $item->video_link = $name;
                $item->save();
                $res = $item->toArray();
                $res['link'] = "video/".$item->video_link;
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($lesson_id, $chapter_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $lesson_id, $chapter_id, $id){
                $chapter = Chapter::findOrFail($chapter_id);
                $video = Video::findOrFail($id);
                $video_file = $video->video_link;
                $res = $video->toArray();
                $video->delete();

                $chapter->video_length = Video::where('chapter_id', '=', $chapter_id)->count();
                $chapter->save();

                @unlink('video/'.$video_file);
                @unlink('video/'.$video->id.'.jpeg');
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function postSort($lesson_id, $chapter_id)
    {
        try {
            DB::transaction(function(){
                $sortData = Input::get("sortData");
                foreach ($sortData as $key => $value){
                    $item = Video::findOrFail($value);
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