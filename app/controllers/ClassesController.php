<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nuiz
 * Date: 16/9/2556
 * Time: 9:43 น.
 * To change this template use File | Settings | File Templates.
 */

class ClassesController extends BaseController {
    public function _rules()
    {
        return array();
    }

    public function index()
    {
        $classes = Classes::all();
        $data = $classes->toArray();
        if($this->_isset_field('groups')){
            $groups = Group::all();
        }

        foreach($data as $key => $value){
            $logo = $value['logo'];
            $data[$key]['logo_link'] = URL::to("lesson_logo/Dancer{$logo}Ip5@2x.png");


            if($this->_isset_field('groups')){
                $buffer = $groups->filter(function($item) use($value){
                    if($item->class_id == $value['id'])
                        return true;
                });
                $data[$key]['groups'] = array(
                    'length'=> $buffer->count(),
                    'data'=> $buffer->toArray()
                );
            }
        }
        return Response::json(array(
            'length'=> $classes->count(),
            'data'=> $data
        ));
    }

    public function store()
    {
        try {
            $classed = new Classes();
            $validator = Validator::make(Input::all(), array(
                'name'=> 'required',
                'color'=> 'required',
                'logo'=> 'required'
            ));
            if($validator->fails()){
                throw new Exception($validator->errors());
            }
            $classed->name = Input::get('name');
            $classed->logo = Input::get('logo');
            $classed->color = Input::get('color');

            $classed->save();
            return  Response::json($classed);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function show($id){
        try {
            $classed = Classes::findOrFail($id);
            $data = $classed->toArray();
            $logo = $data['logo'];
            $data['logo_link'] = URL::to("lesson_logo/Dancer{$logo}Ip5@2x.png");
            return Response::json($data);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function update($id)
    {
        try {
            $res = array();
            DB::transaction(function() use(&$res, $id){
                $item = Classes::findOrFail($id);

                if(Input::has('name')){
                    $item->name = Input::get('name');
                }

                if(Input::has('logo')){
                    $item->logo = Input::get('logo');
                }

                if(Input::has('color')){
                    $item->color = Input::get('color');
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

    public function destroy($id){
        try {
            $response = array();
            DB::transaction(function() use(&$response, $id){
                $classed = Classes::findOrFail($id);
                $groups = Group::where("class_id", "=", $id)->get();
                $groups_id = $groups->lists("id");

                $response = $classed->toArray();
                $classed->delete();

                Group::where("class_id", "=", $id);
                if(count($groups_id)){
                    //delete register group
                    RegisterGroup::whereIn("group_id", $groups_id)->delete();

                    //delete joined group
                    UserGroup::whereIn("group_id", $groups_id)->delete();
                }
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}