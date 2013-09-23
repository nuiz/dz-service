<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nuiz
 * Date: 21/9/2556
 * Time: 13:48 น.
 * To change this template use File | Settings | File Templates.
 */

class ActivityController extends BaseController {
    public function index()
    {
        $items = Activity::all()->toArray();
        foreach($items as $key => $value){
            if($this->_isset_field('like')){
                $items[$key]['like'] = Like::find($value['id'])->toArray();
                if(!is_null(Auth::getUser())){
                    $items[$key]['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $value['id'])->count() > 0;
                }
            }
        }

        return Response::json(array(
            'length'=> count($items),
            'data'=> $items
        ));
    }

    public function show($id)
    {
        try {
            $item = Activity::findOrFail($id)->toArray();
            if($this->_isset_field('like')){
                $items['like'] = Like::find($id)->toArray();
                if(!is_null(Auth::getUser())){
                    $items['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $id)->count() > 0;
                }
            }
            return Response::json($item);
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
                    'name'=> array('required'),
                    'message'=> array('required'),
                    'start_time'=> array('required')
                ));

                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }
                $item = new Activity();
                $item->name = Input::get('name');
                $item->message = Input::get('message');
                $item->start_time = date("Y-m-d H:i:s", strtotime(Input::get('start_time')));

                $item->save();
                $res = $item->toArray();
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
                $item = Activity::findOrFail($id);

                if(Input::has('name')){
                    $item = Input::get('name');
                }

                if(Input::has('message')){
                    $item->name = Input::get('message');
                }

                if(Input::has('start_time')){
                    $item->name = date("Y-m-d H:i:s", strtotime(Input::get('start_time')));
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

    public function destroy($id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $id){
                $item = Activity::findOrFail($id);
                $res = $item->toArray();

                $item->delete();
            });
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}