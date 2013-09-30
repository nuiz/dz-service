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

        $pictures_id = $groups->lists('picture_id');
        if(count($pictures_id) > 0){
            $pictures = Picture::whereIn('id', $pictures_id)->get();
        }
        foreach($data as $key => $value){
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

            if($this->_isset_field('users')){
                $users = UserGroup::where('group_id', '=', $group_id)->with('user')->get();
                $response['users'] = array('data'=> array(), 'length'=> 0);
                $response['users']['data'] = $users->toArray();
                $response['users']['length'] = count($response['users']['data']);
            }

            $data = $group->toArray();
            $picture = Picture::find($data['picture_id']);
            if(is_null($picture))
                $data['picture'] = array('link'=> URL::to("picture/default.jpg"));
            else
                $data['picture'] = $picture->toArray();

            return Response::json($data);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($class_id)
    {
        try {
            $response = array();
            DB::transaction(function() use(&$response, $class_id){
                $validator = Validator::make(Input::all(), array(
                    'name'=> array('required'),
                    'description'=> array('required'),
                ));
                if($validator->fails())
                    throw new Exception($validator->messages()->first());

                $group = new Group();
                $group->name = Input::get('name');
                $group->description = Input::get('description');
                $group->class_id = $class_id;

                if(Input::hasFile('picture')){
                    $picFile = Input::file('picture');
                    $ext = strtolower($picFile->getClientOriginalExtension());
                    $pic_allows = array('jpg', 'jpeg', 'png');

                    if(!in_array($ext, $pic_allows)){
                        throw new Exception("Picture upload allow jpg,jpeg,png only");
                    }

                    $picture = new Picture();
                    list($width, $height, $type, $attr) = getimagesize($picFile->getRealPath());
                    $picture->size_x = $width;
                    $picture->size_y = $height;
                    $picture->save();

                    $name = $picture->id.'.'.$ext;
                    $picFile->move('picture', $name);
                    chmod('picture/'.$name, 0777);

                    $picture->picture_link = $name;
                    $picture->save();

                    $group->picture_id = $picture->id;
                }

                $group->save();
                $response = $group->toArray();
            });
            return Response::json($response);
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
                $response = $group->toArray();
                $group->delete();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}