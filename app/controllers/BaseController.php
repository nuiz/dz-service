<?php

class BaseController extends Controller {
    public function __construct()
    {
        if(!is_null(Request::header('X-Auth-Token'))){
            $this->beforeFilter('auth.token');
        }
    }

    protected $_fields = null;
    protected function _fields()
    {
        if(is_null($this->_fields)){
            $this->_fields = array();
            if(Input::has('fields'))
                $this->_fields = explode(',', Input::get('fields'));
            foreach($this->_fields as $key => $value){
                $this->_fields[$key] = trim($value);
            }
        }
        return $this->_fields;
    }

    protected function _isset_field($name)
    {
        return array_search($name, $this->_fields())!==false;
    }

    protected function _auth_owner($item)
    {
        $user = Auth::getUser();
        if(!$user)
            return false;
        return $item->is_owner($user);
    }

    protected function _auth_admin()
    {
        $user = Auth::getUser();
        if(!$user)
            return false;
        return $user->type == 'admin';
    }

    protected function _validate_permission($resource, $action, $item = null)
    {
        $rules = $this->_rules();

        if(!isset($rules[$resource]))
            return true;
        if(!isset($rules[$resource][$action]))
            return true;

        $rule = $rules[$resource][$action];
        if(array_search('owner', $rule)!==false){
            if(!$this->_auth_owner($item))
                throw new Exception("You not have permission for this action");
        }
        else if(array_search('admin', $rule)!==false){
            if(!$this->_auth_admin())
                throw new Exception("You not have permission for this action");
        }
    }

    protected function _require_authenticate()
    {
        if(!Auth::getUser())
            throw new Exception("Authentication is required to request this resource");
    }

    /*
    public function callFilter($route, $filter, $request, $parameters = array())
    {

        return parent::callFilter($route, $filter, $request, $parameters);
        //
        try {
            parent::callFilter($route, $filter, $request, $parameters);
        }
        catch (Tappleby\AuthToken\Exceptions\NotAuthorizedException $e) {
            Auth::logout();
        }
        //
    }
    */

	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}
}