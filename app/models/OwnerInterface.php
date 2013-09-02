<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 2/9/2556
 * Time: 11:01 น.
 * To change this template use File | Settings | File Templates.
 */

interface OwnerInterface {
    public function is_owner(User $user);
}