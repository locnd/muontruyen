<?php

namespace app\models;

class Chapter extends ModelCommon
{
    public static function tableName(){
        return 'dl_chapters';
    }
    const INACTIVE = 0;
    const ACTIVE = 1;

    public function getImages()
    {
        return $this->hasMany(Image::className(), ['chapter_id' => 'id'])->where(array('dl_images.status'=>Image::ACTIVE))->orderBy(['stt' => SORT_ASC]);
    }
    public function getBook()
    {
        return $this->hasOne(Book::className(), ['id' => 'book_id']);
    }

    public function increa_stt() {
        $chapters = Chapter::find()->where(['>=', 'stt', $this->stt])
            ->andWhere(['<>', 'id', $this->id])
            ->andWhere(array('book_id' => $this->book_id))->all();
        foreach ($chapters as $chapter) {
            $chapter->stt++;
            $chapter->save();
        }
    }
}
