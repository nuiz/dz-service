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

            /*
            if($this->_isset_field('users')){
                $users = UserGroup::where('group_id', '=', $group_id)->with('user')->get();
                $response['users'] = array('data'=> array(), 'length'=> 0);
                $response['users']['data'] = $users->toArray();
            }
            */

            $data = $group->toArray();
            $video = NewsVideo::find($data['video_id']);
            if(!is_null($video)){
                $data['video'] = $video->toArray();
                $data['video']['thumb'] = URL::to('news_video/'.$video->id.'.jpeg');
                $data['video']['link'] = URL::to('news_video/'.$video->video_link);
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
                    'video'=> array('required')
                ));
                if($validator->fails())
                    throw new Exception($validator->messages()->first());

                $classed = Classes::findOrFail($class_id);

                $group = new Group();
                $group->name = Input::get('name');
                $group->description = Input::get('description');
                $group->class_id = $class_id;

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
                    $item = Input::get('name');
                }

                if(Input::has('description')){
                    $item->name = Input::get('description');
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

    public function destroy($class_id, $group_id)
    {
        try {
            $response = array();
            DB::transaction(function() use($group_id, &$response){
                $group = Group::findOrFail($group_id);
                $video = NewsVideo::find($group->video_id);
                $response = $group->toArray();
                $group->delete();

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