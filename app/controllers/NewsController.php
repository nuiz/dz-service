<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 19/9/2556
 * Time: 14:08 น.
 * To change this template use File | Settings | File Templates.
 */

class NewsController extends BaseController {
    public function index()
    {
        try {
            $news = News::all();
            return Response::json(array(
                'length'=> $news->count(),
                'data'=> $news->toArray()
            ));
        }
        catch (Exception $e) {

        }
    }

    public function store()
    {
        try {
            $res = array();
            DB::transaction(function() use (&$res){
                $validator = Validator::make(Input::all(), array(
                    'name'=> array('required'),
                    'description'=> array('required')
                ));
                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }

                $news = new News();
                $news->name = Input::get('name');
                $news->description = Input::get('description');

                $news->save();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function() use($id) {
                $news = News::findOrFail($id);
                return Response::json($news);
            });
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}