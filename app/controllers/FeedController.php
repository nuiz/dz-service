<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 26/10/2556
 * Time: 11:17 น.
 * To change this template use File | Settings | File Templates.
 */

class FeedController extends BaseController {
    public function index(){
        try {
            $user = Auth::getUser();

            $limit = null;
            if(isset($_GET['limit'])){
                $limit = $_GET['limit'];
            }
            $paging = DzObject::whereIn("type", array("news", "showcase", "activity"))->orderBy('id', 'desc')->paginate($limit);
            $feeds = $paging->getCollection();
            $news_id = $feeds->filter(function($item){
                if($item->type=="news") return true;
            })->lists("id");
            $showcases_id = $feeds->filter(function($item){
                if($item->type=="showcase") return true;
            })->lists("id");
            $activities_id = $feeds->filter(function($item){
                if($item->type=="activity") return true;
            })->lists("id");

            $data = $feeds->toArray();

            $news = array();
            $pictures_id = array();
            $videos_id = array();
            if(count($news_id)>0){
                $news = News::whereIn("id", $news_id)->get();
                $pictures_id = array_merge($pictures_id, $news->lists("picture_id"));
                $videos_id = $news->lists('video_id');
            }
            $showcases = array();
            if(count($showcases_id)>0){
                $showcases = Showcase::whereIn("id", $showcases_id)->get();
                $pictures_id = array_merge($pictures_id, $showcases->lists("picture_id"));
            }
            $activities = array();
            if(count($activities_id)>0){
                $activities = Activity::whereIn("id", $activities_id)->get();
                $pictures_id = array_merge($pictures_id, $activities->lists("picture_id"));
            }
            $pictures = array();
            if(count($pictures_id)>0){
                $pictures = Picture::whereIn("id", $pictures_id)->get();
            }
            $videos = array();
            if(count($videos_id)>0){
                $videos = NewsVideo::whereIn('id', $videos_id)->get();
            }
            $fnGetPicture = (function($id) use($pictures){
                $buffer = $pictures->filter(function($item) use($id){
                    if($item->id == $id) return true;
                })->first();
                $data = $buffer->toArray();
                $data['link'] = URL::to("picture/".$data['picture_link']);
                return $data;
            });
            $feeds_id = $feeds->lists("id");
            $likes = array();
            if(count($feeds_id)>0){
                $likes = Like::whereIn("id", $feeds_id)->get();
            }

            $fnMakeObject = (function(&$object) use($news, $showcases, $activities, $fnGetPicture, $videos, $likes){

                if($object['type']=="news"){
                    $buffer = $news->filter(function($item) use($object){
                        if($object['id']==$item->id)
                            return true;
                    });
                    $object['news'] = $buffer->first()->toArray();
                    if($object['news']['media_type']=="picture"){
                        $object['news']['picutre'] = $fnGetPicture($object['news']['picture_id']);
                    }
                    else if($object['news']['media_type']=="video") {
                        $buffer_video = $videos->filter(function($item) use ($object){
                            if($object['video_id']==$item->id){
                                return true;
                            }
                        });
                        if($buffer_video->count()>0){
                            $buffer2 = $buffer_video->first()->toArray();
                            $buffer2['link'] = URL::to('news_video/'.$buffer2['video_link']);
                            $buffer2['thumb'] = URL::to('news_video/'.$buffer2['id'].'.jpeg');
                            $object['news']['video'] = $buffer2;
                        }
                    }
                }
                else if($object['type']=="showcase") {
                    $buffer = $showcases->filter(function($item) use($object){
                        if($object['id']==$item->id)
                            return true;
                    });
                    $object['showcase'] = $buffer->first()->toArray();
                }
                else if($object['type']=="activity") {
                    $buffer = $activities->filter(function($item) use($object){
                        if($object['id']==$item->id)
                            return true;
                    });
                    $object['activity'] = $buffer->first()->toArray();
                    if($object['activity']['picture_id']!=0){
                        $object['activity']['picutre'] = $fnGetPicture($object['activity']['picture_id']);
                    }
                }

                $user = Auth::user();
                if($this->_isset_field('like')){
                    $object[$object['type']]['like'] = $likes->filter(function($item) use ($object){
                        return $item->id == $object['id'];
                    })->first()->toArray();
                    if(!is_null($user)){
                        $object[$object['type']]['like']['is_liked'] = UserLike::where('user_id', '=', $user->id)->where('object_id', '=', $object['id'])->count() > 0;
                    }
                }

                if($this->_isset_field('comment')){
                    $object[$object['type']]['comment'] = Comment::find($object['id'])->toArray();
                }
            });

            foreach($data as $key => $value){
                $fnMakeObject($data[$key]);
            }
            $resData = array(
                "length"=> count($data),
                "data"=> $data,
                'paging'=> array(
                    'length'=> $paging->getLastPage(),
                    'current'=> $paging->getCurrentPage(),
                    'limit'=> $paging->getPerPage()
                )
            );
            if($paging->getCurrentPage() < $paging->getLastPage()){
                $query_string = http_build_query(array_merge($_GET, array(
                    "page"=> $paging->getCurrentPage()+1,
                    "limit"=> $paging->getPerPage()
                )));
                $resData['paging']['next'] = sprintf("%s?%s", URL::to("feed"), $query_string);
            }
            if($paging->getCurrentPage() > 1){
                $query_string = http_build_query(array_merge($_GET, array(
                    "page"=> $paging->getCurrentPage()-1,
                    "limit"=> $paging->getPerPage()
                )));
                $resData['paging']['previous'] = sprintf("%s?%s", URL::to("feed"), $query_string);
            }
            return Response::json($resData);
        }
        catch(Exception $e){
            return Response::exception($e);
        }
    }
}