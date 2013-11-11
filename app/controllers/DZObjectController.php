<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 21/9/2556
 * Time: 16:40 น.
 * To change this template use File | Settings | File Templates.
 */

class DZObjectController extends BaseController {
    public function show($id){
        try {
            $dz_object = DzObject::findOrFail($id);
            $res = array(
                "id"=> $dz_object->id,
                "type"=> $dz_object->type
            );
            $picture_id = false;
            $video_id = false;
            if($dz_object->type == "news"){
                $buffer = News::findOrFail($id);
                $res['news'] = $buffer->toArray();
                if($buffer['media_type']=='picture'){
                    $picture_id = $buffer['picture_id'];
                }
                else if($buffer['media_type']=='video'){
                    $video_id = $buffer['video_id'];
                }
            }
            else if($dz_object->type == "showcase"){
                $buffer = Showcase::findOrFail($id);
                $res['showcase'] = $buffer->toArray();
                $picture_id = $buffer['picture_id'];
            }
            else if($dz_object->type == "activity"){
                $buffer = Activity::findOrFail($id);
                $res['activity'] = $buffer->toArray();
                $picture_id = $buffer['picture_id'];
            }

            if($picture_id && $picture_id!=0){
                $picture = Picture::findOrFail($picture_id);
                $res[$dz_object->type]['picture'] = $picture->toArray();
                $res[$dz_object->type]['picture']['link'] = URL::to("picture/".$picture->picture_link);
            }
            else if($video_id && $video_id!=0){
                $video = Picture::findOrFail($video_id);
                $res[$dz_object->type]['video'] = $video->toArray();
                $res[$dz_object->type]['video']['link'] = URL::to("video/".$video->video_id.".jpeg");
            }
            return Response::json($res);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}