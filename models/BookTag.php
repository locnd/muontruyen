<?php

namespace app\models;

class BookTag extends ModelCommon
{
    public static function tableName(){
        return 'dl_book_tag';
    }
    const INACTIVE = 0;
    const ACTIVE = 1;

    public function getTag()
    {
        return $this->hasOne(Tag::className(), ['id' => 'tag_id']);
    }
    public function getBook()
    {
        return $this->hasOne(Book::className(), ['id' => 'book_id']);
    }
}
