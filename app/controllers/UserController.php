<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 7/8/2556
 * Time: 15:26 น.
 * To change this template use File | Settings | File Templates.
 */

use Extend\Laravel;

class UserController extends BaseController {

    public function index($id = null)
    {
        return print_r($id, true);
    }

    public function show($id){
        try {
            $user = User::findOrFail($id);
            return Response::json($user);
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return Response::exception($e);
        }
    }
}