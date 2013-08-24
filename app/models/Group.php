<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 20/8/2556
 * Time: 13:59 น.
 * To change this template use File | Settings | File Templates.
 */

class Group extends DZEloquent {
    protected $table = 'groups';
    protected $_dz_type = 'group';

    public function getUsers()
    {
        $users = array();
        return $users;
    }
}