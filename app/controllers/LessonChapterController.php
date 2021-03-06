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
        $lesson = Lesson::findOrFail($lesson_id);
        $chapters = Chapter::where('lesson_id', '=', $lesson_id)->orderBy('sort_seq', 'asc')->get();
        $data = $chapters->toArray();

        $pictures_id = $chapters->lists('picture_id');
        if(count($pictures_id) > 0){
            $pictures = Picture::whereIn('id', $pictures_id)->get();
        }
        foreach ($data as $key => $value){
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
            $data[$key]['picture'] = $picture;
            $data[$key]['color'] = $lesson->color;
        }
        return Response::json(array(
            'length'=> count($data),
            'data'=> $data
        ));
    }

    public function show($lesson_id, $id)
    {
        try {
            $lesson = Lesson::findOrFail($lesson_id);
            $chapter = Chapter::findOrFail($id);
            $data = $chapter->toArray();
            $picture = Picture::find($data['picture_id']);
            if(is_null($picture))
                $data['picture'] = array('link'=> URL::to("picture/default.jpg"));
            else{
                $data['picture'] = $picture->toArray();
                $data['picture']['link'] = URL::to("picture/".$picture->picture_link);
            }
            $data['color'] = $lesson->color;

            return Response::json($data);
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
                $lesson = Lesson::findOrFail($lesson_id);

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

                    $chapter->picture_id = $picture->id;
                }

                $chapter->sort_seq = Chapter::where("lesson_id", "=", $lesson_id)->max("sort_seq") + 1;
                $chapter->save();

                $lesson->chapter_length = Chapter::where("lesson_id", "=", $lesson_id)->count();
                $lesson->save();

                $res = $chapter->toArray();
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
                    $notification->type = "chapter";
                    $notification->message = "Update: added chapter";
                    $notification->save();

                    $nfData = array(
                        'id'=> $notification->id,
                        'object_id'=> $res['id'],
                        'type'=> "chapter"
                    );
                    if(!empty($user->ios_device_token)){
                        IOSPush::push($user->ios_device_token, "Update: added chapter", $nfData);
                    }
                });
            }
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
                    $chapter->name = Input::get('name');
                }

                if(Input::has('description')){
                    $chapter->description = Input::get('description');
                }

                if(Input::has('deletePicture') && Input::get('deletePicture')=="yes"){
                    $picture = Picture::find($chapter->picture_id);
                    if(!is_null($picture)){
                        $path = "picture/".$picture->picture_link;
                        $picture->delete();
                        @unlink($path);
                    }
                    $chapter->picture_id = 0;
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

    public function editPicture($lesson_id, $id)
    {
        $res = array();
        try {
            DB::transaction(function() use (&$res, $lesson_id, $id){
                $chapter = Chapter::findOrFail($id);
                $oldPicture = Picture::find($chapter->picture_id);

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

                $chapter->picture_id = $picture->id;
                $chapter->save();

                $res = $chapter->toArray();
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

    public function destroy($lesson_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $lesson_id, $id){
                $lesson = Lesson::findOrFail($lesson_id);
                $chapter = Chapter::findOrFail($id);
                $picture = Picture::find($chapter->picture_id);

                $chapter->delete();

                $lesson->chapter_length = Chapter::where('lesson_id', '=', $lesson_id)->count();
                $lesson->save();

                //delete video
                Video::where("chapter_id", "=", $id)->delete();

                if(!is_null($picture)){
                    $picturePath = "picture/".$picture->picture_link;
                    @$picture->delete();
                    @unlink($picturePath);
                }

                $res = $chapter->toArray();
            });
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function postSort($lesson_id)
    {
        try {
            DB::transaction(function(){
                $sortData = Input::get("sortData");
                foreach ($sortData as $key => $value){
                    $item = Chapter::findOrFail($value);
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