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
        error_log("header token".Input::header('X-Auth-Token'));
        $user = Auth::getUser();

        if(isset($_GET['start_date']) && isset($_GET['end_date'])){
            $collection = Activity::where(DB::raw("YEAR(start_time)"), ">=", $_GET['start_date'])->where(DB::raw("MONTH(start_time)"), "<=", $_GET['end_date'])->orderBy('created_at', 'desc')->get();
        }
        else {
            $collection = Activity::orderBy('created_at', 'desc')->get();
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
            $items[$key]['start_time_2'] = date("g:i A", strtotime($value['start_time']));

            if(!is_null($user)){
                $items[$key]['is_joined'] = UserActivity::where("user_id", "=", $user->id)->where("activity_id", "=", $value["id"])->count() > 0;
            }
            if($this->_isset_field('like')){
                $items[$key]['like'] = Like::find($value['id'])->toArray();
                if(!is_null(Auth::getUser())){
                    $items[$key]['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $value['id'])->count() > 0;
                }
            }

        }

        $fnFilterDay = (function($date) use($items){
            $data = array();
            foreach($items as $key => $value){
                $time = strtotime($value['start_time']);
                if(date('Y-m-d', $time)==$date){
                    $data[] = $value;
                }
            }
            return array(
                'date'=> $date,
                'has_data'=> count($data)>0? "yes": "no",
                'length'=> count($data),
                'data'=> $data
            );
        });

        $data = array();
        if(isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['list_mode']) && $_GET['list_mode']=='day'){
            $listDay = array();
            $count = count($items);
            $date = new DateTime($_GET['start_date']);
            $end = new DateTime($_GET['end_date']);
            for($i=0; $date->getTimestamp() <= $end->getTimestamp(); $i++) {
                $listDay[] = $fnFilterDay($date->format('Y-m-d'));
                $date->add(new DateInterval("P1D"));
            }
            $data = $listDay;
        }
        else {
            $data = $items;
        }

        return Response::json(array(
            'length'=> count($data),
            'data'=> $data
        ));
    }

    public function show($id)
    {
        try {
            $user = Auth::getUser();
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
            else {
                $item['picture'] = $picture->toArray();
                $item['picture']['link'] = URL::to("picture/".$picture->picture_link);
            }

            if(!is_null($user)){
                $item['is_joined'] = UserActivity::where("user_id", "=", $user->id)->where("activity_id", "=", $item["id"])->count() > 0;
            }
            $item['start_time_2'] = date("H:i A", strtotime($item['start_time']));
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
                    $item->name = Input::get('name');
                }

                if(Input::has('message')){
                    $item->message = Input::get('message');
                }

                if(Input::has('start_time')){
                    $item->start_time = date("Y-m-d H:i:s", strtotime(Input::get('start_time')));
                }

                if(Input::has('deletePicture') && Input::get('deletePicture')=="yes"){
                    $picture = Picture::find($item->picture_id);
                    if(!is_null($picture)){
                        $path = "picture/".$picture->picture_link;
                        $picture->delete();
                        @unlink($path);
                    }
                    $item->picture_id = 0;
                }

                $item->save();
                $res = $item->toArray();

                $picture = Picture::find($res['picture_id']);
                if(!is_null($picture))
                    $res['picture'] = $picture->toArray();

            });
            return Response::json($res);
        }
        catch (Exception $e){
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function editPicture($id)
    {
        $res = array();
        try {
            DB::transaction(function() use (&$res, $id){
                $item = Activity::findOrFail($id);

                $picFile = Input::file('picture');
                $ext = strtolower($picFile->getClientOriginalExtension());
                $pic_allows = array('jpg', 'jpeg', 'png');

                if(!in_array($ext, $pic_allows)){
                    throw new Exception("Picture upload allow jpg,jpeg,png only");
                }

                $oldPicture = Picture::find($item->picture_id);

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
                $item->save();

                $res = $item->toArray();
                $res['picture'] = $picture->toArray();
                $res['picture']['link'] = URL::to("picture/".$picture->picture_link);

                if(!is_null($oldPicture)){
                    $oldPath = "picture/".$oldPicture->picture_link;
                    @$oldPicture->delete();
                    @unlink($oldPath);
                }
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