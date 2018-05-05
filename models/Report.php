<?php

namespace app\models;

class Report extends ModelCommon
{
    public static function tableName(){
        return 'dl_reports';
    }
    const STATUS_NEW = 0;
    const STATUS_FIXED = 1;

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
    public function getChapter()
    {
        return $this->hasOne(Chapter::className(), ['id' => 'chapter_id']);
    }
    public function getBook()
    {
        return $this->hasOne(Book::className(), ['id' => 'book_id']);
    }
}
