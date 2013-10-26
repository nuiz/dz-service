<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 17/10/2556
 * Time: 13:49 น.
 * To change this template use File | Settings | File Templates.
 */

class GroupController extends BaseController {
    public function index()
    {
        $items = Group::all();
        $data = $items->toArray();
        if($this->_isset_field('class')){
            $class = Classes::all();
            foreach($data as $key => $value){
                $buffer = $class->filter(function($item) use($value){
                    if($item->id==$value['class_id'])
                        return true;
                });
                $data[$key]['class'] = $buffer->first()->toArray();
            }
        }
        return Response::json(array(
            'length'=> count($data),
            'data'=> $data
        ));
    }

    public function show($group_id)
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
}