<?php

namespace app\models;

class Device extends ModelCommon
{
    public static function tableName(){
        return 'dl_devices';
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
