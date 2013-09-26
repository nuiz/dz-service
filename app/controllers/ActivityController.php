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
        if(isset($_GET['year']) && isset($_GET['month'])){
            $collection = Activity::where(DB::raw("YEAR(start_time)"), "=", $_GET['year'])->where(DB::raw("MONTH(start_time)"), "=", $_GET['month'])->get();
        }
        else {
            $collection = Activity::all();
        }

        $items = $collection->toArray();

        $pictures_id = $collection->lists('picture_id');
        if(count($pictures_id) > 0){
            $pictures = Picture::whereIn('id', $pictures_id)->get();
        }
        foreach($items as $key => $value){
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
            $items[$key]['picture'] = $picture;

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
            $picture = Picture::find($item['picture_id']);
            if(is_null($picture))
                $item['picture'] = array('link'=> URL::to("picture/default.jpg"));
            else
                $item['picture'] = $picture->toArray();

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

                    $item->picture_id = $picture->id;
                }

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