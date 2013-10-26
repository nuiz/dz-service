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
                $top = ($picture->height - 396)/2;
                if($top > 0){
                    $picture->crop(616, 396);
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