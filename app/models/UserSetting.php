<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 24/8/2556
 * Time: 12:20 น.
 * To change this template use File | Settings | File Templates.
 */

class UserSetting extends Eloquent implements OwnerInterface {
    protected $table = 'users_setting';

    public function is_owner(User $user)
    {
        return $this->id===$user->id;
    }
}