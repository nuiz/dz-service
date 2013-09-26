<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 19/9/2556
 * Time: 14:17 น.
 * To change this template use File | Settings | File Templates.
 */

class Picture extends DZEloquent {
    protected $_dz_type = 'picture';
    protected $table = 'pictures';

    public function delete()
    {
        @unlink("picture/".$this->picture_link);
        return parent::delete();
    }
}