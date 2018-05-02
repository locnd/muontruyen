<?php

namespace app\models;

class Group extends ModelCommon
{
    public static function tableName(){
        return 'dl_groups';
    }
    const INACTIVE = 0;
    const ACTIVE = 1;

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
    public function getFollows()
    {
        return $this->hasMany(Follow::className(), ['group_id' => 'id']);
    }
}
