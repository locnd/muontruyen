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
    public function get_image() {
        if(empty($this->image)) {
            $this->image = 'error.jpg';
        }
        if($this->image == 'error.jpg') {
            if(\Yii::$app->params['use_image_source']) {
                return $this->image_source;
            }
            return \Yii::$app->urlManager->createAbsoluteUrl(['/']).'uploads/books/'.$this->image;
        }
        return \Yii::$app->urlManager->createAbsoluteUrl(['/']).'uploads/books/'.$this->book->slug.'/chap'.$this->chapter->id.'/'.$this->image;
    }
}
