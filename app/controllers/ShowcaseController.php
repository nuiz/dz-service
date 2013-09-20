<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 30/8/2556
 * Time: 10:43 น.
 * To change this template use File | Settings | File Templates.
 */

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
            $showcases = Showcase::all();

            return Response::json(array(
                'length'=> $showcases->count(),
                'data'=> $showcases->toArray()
            ));
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function show($id){
        try {
            $showcase = Showcase::findOrFail($id);
            return Response::json($showcase);
        }
        catch (Exception $e){
            return Response::exception($e);
        }
    }

    //admin only can store showcase
    public function store(){
        $response = array();
        try {
            $validator = Validator::make(Input::all(), array(
                'youtube_id'=> array('required')
            ));
            if($validator->fails()){
                throw new Exception($validator->errors());
            }
            DB::transaction(function() use (&$response){
                $showcase = new Showcase();
                $showcase->youtube_id = Input::get('youtube_id');
                $showcase->save();

                $response = $showcase->getAttributes();
            });
            Response::json($response);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    //admin only can update showcase
    public function update($id){
        $response = array();
        try {
            $validator = Validator::make(Input::all(), array(
                'id'=> array('required')
            ));
            if($validator->fails())
                throw new Exception($validator->errors());

            DB::transaction(function() use($id, &$response){
                $showcase = Showcase::find($id);
                if(Input::has('youtube_id'))
                    $showcase->youtube_id = Input::get('youtube_id');
                $showcase->save();

                $response = $showcase->getAttributes();
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
                $showcase = Showcase::find($id);
                $showcase->delete();
            });
        } catch(Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}