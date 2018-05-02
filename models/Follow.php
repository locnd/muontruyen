<?php

namespace app\models;

class Follow extends ModelCommon
{
    public static function tableName(){
        return 'dl_follows';
    }
    const READ = 0;
    const UNREAD = 1;

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
    public function getBook()
    {
        return $this->hasOne(Book::className(), ['id' => 'book_id']);
    }
    public function getGroup()
    {
        return $this->hasOne(Group::className(), ['id' => 'group_id']);
    }
}
