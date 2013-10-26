<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 22/10/2556
 * Time: 16:37 น.
 * To change this template use File | Settings | File Templates.
 */

class SettingController extends BaseController {
    public function index(){
        try {
            $data = array();
            $setting = Setting::all();
            if($setting->count() == 0){
                $setting = new Setting();
                $setting->save();
                $setting = Setting::find($setting->id);
            }
            else {
                $setting = $setting->first();
            }
            $data = $setting->toArray();
            $img = Picture::find($data['picture_id']);
            if(!is_null($img)){
                $data['picture'] = $img->toArray();
                $data['picture']['link'] = URL::to('picture/'.$img->picture_link);
            }
            return Response::json($data);
        } catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store(){
        try {
            $res = array();
            DB::transaction(function() use(&$res){
                $setting = Setting::all();
                if($setting->count() == 0){
                    $setting = new Setting();
                    $setting->save();
                    $setting = Setting::find($setting->id);
                }
                else {
                    $setting = $setting->first();
                }

                if(Input::has("phone")){
                    $setting->phone = Input::get("phone");
                }
                if(Input::has("email")){
                    $setting->email = Input::get("email");
                }
                if(Input::has("line")){
                    $setting->line = Input::get("line");
                }
                if(Input::has("facebook")){
                    $setting->facebook = Input::get("facebook");
                }
                if(Input::has("twitter")){
                    $setting->twitter = Input::get("twitter");
                }
                if(Input::has("youtube")){
                    $setting->youtube = Input::get("youtube");
                }
                if(Input::has("website")){
                    $setting->website = Input::get("website");
                }

                if(Input::hasFile("picture")){
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

                    $setting->picture_id = $picture->id;
                }
                $setting->save();
                $data = $setting->toArray();
                $img = Picture::find($data['picture_id']);
                if(!is_null($img)){
                    $data['picture'] = $img->toArray();
                    $data['picture']['link'] = URL::to('picture/'.$img->picture_link);
                }
                $res = $data;
            });
            return Response::json($res);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}