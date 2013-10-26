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
        $lessons = Lesson::orderBy('sort_seq', 'asc')->get();
        $data = $lessons->toArray();
        foreach($data as $key => $value){
            $logo = $value['logo'];
            $data[$key]['logo_link'] = URL::to("lesson_logo/Dancer{$logo}Ip5@2x.png");
        }
        $res = Response::json(array(
            'length'=> count($data),
            'data'=> $data
        ));
        $res->send();
    }

    public function show($id)
    {
        try {
            $lesson = Lesson::findOrFail($id);
            $data = $lesson->toArray();
            $logo = $data['logo'];
            $data['logo_link'] = URL::to("lesson_logo/Dancer{$logo}Ip5@2x.png");
            return Response::json($data);
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
                    'name'=> array('required'),
                    'logo'=> array('required')
                ));

                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }
                $lesson = new Lesson();
                $lesson->name = Input::get('name');
                $lesson->color = Input::get('color');
                $lesson->logo = Input::get('logo');
                $lesson->sort_seq = Lesson::max("sort_seq") + 1;

                $lesson->save();
                $res = $lesson->toArray();
            });
            $resp = Response::json($res);
            $resp->send();

            $users_setting = UserSetting::where("new_lesson", "=", "1")->get();
            if($users_setting->count() > 0){
                $users_id = $users_setting->lists("id");
                $users = User::whereIn("id", $users_id)->get();

                $users->each(function($user) use($res){
                    $notification = new Notification();
                    $notification->object_id = $res['id'];
                    $notification->user_id = $user->id;
                    $notification->type = "lesson";
                    $notification->message = "Update: added lesson";
                    $notification->save();

                    $nfData = array(
                        'id'=> $notification->id,
                        'object_id'=> $res['id'],
                        'type'=> "lesson"
                    );
                    if(!empty($user->ios_device_token)){
                        IOSPush::push($user->ios_device_token, "Update: added lesson", $nfData);
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
                $lesson = Lesson::findOrFail($id);

                if(Input::has('color')){
                    $lesson->color = Input::get('color');
                }

                if(Input::has('logo')){
                    $lesson->logo = Input::get('logo');
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

                //delete chapter and video
                $chapters = Chapter::where("lesson_id", "=", $id)->get();
                $chapters_id = $chapters->lists("id");
                if(count($chapters_id)> 0){
                    Chapter::where("lesson_id", "=", $id)->delete();
                    Video::whereIn("chapter_id", "=", $chapters_id)->delete();
                }
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function postSort()
    {
        try {
            DB::transaction(function(){
                $sortData = Input::get("sortData");
                foreach ($sortData as $key => $value){
                    $item = Lesson::findOrFail($value);
                    $item->sort_seq = $key;
                    $item->save();
                }
            });
            return Response::json(array('sort'=> Input::get('sortData')));
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}