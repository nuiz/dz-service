<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 3/9/2556
 * Time: 12:07 น.
 * To change this template use File | Settings | File Templates.
 */

class UserPictureController extends BaseController {
    protected $_profile_path = 'picture/profile';
    protected $_profile_extension = 'jpg';

    public function _rules()
    {
        return array(
            'user.picture'=> array(
                'update'=> array('owner', 'admin')
            )
        );
    }

    public function index($user_id)
    {
        try {
            $user = User::findOrFail($user_id);

            $picture = file_exists($this->_profile_path.'/'.$user->id.'.'.$this->_profile_extension)?
                Image::make($this->_profile_path.'/'.$user->id.'.'.$this->_profile_extension):
                Image::make($this->_profile_path.'/default.jpg');
            $picture->resize(120, 120);
            $response = Response::make($picture, 200, array(
                'Content-Type'=> 'image/jpeg'
            ));
            return $response;
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function store($user_id)
    {
        try {
            $validator = Validator::make(Input::all(), array(
                'picture'=> array('image', 'required')
            ));
            if($validator->fails()){
                throw new Exception($validator->errors());
            }

            $user = User::findOrFail($user_id);
            $this->_validate_permission('user.picture', 'update', $user);

            //Input::file('photo')->move($this->_profile_path.'/'.$user_id.'.'.$this->_profile_extension);
            $picture = Image::make(Input::file('picture')->getRealPath());
            $picture->resize(120, 120)->save($this->_profile_path.'/'.$user_id.'.'.$this->_profile_extension);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }
}