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
    public static function data($data)
    {
        return parent::json(array(
            'data'=> $data
        ));
    }

    public static function exception($e)
    {
        return parent::json(array(
            'error'=> array(
                'code'=> $e->getCode(),
                'type'=> get_class($e),
                'message'=> $e->getMessage(),
            )
        ));
    }
}