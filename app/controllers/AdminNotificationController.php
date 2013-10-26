<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 16/10/2556
 * Time: 12:00 น.
 * To change this template use File | Settings | File Templates.
 */

class AdminNotificationController extends BaseController {
    public function index(){
        $limit = null;
        if(isset($_GET['limit'])){
            $limit = $_GET['limit'];
        }
        $type = null;
        if(isset($_GET['type'])){
            $type = $_GET['type'];
        }
        if(is_null($type)){
            $nfPaginate = DzObject::whereIn("type", array("user_activity", "register_upgrade", "register_group"))->orderBy("created_at", "desc")->paginate($limit);
        }
        else {

            $nfPaginate = DzObject::where("type", array("user_activity", "register_upgrade", "register_group"))->orderBy("created_at", "desc")->paginate($limit);
        }

        $collection = $nfPaginate->getCollection();
        return Response::json(array(
            "length"=> $collection->count(),
            "total"=> $nfPaginate->getTotal(),
            "data"=> $collection->toArray()
        ));
    }
}