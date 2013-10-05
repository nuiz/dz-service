<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 7/8/2556
 * Time: 16:21 น.
 * To change this template use File | Settings | File Templates.
 */

namespace Extend\Laravel;

class Response extends \Illuminate\Support\Facades\Response {
    public static function json($data = array(), $status = 200, array $headers = array())
    {
        $res = parent::json($data);
        $res->headers->add(array("Content-Length"=> strlen($res->getContent())));
        return $res;
    }

    public static function exception($e)
    {
        return parent::json(array(
            'error'=> array(
                'code'=> $e->getCode(),
                'type'=> get_class($e),
                'message'=> $e->getMessage(),
                'file'=> $e->getFile(),
                'line'=> $e->getLine(),
                'trace'=> $e->getTrace()
            )
        ));
    }

    public static function pre($data)
    {
        return '<pre>'.
            print_r($data, true).
            '</pre>';
    }
}