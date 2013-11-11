<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 5/11/2556
 * Time: 10:35 น.
 * To change this template use File | Settings | File Templates.
 */

class AdminCalendarController extends BaseController {
    public function index(){
        $groups_study = GroupStudy::all();
        $data = $groups_study->toArray();
        $groups_id = $groups_study->lists('group_id');
        if(count($groups_id)>0){
            $groups = Group::whereIn("id", $groups_id)->get();
            foreach($data as $key => $value){
                $buffer = $groups->filter(function($item) use($value){
                    if($item->id==$value['group_id'])
                        return true;
                });
                $data[$key]['group'] = $buffer->first()->toArray();
            }
        }
        $json = array(
            'length'=> count($data),
            'data'=> $data
        );
        return Response::json($json);
    }
}