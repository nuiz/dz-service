<?php
/**
 * Created by JetBrains PhpStorm.
 * User: P2DC
 * Date: 7/8/2556
 * Time: 15:26 น.
 * To change this template use File | Settings | File Templates.
 */

class UserController extends BaseController implements ResourceInterface {

    public function _rules()
    {
        return array(
            'user'=> array(
                'update'=> array('owner', 'admin'),
            ),
            'user.setting'=> array(
                'get'=> array('owner', 'admin'),
                'update'=> array('owner', 'admin')
            ),
            'user.type'=> array(
                'update'=> array('admin')
            )
        );
    }

    public function index()
    {
        try {
            $users = User::where("type", "!=", "admin")->get();
            $data = $users->toArray();

            if($this->_isset_field('groups') && $users->count() > 0){
                $groups = Group::all();
                $users_groups = UserGroup::all();
                $classes = Classes::all();
                $fnG = (function($user_id) use($groups, $users_groups, $classes){
                    $ugs = $users_groups->filter(function($item) use($user_id){
                        if($item->user_id == $user_id)
                            return true;
                    });
                    $gs = $groups->filter(function($item) use($ugs){
                        foreach($ugs as $key => $value){
                            if($value->group_id == $item->id)
                                return true;
                        }
                    });
                    $gData = $gs->toArray();

                    foreach($gData as $key => $value){
                        $buffer = $classes->filter(function($item) use($value){
                            if($item->id == $value['class_id'])
                                return true;
                        });
                        if($buffer->count() > 0){
                            $gData[$key]['class'] = $buffer->first()->toArray();
                        }
                    }
                    return $gData;
                });
                foreach($data as $key => $value){
                    $data[$key]['groups'] = array('data'=> $fnG($value['id']));
                    $data[$key]['groups']['length'] = count($data[$key]['groups']['data']);
                }
            }
            return Response::json(array(
                'data'=> $data,
                'length'=> count($data)
            ));
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function show($id){
        try {
            $user = User::findOrFail($id);
            $response = $user->attributesToArray();

            $fields = $this->_fields();

            //owner or admin can access
            if($this->_isset_field('setting')){
                $this->_validate_permission('user.setting', 'get', $user);

                $user_setting = UserSetting::find($id);
                if(is_null($user_setting)){
                    $user_setting = new UserSetting();
                    $user_setting->id = $id;
                    $user_setting->save();
                    $user_setting = UserSetting::find($id);
                }
                $response['setting'] = $user_setting->attributesToArray();
            }

            /*
             * user facebook_id แทนที่ได้ username
             * เฉพาะกิจ รอ update app แล้วค่อยเปลี่ยนเป็นเหมือนเดิม
             */
            $response['facebook_id'] = $response['username'];
            return Response::json($response);
        }
        catch (Exception $e){
            return Response::exception($e);
        }
    }

    //admin only can store user
    public function store(){
        try {
            $response = null;
            DB::transaction(function() use (&$response){
                $validator = Validator::make(Input::all(), array(
                    'email'=> array('email', 'required'),
                    'password'=> array('min: 4', 'max: 16', 'required'),
                ));

                $attributes = Input::all();
                $attributes['password'] = Hash::make($attributes['password']);
                $attributes['type'] = 'normal';
                $user = new User();
                $user->setRawAttributes($attributes);
                $user->save();

                $response = $user->attributesToArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    //admin only can update 'type' field
    //user update 'type' response exception
    public function update($id){
        try {
            $response = null;
            DB::transaction(function() use ($id, &$response){
                $validator = Validator::make(Input::all(), array(
                    'type'=> 'in:normal,member',
                ));

                if($validator->fails())
                    throw new Exception($validator->errors());

                $user = User::findOrFail($id);

                //$this->_validate_permission('user', 'update', $user);
                if(Input::has('type')){
                    //$this->_validate_permission('user.type', 'update', $user);
                    $user->setAttribute('type', Input::get('type'));
                }

                if(Input::has('member_timeout')){
                    $user->member_timeout = Input::get("member_timeout");
                }

                if(Input::has('first_name')){
                    $user->first_name = Input::get('first_name');
                }

                if(Input::has('phone_number')){
                    $user->last_name = Input::get('phone_number');
                }

                if(Input::has('birth_date')){
                    $user->last_name = Input::get('birth_date');
                }

                if(Input::has('gender')){
                    $user->last_name = Input::get('gender');
                }

                if(Input::has('email_show')){
                    $user->email_show = Input::get('email_show');
                }

                if(Input::has('phone_show')){
                    $user->phone_show = Input::get('phone_show');
                }
                $user->save();

                $response = $user->toArray();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }

    public function destroy($id)
    {
        try {
            $response = array();
            DB::transaction(function() use($id, &$response) {
                $user = User::findOrFail($id);
                $user_setting = UserSetting::find($id);
                $response = $user->toArray();

                $user->delete();
                if($user_setting)
                    $user_setting->delete();

                $users_groups = UserGroup::where('user_id', '=', $id)->get();
                $groups_id = array_unique($users_groups->lists('group_id'));

                UserGroup::where('user_id', '=', $id)->delete();
                if(count($groups_id)>0){
                    $groups = Group::whereIn('id', $groups_id)->get();
                    foreach($groups as $key => $group) {
                        $group->user_length = UserGroup::where('group_id', '=', $group->id)->count();
                        $group->save();
                    }
                }

                $users_comments = UserComment::where('user_id', '=', $id)->get();
                $comments_id = array_unique($users_comments->lists('object_id'));

                UserComment::where('user_id', '=', $id)->delete();
                if(count($comments_id)>0){
                    $comments = Comment::whereIn('id', $comments_id)->get();
                    foreach($comments as $key => $comment) {
                        $comment->length = UserComment::where('object_id', '=', $comment->id)->count();
                        $comment->save();
                    }
                }

                $users_likes = UserLike::where('user_id', '=', $id)->get();
                $likes_id = array_unique($users_likes->lists('object_id'));

                UserLike::where('user_id', '=', $id)->delete();
                if(count($likes_id)>0){
                    $likes = Like::whereIn('id', $likes_id)->get();
                    foreach($likes as $key => $like) {
                        $like->length = UserLike::where('object_id', '=', $like->id)->count();
                        $like->save();
                    }
                }

                $users_activities = UserActivity::where('user_id', '=', $id)->get();
                $activities_id = array_unique($users_activities->lists('activity_id'));

                UserActivity::where('user_id', '=', $id)->delete();
                if(count($activities_id)>0){
                    $activities = Activity::whereIn('id', $activities_id)->get();
                    foreach($activities as $key => $activity) {
                        $activity->user_length = UserActivity::where('activity_id', '=', $activity->id)->count();
                        $activity->save();
                    }
                }

                RegisterUpgrade::where('user_id', '=', $id)->delete();
                RegisterGroup::where('user_id', '=', $id)->delete();
                Notification::where('user_id', '=', $id)->delete();
            });
            return Response::json($response);
        }
        catch (Exception $e) {
            return Response::exception($e);
        }
    }

    public function postRegister()
    {
        try {
            $data = array();
            DB::transaction(function() use (&$data){
                $validator = Validator::make(Input::all(), array(
                    'email'=> array('email', 'required'),
                    'username'=> array('min: 4', 'max: 16', 'required'),
                    'password'=> array('min: 4', 'max: 16', 'required')
                ));
                if ($validator->fails())
                    throw new Exception($validator->errors());

                if(User::where('email', '=', Input::get('email'))->count() > 0){
                    throw new Exception('email duplicate');
                }
                if(User::where('username', '=', Input::get('username'))->count() > 0){
                    throw new Exception('username duplicate');
                }

                $email = $_POST['email'];
                $password = $_POST['password'];

                $md5_password = Hash::make($password);
                $user = new User();
                $user->email = $email;
                $user->email_show = $email;
                $user->password = $md5_password;
                $user->username = Input::get('username');
                if(Input::has('gender'))
                    $user->gender = Input::get('gender');
                if(Input::has('birth_date'))
                    $user->birth_date = 'normal';
                if(Input::has('phone_number')){
                    $user->phone_number = Input::get('phone_number');
                    $user->phone_show = Input::get('phone_show');
                }

                $user->type = 'normal';
                $user->save();
                
                $data['user'] = $user->toArray();
                
                $authToken = AuthToken::create($user);
                $publicToken = AuthToken::publicToken($authToken);
                
                $data['token'] = $publicToken;
            });
            return Response::json($data);
        }
        catch (Exception $e) {
            DB::rollBack();
            return Response::exception($e);
        }
    }
}
