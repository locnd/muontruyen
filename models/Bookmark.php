<?php

namespace app\models;

class Bookmark extends ModelCommon
{
    public static function tableName(){
        return 'dl_bookmarks';
    }
    const INACTIVE = 0;
    const ACTIVE = 1;

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
    public function getBook()
    {
        return $this->hasOne(Book::className(), ['id' => 'book_id']);
    }
    public function getChapter()
    {
        return $this->hasOne(Chapter::className(), ['id' => 'chapter_id']);
    }

}
