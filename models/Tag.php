<?php

namespace app\models;

class Tag extends ModelCommon
{
    public static function tableName(){
        return 'dl_tags';
    }
    const INACTIVE = 0;
    const ACTIVE = 1;

    public function getBookTags()
    {
        return $this->hasMany(BookTag::className(), ['tag_id' => 'id']);
    }
}
