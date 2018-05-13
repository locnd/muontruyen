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
            $this->save();
        }
        if($this->image == 'error.jpg') {
            if(\Yii::$app->params['use_image_source']) {
                $image = $this->image_source;
            } else {
                $image = \Yii::$app->urlManager->createAbsoluteUrl(['/']).'uploads/books/'.$this->image;
            }
        } else {
            $image = \Yii::$app->urlManager->createAbsoluteUrl(['/']).'uploads/books/'.$this->chapter->book->slug.'/chap'.$this->chapter->id.'/'.$this->image;
        }
        return $image;
    }
}
