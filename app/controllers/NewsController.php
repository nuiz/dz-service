<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 19/9/2556
 * Time: 14:08 น.
 * To change this template use File | Settings | File Templates.
 */

class NewsController extends BaseController {
    public function index()
    {
        try {
            $user = Auth::getUser();

            $limit = null;
            if(isset($_GET['limit'])){
                $limit = $_GET['limit'];
            }
            $paging = News::orderBy('created_at', 'desc')->paginate($limit);
            $news = $paging->getCollection();
            $data = $news->toArray();
            $pictures_id = $news->lists('picture_id');
            $videos_id = $news->lists('video_id');
            $news_id = $news->lists('id');

            if(count($pictures_id)>0){
                $pictures = Picture::whereIn('id', $pictures_id)->get();
            }
            if(count($videos_id)>0){
                $videos = NewsVideo::whereIn('id', $videos_id)->get();
            }
            if(count($data)> 0 & $this->_isset_field('like')){
                $likes = Like::whereIn('id', $news_id)->get();
            }
            foreach($data as $key => $value) {
                if($pictures_id>0){
                    $buffer = $pictures->filter(function($item) use ($value){
                        if($value['picture_id']==$item->id){
                            return true;
                        }
                    });
                    if($buffer->count()>0){
                        $buffer2 = $buffer->first()->toArray();
                        $buffer2['link'] = URL::to('picture/'.$buffer2['picture_link']);
                        $data[$key]['picture'] = $buffer2;
                    }
                }
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
                if($value['media_type']=='none'){
                    $data[$key]['picture'] = array(
                        'link'=> URL::to('picture/default.jpg')
                    );
                }
                if($this->_isset_field('like')){
                    $data[$key]['like'] = $likes->filter(function($item) use ($value){
                        return $item->id == $value['id'];
                    })->first()->toArray();
                    if(!is_null($user)){
                        $data[$key]['like']['is_liked'] = UserLike::where('user_id', '=', $user->id)->where('object_id', '=', $value['id'])->count() > 0;
                    }
                }
                if($this->_isset_field('comment')){
                    $data[$key]['comment'] = Comment::find($value['id'])->toArray();
                }
            }

            $res = array(
                'length'=> $paging->getTotal(),
                'data'=> $data,
                'paging'=> array(
                    'length'=> $paging->getLastPage(),
                    'current'=> $paging->getCurrentPage(),
                    'limit'=> $paging->getPerPage()
                ),
            );
            if($paging->getCurrentPage() < $paging->getLastPage()){
                $query_string = http_build_query(array_merge($_GET, array(
                    "page"=> $paging->getCurrentPage()+1,
                    "limit"=> $paging->getPerPage()
                )));
                $res['paging']['next'] = sprintf("%s?%s", URL::to("news"), $query_string);
            }
            if($paging->getCurrentPage() > 1){
                $query_string = http_build_query(array_merge($_GET, array(
                    "page"=> $paging->getCurrentPage()-1,
                    "limit"=> $paging->getPerPage()
                )));
                $res['paging']['previous'] = sprintf("%s?%s", URL::to("news"), $query_string);
            }
            return Response::json($res);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function show($id)
    {
        try {
            $item = News::findOrFail($id)->toArray();
            $picture = Picture::find($item['picture_id']);
            if(!is_null($picture)){
                $item['picture'] = $picture->toArray();
                $item['picture']['link'] = URL::to('picture/'.$item['picture']['picture_link']);
            }
            $video = NewsVideo::find($item['video_id']);
            if(!is_null($video)){
                $item['video'] = $video->toArray();
                $item['video']['link'] = URL::to('news_video/'.$item['video']['video_link']);
                $item['video']['thumb'] = URL::to('news_video/'.$item['video']['id'].'.jpeg');
            }
            if($item['media_type']=='none'){
                $item['picture'] = array(
                    'link'=> URL::to('picture/default.jpg')
                );
            }

            if($this->_isset_field('like')){
                $item['like'] = Like::find($id)->toArray();
                if(!is_null(Auth::getUser())){
                    $item['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $item['id'])->count() > 0;
                }
            }

            if($this->_isset_field('comment')){
                $item['comment'] = Comment::find($id)->toArray();
            }

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
            DB::transaction(function() use (&$res){
                $validator = Validator::make(Input::all(), array(
                    'name'=> array('required'),
                    'message'=> array('required')
                ));
                if($validator->fails()){
                    throw new Exception($validator->errors()->first());
                }

                $news = new News();
                $news->name = Input::get('name');
                $news->message = Input::get('message');
                $news->media_type = 'none';

                if(Input::hasFile('media')){
                    $media = Input::file('media');
                    $ext = strtolower($media->getClientOriginalExtension());

                    $pic_allows = array('jpg', 'jpeg', 'png');
                    $video_allows = array('mp4');

                    if(in_array($ext, $pic_allows)){
                        $picture = new Picture();
                        list($width, $height, $type, $attr) = getimagesize($media->getRealPath());
                        $picture->size_x = $width;
                        $picture->size_y = $height;
                        $picture->save();

                        $name = $picture->id.'.'.$ext;
                        $media->move('picture', $name);
                        chmod('picture/'.$name, 0777);

                        $picture->picture_link = $name;
                        $picture->save();

                        $news->picture_id = $picture->id;
                        $news->media_type = 'picture';
                    }
                    else if(in_array($ext, $video_allows)){
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

                        $news->video_id = $news_video->id;
                        $news->media_type = 'video';
                    }
                    else {
                        throw new Exception('media type not allow');
                    }
                }
                $news->save();
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
                $item = News::findOrFail($id);

                if(Input::has('name')){
                    $item->name = Input::get('name');
                }

                if(Input::has('message')){
                    $item->message = Input::get('message');
                }

                if(Input::has('deleteMedia') && Input::get('deleteMedia')=='yes'){
                    $oldType = $item->media_type;
                    if($oldType=='picture'){
                        $picture = Picture::find($item->picture_id);
                        $oldPath = "picture/".$picture->picture_link;

                        $item->media_type = "none";
                        $picture->delete();
                    }
                    else if($oldType=='video') {
                        $video = NewsVideo::find($item->video_id);
                        $oldPath = "news_video/".$video->video_link;

                        $item->media_type = "none";
                        $video->delete();
                    }
                    $item->picture_id = 0;
                    $item->video_id = 0;
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

    public function editMedia($id)
    {
        try {
            $res = array();
            if(!Input::hasFile('media')){
                throw new Exception('editMedia file require media upload');
            }

            DB::transaction(function() use(&$res, $id){
                $item = News::findOrFail($id);

                $oldMedia = null;
                $oldPath = null;
                if($item->media_type=="picutre"){
                    $oldMedia = Picture::find($item->picture_id);
                    if(!is_null($oldMedia))
                        $oldPath = "picture/{$oldMedia->picture_link}";
                }
                else if($item->media_type=="video"){
                    $oldMedia = Video::find($item->picture_id);
                    if(!is_null($oldMedia))
                        $oldPath = "picture/{$oldMedia->video_link}";
                }

                $media = Input::file('media');
                $ext = strtolower($media->getClientOriginalExtension());

                $pic_allows = array('jpg', 'jpeg', 'png');
                $video_allows = array('mp4');

                if(in_array($ext, $pic_allows)){
                    $picture = new Picture();
                    list($width, $height, $type, $attr) = getimagesize($media->getRealPath());
                    $picture->size_x = $width;
                    $picture->size_y = $height;
                    $picture->save();

                    $name = $picture->id.'.'.$ext;
                    $media->move('picture', $name);
                    chmod('picture/'.$name, 0777);

                    $picture->picture_link = $name;
                    $picture->save();

                    $item->video_id = 0;
                    $item->picture_id = $picture->id;
                    $item->media_type = 'picture';
                }
                else if(in_array($ext, $video_allows)){
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

                    $item->picture_id = 0;
                    $item->video_id = $news_video->id;
                    $item->media_type = 'video';
                }
                else {
                    throw new Exception('media type not allow');
                }

                $item->save();
                $res = $item->toArray();
                if($item->media_type=='picture'){
                    $res['picture'] = $picture->toArray();
                    $res['picture']['link'] = URL::to("picture/".$picture->picture_link);
                }
                else {
                    $res['video'] = $news_video->toArray();
                    $res['video']['link'] = URL::to("news_video/".$news_video->video_link);
                }

                if(!is_null($oldMedia)){
                    @$oldMedia->delete();
                }
                if(!is_null($oldPath)){
                    @unlink($oldPath);
                }
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
            DB::transaction(function() use($id, &$res) {
                $news = News::findOrFail($id);
                $res = $news->toArray();
                $picture = Picture::find($news->picture_id);
                if(!is_null($picture)){
                    $path = 'picture/'.$picture->picture_link;

                    $picture->delete();
                    @unlink($path);
                }
                $news->delete();
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}