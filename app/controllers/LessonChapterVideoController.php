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
            $videos = Video::where('chapter_id', '=', $chapter_id)->get();
            $data = $videos->toArray();
            foreach($data as $key => $value){
                $data[$key]['link'] = URL::to('video/'.$value['video_link']);
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
            $item = Video::findOrFail($id);
            $data = $item->toArray();
            $data['link'] = URL::to('video/'.$data['video_link']);
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

                $video = new Video();
                $video->chapter_id = $chapter_id;
                $video->name = Input::get('name');
                $video->description = Input::get('description');
                $video->is_public = Input::get('is_public');
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
            return Response::json($res);
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
                    $item = Input::get('name');
                }


                if(Input::has('description')){
                    $item->name = Input::get('description');
                }

                if(Input::hasFile('video')){
                    $videoFile = Input::file('video');
                    $ext = strtolower($videoFile->getClientOriginalExtension());
                    $allows = array('mp4', '3gp');
                    if(!in_array($ext, $allows) ) {
                        throw new Exception('file upload not allowed');
                    }

                    $name = $item->id.'.'.$ext;
                    $videoFile->move('video', $name);
                    chmod('video/'.$name, 0777);

                    $video_path = 'video/'.$name;
                    $thumbnail_path = 'video/'.$item->id.'.jpeg';

                    // shell command [highly simplified, please don't run it plain on your script!]
                    shell_exec("ffmpeg -i {$video_path} -deinterlace -an -ss 1 -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg {$thumbnail_path} 2>&1");
                    chmod($thumbnail_path, 0777);

                    $item->video_link = $name;
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
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}