<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 5/11/2556
 * Time: 15:41 น.
 * To change this template use File | Settings | File Templates.
 */

class GroupStudyController extends BaseController {
    public function index($group_id){
        $study = GroupStudy::where("group_id", "=", $group_id)->get();
        $data = array();
        $data = $study->toArray();

        /*
        if($study->count()>0){
            foreach($data as $key => $value){

            }
        }
        */
        return Response::json(array(
            "length"=> count($data),
            "data"=> $data
        ));
    }

    public function store($group_id){
        try {
            $group = Group::findOrFail($group_id);
            $study = new GroupStudy();
            $study->status = "add";
            $study->group_id = $group_id;

            $study->start = Input::get("start");
            $study->ori_start = Input::get("start");

            $study->end = Input::get("end");
            $study->ori_end = Input::get("end");

            $study->save();
            $study = GroupStudy::find($study->id);

            $json = $study->toArray();
            $json['group'] = $group->toArray();
            return Response::json($json);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}