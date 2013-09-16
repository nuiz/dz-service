<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nuiz
 * Date: 16/9/2556
 * Time: 9:43 น.
 * To change this template use File | Settings | File Templates.
 */

class ClassesController extends BaseController {
    public function __rules()
    {
        return array();
    }

    public function index()
    {
        $classes = Classes::all();
        $data = array();

        $groups = Group::all();
        foreach($classes as $key => $value){
            $data[$key] = $value->toArray();
            $groupsFillter = $groups->filter(function($item) use($value){
                if($item->class_id==$value->id)
                    return true;
            });
            $data[$key]['groups'] = array();
            $data[$key]['groups'] = array(
                'data'=> $groupsFillter->toArray(),
                'length'=> $groupsFillter->count()
            );
        }
        return Response::json(array(
            'length'=> $classes->count(),
            'data'=> $data
        ));
    }
}