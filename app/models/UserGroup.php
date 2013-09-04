<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 3/9/2556
 * Time: 10:34 น.
 * To change this template use File | Settings | File Templates.
 */

class UserGroup extends DZEloquent {
    protected $table = 'users_groups';
    protected $_dz_type = 'group';

    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }

    public function group()
    {
        return $this->belongsTo('Group', 'group_id');
    }
}