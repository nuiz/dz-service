<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nuiz
 * Date: 21/9/2556
 * Time: 10:22 น.
 * To change this template use File | Settings | File Templates.
 */

class LessonChapterController extends BaseController {
    public function index($lesson_id)
    {
        $chapters = Chapter::where('lesson_id', '=', $lesson_id)->get();
        $data = $chapters->toArray();

        $pictures_id = $chapters->lists('picture_id');
        if(count($pictures_id) > 0){
            $pictures = Picture::whereIn('id', $pictures_id)->get();
        }
        foreach ($data as $key => $value){
            $picture = array('link'=> URL::to("picture/default.jpg"));
            if($pictures_id>0){
                $buffer = $pictures->filter(function($item) use ($value){
                    if($value['picture_id']==$item->id){
                        return true;
                    }
                });
                if($buffer->count()>0){
                    $buffer2 = $buffer->first()->toArray();
                    $buffer2['link'] = URL::to('picture/'.$buffer2['picture_link']);
                    $picture = $buffer2;
                }
            }
            $data[$key]['picture'] = $picture;
        }
        return Response::json(array(
            'length'=> count($data),
            'data'=> $data
        ));
    }

    public function show($lesson_id, $id)
    {
        try {
            $chapter = Chapter::findOrFail($id);
            $data = $chapter->toArray();
            $picture = Picture::find($data['picture_id']);
            if(is_null($picture))
                $data['picture'] = array('link'=> URL::to("picture/default.jpg"));
            else
                $data['picture'] = $picture->toArray();

            return Response::json($chapter);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($lesson_id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $lesson_id){
                $lesson = Lesson::findOrFail($lesson_id);

                $validator = Validator::make(Input::all(), array(
                    'name'=> array('required'),
                    'description'=> array('required')
                ));

                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }

                $chapter = new Chapter();
                $chapter->lesson_id = $lesson_id;
                $chapter->name = Input::get('name');
                $chapter->description = Input::get('description');

                if(Input::hasFile('picture')){
                    $picFile = Input::file('picture');
                    $ext = strtolower($picFile->getClientOriginalExtension());
                    $pic_allows = array('jpg', 'jpeg', 'png');

                    if(!in_array($ext, $pic_allows)){
                        throw new Exception("Picture upload allow jpg,jpeg,png only");
                    }

                    $picture = new Picture();
                    list($width, $height, $type, $attr) = getimagesize($picFile->getRealPath());
                    $picture->size_x = $width;
                    $picture->size_y = $height;
                    $picture->save();

                    $name = $picture->id.'.'.$ext;
                    $picFile->move('picture', $name);
                    chmod('picture/'.$name, 0777);

                    $picture->picture_link = $name;
                    $picture->save();

                    $chapter->picture_id = $picture->id;
                }

                $chapter->save();

                $lesson->chapter_length = Chapter::where("lesson_id", "=", $lesson_id)->count();
                $lesson->save();

                $res = $chapter->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function update($lesson_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $lesson_id, $id){
                $chapter = Chapter::findOrFail($id);

                if(Input::has('name')){
                    $chapter = Input::get('name');
                }

                if(Input::has('description')){
                    $chapter->name = Input::get('description');
                }

                $chapter->save();
                $res = $chapter->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e){
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($lesson_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $lesson_id, $id){
                $lesson = Lesson::findOrFail($lesson_id);
                $chapter = Chapter::find($id);

                $chapter->delete();
                $lesson->chapter_length = Chapter::where('lesson_id', '=', $lesson_id)->count();
                $lesson->save();

                $res = $chapter->toArray();
            });
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}