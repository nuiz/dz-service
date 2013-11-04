<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 21/10/2556
 * Time: 10:06 น.
 * To change this template use File | Settings | File Templates.
 */

class PicController extends Controller {
    public function show($id){
        try {
            $pic = Picture::findOrFail($id);

            $picture = Image::make('picture/'.$pic->picture_link);
            $display = Input::has("display")? Input::get("display"): "default";
            if($display == "update"){
                $picture->resize(616, null, true);
                //if($picture->height > 396){
                    $picture->crop(616, 396);
                //}
            }
            else if($display == "custom"){
                $picture->resize(Input::get("size_x"), null, true);
                if(Input::has("size_y")){
                    $picture->crop(Input::get("size_x"), Input::get("size_y"));
                }
                else{
                    $picture->crop(Input::get("size_x"), $picture->height);
                }
            }
            $response = Response::make($picture, 200, array(
                'Content-Type'=> 'image/jpeg'
            ));
            return $response;
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}