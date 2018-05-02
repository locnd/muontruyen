<?php

namespace app\models;

class Image extends ModelCommon
{
    public static function tableName(){
        return 'dl_images';
    }
    const INACTIVE = 0;
    const ACTIVE = 1;
    public function getChapter()
    {
        return $this->hasOne(Chapter::className(), ['id' => 'chapter_id']);
    }
}
