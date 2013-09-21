<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nuiz
 * Date: 21/9/2556
 * Time: 9:04 น.
 * To change this template use File | Settings | File Templates.
 */

class LessonController extends BaseController {
    public function index()
    {
        $lesson = Lesson::all();
        return Response::json(array(
            'length'=> $lesson->count(),
            'data'=> $lesson->toArray()
        ));
    }

    public function show($id)
    {
        try {
            $lesson = Lesson::findOrFail($id);
            return Response::json($lesson);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store()
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res){
                $validator = Validator::make(Input::all(), array(
                    'color'=> array('required'),
                    'name'=> array('required')
                ));

                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }
                $lesson = new Lesson();
                $lesson->name = Input::get('name');
                $lesson->color = Input::get('color');

                $lesson->save();
                $res = $lesson->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function update($id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $id){
                $lesson = Lesson::findOrFail($id);

                if(Input::has('color')){
                    $lesson = Input::get('color');
                }

                if(Input::has('name')){
                    $lesson->name = Input::get('name');
                }

                $lesson->save();
                $res = $lesson->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e){
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $id){
                $lesson = Lesson::findOrFail($id);
                $res = $lesson->toArray();

                $lesson->delete();
            });
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}