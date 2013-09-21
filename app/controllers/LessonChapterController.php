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
        return Response::json(array(
            'length'=> $chapters->count(),
            'data'=> $chapters->toArray()
        ));
    }

    public function show($lesson_id, $id)
    {
        try {
            $chapter = Chapter::findOrFail($id);
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

                $chapter->save();
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