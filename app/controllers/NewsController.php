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
            $paging = News::orderBy('created_at')->paginate($limit);
            $news = $paging->getCollection();
            $data = $news->toArray();
            $pictures_id = $news->lists('picture_id');
            $news_id = $news->lists('id');

            if(count($pictures_id)>0){
                $pictures = Picture::whereIn('id', $pictures_id)->get();
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
                if($this->_isset_field('like')){
                    $data[$key]['like'] = $likes->filter(function($item) use ($value){
                        return $item->id == $value['id'];
                    })->first()->toArray();
                    if(!is_null($user)){
                        $data[$key]['like']['is_liked'] = UserLike::where('user_id', '=', $user->id)->where('object_id', '=', $value['id']);
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
            $item['picture'] = Picture::findOrFail($item['picture_id'])->toArray();
            $item['picture']['link'] = URL::to('picture/'.$item['picture']['picture_link']);

            if($this->_isset_field('like')){
                $item['like'] = Like::find($id)->toArray();
                if(!is_null(Auth::getUser())){
                    $item['like']['is_liked'] = UserLike::where('user_id', '=', Auth::getUser()->id)->where('object_id', '=', $item['id']);
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

                if(Input::hasFile('picture')){
                    $picture = new Picture();
                    list($width, $height, $type, $attr) = getimagesize(Input::file('picture')->getRealPath());
                    $picture->size_x = $width;
                    $picture->size_y = $height;
                    $picture->save();

                    $name = $picture->id.'.'.Input::file('picture')->getClientOriginalExtension();
                    Input::file('picture')->move('picture', $name);
                    chmod('picture/'.$name, 0777);

                    $picture->picture_link = $name;
                    $picture->save();
                    $news->picture_id = $picture->id;
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
                    $item = Input::get('name');
                }

                if(Input::has('message')){
                    $item->name = Input::get('message');
                }

                if(Input::hasFile('picture')){
                    $picture = new Picture();
                    list($width, $height, $type, $attr) = getimagesize(Input::file('picture')->getRealPath());
                    $picture->size_x = $width;
                    $picture->size_y = $height;
                    $picture->save();

                    $name = $picture->id.'.'.Input::file('picture')->getClientOriginalExtension();
                    Input::file('picture')->move('picture', $name);
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