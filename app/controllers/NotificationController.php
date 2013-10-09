<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 7/10/2556
 * Time: 10:25 น.
 * To change this template use File | Settings | File Templates.
 */

class NotificationController extends BaseController {
    public function index()
    {
        $user = Auth::getUser();
        try {
            if(is_null($user)){
                throw new Exception("auth is required this action");
            }
            $limit = Input::has("limit")? Input::get("limit"): null;
            $page = Input::has("page")? Input::get("page"): 0;
            $paging = Notification::orderBy('created_at', 'desc')
                ->where("user_id", "=", $user->id)
                ->paginate($limit);

            $collection = $paging->getCollection();
            $data = $collection->toArray();

            $res = array(
                'length'=> count($data),
                'total'=> $paging->getTotal(),
                'data'=> $data,
                'paging'=> array(
                    'length'=> $paging->getLastPage(),
                    'current'=> $paging->getCurrentPage(),
                    'limit'=> $paging->getPerPage()
                ),
            );
            if($paging->getCurrentPage() < $paging->getLastPage()){
                $query_string = http_build_query(array_merge($_GET, array(
                    "page"=> $paging->getCurrentPage()+1,
                    "limit"=> $paging->getPerPage()
                )));
                $res['paging']['next'] = sprintf("%s?%s", URL::to("news"), $query_string);
            }
            if($paging->getCurrentPage() > 1){
                $query_string = http_build_query(array_merge($_GET, array(
                    "page"=> $paging->getCurrentPage()-1,
                    "limit"=> $paging->getPerPage()
                )));
                $res['paging']['previous'] = sprintf("%s?%s", URL::to("news"), $query_string);
            }
            return Response::json($res);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}