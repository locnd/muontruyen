<?php

namespace app\models;

class Read extends ModelCommon
{
    public static function tableName(){
        return 'dl_readed';
    }
    public function getBook()
    {
        return $this->hasOne(Book::className(), ['id' => 'book_id']);
    }
}
