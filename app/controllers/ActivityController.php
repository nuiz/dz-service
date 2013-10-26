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
        $user = Auth::getUser();

        if(isset($_GET['start_date'])){
            $exSD = explode("-", $_GET['start_date']);
            if($exSD[0] > 2500){
                $exSD[0] = $exSD[0]-543;
            }
            $_GET['start_date'] = implode("-", $exSD);
        }

        if(isset($_GET['end_date'])){
            $exSD = explode("-", $_GET['end_date']);
            if($exSD[0] > 2500){
                $exSD[0] = $exSD[0]-543;
            }
            $_GET['end_date'] = implode("-", $exSD);
        }

        if(isset($_GET['year'])){
            if($_GET['year'] > 2500){
                $_GET['year'] = $_GET['year']-543;
            }
        }

        if(isset($_GET['start_date']) && isset($_GET['end_date'])){
            $collection = Activity::where("start_time", ">=", $_GET['start_date'])->where("start_time", "<=", $_GET['end_date'])->orderBy('start_time', 'asc')->get();
        }
        else if(isset($_GET['month']) && isset($_GET['year'])){
            $collection = Activity::where(DB::raw("MONTH(start_time)"), "=", $_GET['month'])
                ->where(DB::raw("YEAR(start_time)"), "=", $_GET['year'])
                ->orderBy('start_time', 'asc')->get();
        }
        else if(isset($_GET['list_mode']) && $_GET['list_mode']=='start_month'){
            $collection = Activity::where("start_time", ">=", date('Y-m-1'))
                ->orderBy('start_time', 'asc')->get();
        }
        else {
            $collection = Activity::orderBy('start_time', 'asc')->get();
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
            if($this->_isset_field('comment')){
                $items[$key]['comment'] = Comment::find($value['id'])->toArray();
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

        $fnFilterMonth = (function($month, $year) use($items){
            $data = array();
            foreach($items as $key => $value){
                $time = strtotime($value['start_time']);
                if(date('Y-m', $time)==$year."-".$month){
                    $data[] = $value;
                }
            }
            return count($data)==0? false: array(
                'month'=> $month,
                'year'=> $year,
                'header'=> date("F - Y", strtotime($year." ".$month)),
                'length'=> count($data),
                'data'=> $data
            );
        });

        $data = array();
        if(isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['list_mode']) && $_GET['list_mode']=='day'){
            $listDay = array();
            $date = new DateTime($_GET['start_date']);
            $end = new DateTime($_GET['end_date']);
            for($i=0; $date->getTimestamp() <= $end->getTimestamp(); $i++) {
                $listDay[] = $fnFilterDay($date->format('Y-m-d'));
                $date->add(new DateInterval("P1D"));
            }
            $data = $listDay;
        }
        else if(isset($_GET['list_mode']) && $_GET['list_mode']=='start_month') {
            $start = new DateTime(date('Y-m-1'));
            $lastDate = $collection->count()>0 ? date('Y-m-d', strtotime($collection->last()->start_time)): date('Y-m-d');
            $last = new DateTime($lastDate);
            $listMonth = array();
            while($start->getTimestamp() <= $last->getTimestamp()){
                $buffer = $fnFilterMonth($start->format('m'), $start->format('Y'));
                if($buffer)
                    $listMonth[] = $buffer;

                $start->add(new DateInterval("P1M"));
                $start->setDate($start->format('Y'), $start->format('m'), 1);
            }
            $data = $listMonth;
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

                    $image = Image::make($picFile->getRealPath());
                    $wide = $image->height > $image->width? false: true;
                    if($wide && $image->width > 640){
                        $image->resize(640, null, true);
                    }
                    else if(!$wide && $image->height > 1136){
                        $image->resize(null, 1136, true);
                    }

                    $picture = new Picture();
                    $picture->size_x = $image->width;
                    $picture->size_y = $image->height;
                    $picture->save();

                    $name = $picture->id.'.'.$ext;
                    $saveTo = 'picture/'.$name;
                    $image->save($saveTo);
                    chmod($saveTo, 0777);

                    $picture->picture_link = $name;
                    $picture->save();

                    $item->picture_id = $picture->id;
                }

                $item->save();
                $res = $item->toArray();
            });

            $resp = Response::json($res);
            $resp->send();

            $users_setting = UserSetting::where("news_from_dancezone", "=", "1")->get();
            if($users_setting->count() > 0){
                $users_id = $users_setting->lists("id");
                $users = User::whereIn("id", $users_id)->get();

                $users->each(function($user) use($res){
                    $notification = new Notification();
                    $notification->object_id = $res['id'];
                    $notification->user_id = $user->id;
                    $notification->type = "activity";
                    $notification->message = "Update: added activity";
                    $notification->save();

                    $nfData = array(
                        'id'=> $notification->id,
                        'object_id'=> $res['id'],
                        'type'=> "activity"
                    );
                    if(!empty($user->ios_device_token)){
                        IOSPush::push($user->ios_device_token, "Update: added activity", $nfData);
                    }
                });
            }
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

                $image = Image::make($picFile->getRealPath());
                $wide = $image->height > $image->width? false: true;
                if($wide && $image->width > 640){
                    $image->resize(640, null, true);
                }
                else if(!$wide && $image->height > 1136){
                    $image->resize(null, 1136, true);
                }

                $oldPicture = Picture::find($item->picture_id);

                $picture = new Picture();
                $picture->size_x = $image->width;
                $picture->size_y = $image->height;
                $picture->save();

                $name = $picture->id.'.'.$ext;
                $saveTo = 'picture/'.$name;
                $image->save($saveTo);
                chmod($saveTo, 0777);

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