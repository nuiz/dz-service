<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 24/8/2556
 * Time: 16:55 น.
 * To change this template use File | Settings | File Templates.
 */

abstract class DZEloquent extends Eloquent {
    #protected $_dz_type, $tables for extends Model

    public function save(array $options = array())
    {
        if(!isset($this->_dz_type))
            throw new Exception(__CLASS__.' is require property _dz_type');

        if(!$this->exists){
            $dz_object = new DzObject();
            $dz_object->type = $this->_dz_type;
            $dz_object->save();

            $like = new Like();
            $like->id = $dz_object->id;
            $like->save();

            $comment = new Comment();
            $comment->id = $dz_object->id;
            $comment->save();

            $this->id = $dz_object->id;
        }
        return parent::save($options);
    }

    public function delete()
    {
        $dz_object = DzObject::find($this->id);
        $dz_object->delete();

        $like = Like::find($this->id);
        $like->delete();
        UserLike::where('object_id', '=', $this->id)->delete();
        $comment = Comment::find($this->id);
        $comment->delete();
        UserComment::where('object_id', '=', $this->id)->delete();

        return parent::delete();
    }
}