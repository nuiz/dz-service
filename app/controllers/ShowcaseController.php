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
            $data = $showcases->toArray();

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
                $showcase = Showcase::find($id);
                $showcase->delete();
            });
        } catch(Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}