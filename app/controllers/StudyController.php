<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 5/11/2556
 * Time: 11:28 น.
 * To change this template use File | Settings | File Templates.
 */

class StudyController extends BaseController {
    public function update($id){
        try {
            $study = GroupStudy::findOrFail($id);
            if(Input::has("status")){
                $study->status = Input::get("status");

                if(Input::get("status")=="move"){
                    $study->start = Input::get("start");
                    $study->end = Input::get("end");
                }
            }
            $study->save();
            return Response::json($study);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}