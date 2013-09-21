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
            $item = Activity::findOrFail($id);
            $data = $item->toArray();
            $data['link'] = URL::to('video/'.$data['video_link']);
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
                $validator = Validator::make(Input::all(), array(
                    'name'=> array('required'),
                    'description'=> array('required')
                ));
                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }

                $videoFile = Input::file('video');
                $ext = strtolower($videoFile->getClientOriginalExtension());
                $allows = array('mp4', '3gp');
                if(!in_array($ext, $allows) ) {
                    throw new Exception('file upload not allowed');
                }

                $video = new Video();
                $video->chapter_id = $chapter_id;
                $video->name = Input::get('name');
                $video->description = Input::get('description');
                $video->save();

                $name = $video->id.'.'.$ext;
                $videoFile->move('video', $name);
                chmod('video/'.$name, 0777);

                $video->video_link = $name;
                $video->save();

                $res = $video->toArray();
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

    }
}