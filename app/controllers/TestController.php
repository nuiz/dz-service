<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 17/9/2556
 * Time: 12:51 น.
 * To change this template use File | Settings | File Templates.
 */

class TestController extends Controller {

    public function index()
    {
        return Response::json(Input::all());
    }

    public function store()
    {
        return Response::json(Input::all());
    }

    public function update($id)
    {
        return Response::json(file_get_contents('php://input'));
    }

    public function destroy($id)
    {
        return Response::json(Input::getContent());
    }
}