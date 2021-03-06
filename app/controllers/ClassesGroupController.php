<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 20/8/2556
 * Time: 14:04 น.
 * To change this template use File | Settings | File Templates.
 */

class ClassesGroupController extends BaseController {
    public function _rules()
    {
        return array(
            'group'=> array(
                'post'=> array('admin'),
                'update'=> array('admin'),
                'delete'=> array('admin')
            )
        );
    }

    public function index($class_id)
    {
        $groups = Group::where('class_id', '=', $class_id)->get();
        $data = $groups->toArray();

        $videos_id = $groups->lists('video_id');
        if(count($videos_id) > 0){
            $videos = NewsVideo::whereIn('id', $videos_id)->get();
        }

        $groups_week = false;
        $fnGetWeek = function($id) use($groups_week, $groups){
            if(!$groups_week){
                $groups_id = $groups->lists("id");
                $groups_week = GroupWeek::whereIn("id", $groups_id)->get();
            }

            $buffer = $groups_week->filter(function($item) use($id){
                if($item->id==$id){
                    return true;
                }
            });

            return $buffer->first()->toArray();
        };
        foreach($data as $key => $value){
            if($videos_id>0){
                $buffer = $videos->filter(function($item) use ($value){
                    if($value['video_id']==$item->id){
                        return true;
                    }
                });
                if($buffer->count()>0){
                    $buffer2 = $buffer->first()->toArray();
                    $buffer2['link'] = URL::to('news_video/'.$buffer2['video_link']);
                    $buffer2['thumb'] = URL::to('news_video/'.$buffer2['id'].'.jpeg');
                    $data[$key]['video'] = $buffer2;
                }
            }
            if($this->_isset_field("group_week")){
                $data[$key]['group_week'] = $fnGetWeek($data[$key]['id']);
            }
        }

        return Response::json(array(
            'length'=> $groups->count(),
            'data'=> $data
        ));
    }

    public function show($class_id, $group_id)
    {
        try {
            $group = Group::findOrFail($group_id);
            $response = $group->toArray();
            $week = GroupWeek::findOrFail($group_id);

            /*
            if($this->_isset_field('users')){
                $users = UserGroup::where('group_id', '=', $group_id)->with('user')->get();
                $response['users'] = array('data'=> array(), 'length'=> 0);
                $response['users']['data'] = $users->toArray();
            }
            */

            $data = $group->toArray();
            $data['group_week'] = $week->toArray();
            $video = NewsVideo::find($data['video_id']);
            if(!is_null($video)){
                $data['video'] = $video->toArray();
                $data['video']['thumb'] = URL::to('news_video/'.$video->id.'.jpeg');
                $data['video']['link'] = URL::to('news_video/'.$video->video_link);
            }

            if($this->_isset_field("group_week")){
                $data['group_week'] = GroupWeek::find($group_id)->toArray();
            }

            return Response::json($data);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($class_id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $class_id){
                $validator = Validator::make(Input::all(), array(
                    'name'=> array('required'),
                    'description'=> array('required'),
                    'group_week'=> array('required'),
                    'group_study'=> array('required'),
                    'group_week.study_count'=> array('required'),
                    'group_week.date_start'=> array('required'),
                    'group_week.date_end'=> array('required')
                ));
                if($validator->fails())
                    throw new Exception($validator->messages()->first());

                if(!Input::hasFile("video")){
                    throw new Exception("video is required");
                }

                $input_week = Input::get("group_week");

                $classed = Classes::findOrFail($class_id);

                $group = new Group();
                $group->name = Input::get('name');
                $group->description = Input::get('description');
                $group->class_id = $class_id;

                Log::info("0");
                // video
                if(!Input::hasFile('video')){
                    throw new Exception("this action is required upload video");
                }
                $media = Input::file('video');
                $ext = strtolower($media->getClientOriginalExtension());

                if($ext != 'mp4'){
                    throw new Exception("media type not allow");
                }

                $news_video = new NewsVideo();
                $news_video->save();

                $name = $news_video->id.'.'.$ext;
                $media->move('news_video', $name);
                chmod('news_video/'.$name, 0777);

                $video_path = 'news_video/'.$name;
                $thumbnail_path = 'news_video/'.$news_video->id.'.jpeg';

                // shell command [highly simplified, please don't run it plain on your script!]
                shell_exec("ffmpeg -i {$video_path} -deinterlace -an -ss 1 -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg {$thumbnail_path} 2>&1");
                chmod($thumbnail_path, 0777);

                $news_video->video_link = $name;
                $news_video->save();

                $group->video_id = $news_video->id;

                $group->save();
                $classed->group_length = Group::where("class_id", "=", $class_id)->count();
                $classed->save();
                $group_week = new GroupWeek();
                $group_week->id = $group->id;
                $group_week->study_count = $input_week["study_count"];
                $group_week->date_start = $input_week["date_start"];
                $group_week->date_end = $input_week["date_end"];

                $group_week->sun_active = isset($input_week['sun_active']);
                if(isset($input_week['sun_active'])){
                    $group_week->sun_start = $input_week["sun_start"];
                    $group_week->sun_end = $input_week["sun_end"];
                }
                $group_week->mon_active = isset($input_week['mon_active']);
                if(isset($input_week['mon_active'])){
                    $group_week->mon_start = $input_week["mon_start"];
                    $group_week->mon_end = $input_week["mon_end"];
                }
                $group_week->tue_active = isset($input_week['tue_active']);
                if(isset($input_week['tue_active'])){
                    $group_week->tue_start = $input_week["tue_start"];
                    $group_week->tue_end = $input_week["tue_end"];
                }
                $group_week->wed_active = isset($input_week['wed_active']);
                if(isset($input_week['wed_active'])){
                    $group_week->wed_start = $input_week["wed_start"];
                    $group_week->wed_end = $input_week["wed_end"];
                }
                $group_week->thu_active = isset($input_week['thu_active']);
                if(isset($input_week['thu_active'])){
                    $group_week->thu_start = $input_week["thu_start"];
                    $group_week->thu_end = $input_week["thu_end"];
                }
                $group_week->fri_active = isset($input_week['fri_active']);
                if(isset($input_week['fri_active'])){
                    $group_week->fri_start = $input_week["fri_start"];
                    $group_week->fri_end = $input_week["fri_end"];
                }
                $group_week->sat_active = isset($input_week['sat_active']);
                if(isset($input_week['sat_active'])){
                    $group_week->sat_start = $input_week["sat_start"];
                    $group_week->sat_end = $input_week["sat_end"];
                }
                $group_week->save();

                $input_study = Input::get("group_study");
                foreach($input_study as $key => $value){
                    $group_study = new GroupStudy();
                    $group_study->group_id = $group->id;
                    $group_study->status = "active";
                    $group_study->start = $value['start'];
                    $group_study->end = $value['end'];
                    $group_study->ori_start = $value['start'];
                    $group_study->ori_end = $value['end'];
                    $group_study->save();
                }
                $res = $group->toArray();
                $res['video'] = $news_video->toArray();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function update($class_id, $id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $class_id, $id){
                $item = Group::findOrFail($id);

                if(Input::has('name')){
                    $item->name = Input::get('name');
                }

                if(Input::has('description')){
                    $item->description = Input::get('description');
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

    public function editVideo($class_id, $id)
    {
        $res = array();
        try {
            DB::transaction(function() use (&$res, $class_id, $id){
                $group = Group::findOrFail($id);

                $media = Input::file('video');
                $ext = strtolower($media->getClientOriginalExtension());
                $pic_allows = array('mp4');

                if(!in_array($ext, $pic_allows)){
                    throw new Exception("Video upload allow mp4 only");
                }

                $oldPicture = NewsVideo::find($group->video_id);

                $news_video = new NewsVideo();
                $news_video->save();

                $name = $news_video->id.'.'.$ext;
                $media->move('news_video', $name);
                chmod('news_video/'.$name, 0777);

                $video_path = 'news_video/'.$name;
                $thumbnail_path = 'news_video/'.$news_video->id.'.jpeg';

                // shell command [highly simplified, please don't run it plain on your script!]
                shell_exec("ffmpeg -i {$video_path} -deinterlace -an -ss 1 -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg {$thumbnail_path} 2>&1");
                chmod($thumbnail_path, 0777);

                $news_video->video_link = $name;
                $news_video->save();

                $group->video_id = $news_video->id;
                $group->save();

                $res = $group->toArray();
                $res['video'] = $news_video->toArray();
                $res['video']['thumb'] = URL::to("news_video/".$news_video->id.'.jpg');
                $res['video']['link'] = URL::to("news_video/".$news_video->picture_link);

                if(!is_null($oldPicture)){
                    $oldPath = "video/".$oldPicture->picture_link;
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

    public function destroy($class_id, $group_id)
    {
        try {
            $response = array();
            DB::transaction(function() use($group_id, $class_id, &$response){
                $class = Classes::findOrFail($class_id);
                $group = Group::findOrFail($group_id);
                $video = NewsVideo::find($group->video_id);
                $response = $group->toArray();
                $group->delete();

                //update class length
                UserGroup::where("group_id", "=", $group_id)->delete();
                $class->group_length = Group::where("class_id", "=", $class->id)->count();

                //delete register group
                RegisterGroup::where("group_id", "=", $group_id)->delete();

                //delete joined group
                @UserGroup::where("group_id", "=", $group_id)->delete();

                //delete in calendar
                @GroupStudy::where("group_id", "=", $group_id)->delete();

                //delete in groups_week
                @GroupWeek::where("id", "=", $group_id)->delete();

                $class->save();
                if(!is_null($video)){
                    $video_path = "news_video/".$video->video_link;
                    $video->delete();
                    @unlink($video_path);
                }
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}