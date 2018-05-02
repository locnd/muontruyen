<?php

namespace app\models;

use yii\web\IdentityInterface;

class User extends ModelCommon implements IdentityInterface
{
    public static function tableName(){
        return 'dl_users';
    }
    const INACTIVE = 0;
    const ACTIVE = 1;

    public $salt = 'locnd';
    public $auth_key;

    public function getGroups()
    {
        return $this->hasMany(Group::className(), ['user_id' => 'id'])->where(array('dl_groups.status'=>Group::ACTIVE));
    }
    public function getFollows()
    {
        return $this->hasMany(Follow::className(), ['user_id' => 'id']);
    }
    public function getReads()
    {
        return $this->hasMany(Read::className(), ['user_id' => 'id']);
    }
    public function rules()
    {
        return [
            [['username', 'name', 'email', 'password'], 'required'],
            ['email', 'email', 'message' => 'Email không đúng.'],
        ];
    }
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }
    public function getId()
    {
        return $this->getPrimaryKey();
    }
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function login($data) {
        $errors = array();
        $data['username'] = strtolower($data['username']);
        if(empty($data['username'])) {
            $errors['username'] = 'Tên đăng nhập không được để trống';
        }
        if(empty($data['password'])) {
            $errors['password'] = 'Mật khẩu không được để trống';
        }
        if(!empty($errors)) {
            return $errors;
        }
        $user = User::find()->where(array('username' => $data['username']))->one();
        if(empty($user)) {
            return array('username'=>'Tài khoản không tồn tại');
        }
        if($user->status == self::INACTIVE) {
            return array('username'=>'Tài khoản không khả dụng');
        }
        if(!empty($user->deleted_at)) {
            return array('username'=>'Tài khoản đã bị xoá');
        }
        if(!empty($data['is_admin']) && $user->is_admin != 1) {
            return array('username'=>'Tài khoản không phải quản trị viên');
        }
        if($user->password != md5($this->salt.'_'.$data['password'])) {
            return array('password'=>'Mật khẩu không đúng');
        }
        if(!empty($data['is_web'])) {
            $user->token = $this->randomToken();
            while (User::find()->where(array('token' => $user->token))->count() > 0) {
                $user->token = $this->randomToken();
            }
        }
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();
        return $user;
    }

    public function randomToken($length=48) {
        $token = '';
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $token .= $alphabet[$n];
        }
        return $token;
    }

    public function createUser($data) {
        $errors = array();
        $user = new User();
        $user->username = $data['username'];
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        if ($user->validate()) {
        } else {
            $errors = $user->errors;
        }
        if(!empty($errors)) {
            foreach ($errors as $key => $value) {
                $errors[$key] = str_replace('cannot be blank','không được để trống',$value[0]);
            }
        }
        if(empty($data['password2'])) {
            $errors['password2'] = 'Mật khẩu xác nhận không được để trống.';
        }elseif(!empty($data['password2']) && $data['password'] != $data['password2']) {
            $errors['password2'] = 'Mật khẩu xác nhận không đúng';
        }
        if(empty($errors['username'])) {
            if(strlen($user->username) < 4) {
                $errors['username'] = 'Tên đăng nhập có ít nhất 4 ký tự.';
            }elseif (preg_match('/[^a-z0-9-_.]/', $user->username)) {
                $errors['username'] = 'Tên đăng nhập chỉ bao gồm chữ viết thường, số và các kí tự -_.';
            }elseif(!empty($user->username) && User::find()->where(array('username'=> $user->username))->count() > 0) {
                $errors['username'] = 'Tên đăng nhập này đã tồn tại.';
            }
        }
        if(empty($errors['email'])) {
            if (!empty($user->email) && User::find()->where(array('email'=> $user->email))->count() > 0) {
                $errors['email'] = 'Email này đã tồn tại.';
            }elseif (preg_match('/[^a-z0-9@_.]/', $user->email)) {
                $errors['email'] = 'Email chỉ bao gồm chữ viết thường, số và các kí tự @_.';
            }
        }
        if(!empty($errors)) {
            return $errors;
        }
        $user->password = md5($this->salt.'_'.$data['password']);
        $user->status = self::ACTIVE;
        $user->token = $this->randomToken();
        while(User::find()->where(array('token'=> $user->token))->count() > 0) {
            $user->token = $this->randomToken();
        }
        $user->save();
        return $user;
    }
}
