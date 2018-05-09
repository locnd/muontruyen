<?php

namespace app\models;

class Book extends ModelCommon
{
    public static function tableName(){
        return 'dl_books';
    }
    const INACTIVE = 0;
    const ACTIVE = 1;

    public function getChapters()
    {
        return $this->hasMany(Chapter::className(), ['book_id' => 'id'])->where(array('status'=>Chapter::ACTIVE))->orderBy(['stt' => SORT_DESC, 'id' => SORT_DESC]);
    }
    public function getServer()
    {
        return $this->hasOne(Server::className(), ['id' => 'server_id']);
    }
    public function getFollows()
    {
        return $this->hasMany(Follow::className(), ['book_id' => 'id']);
    }
    public function getBookTags()
    {
        return $this->hasMany(BookTag::className(), ['book_id' => 'id']);
    }

    public function save_tags($tag_ids) {
        foreach ($this->bookTags as $book_tag) {
            $book_tag->delete();
        }
        $tags_arr = explode(',',$tag_ids);
        foreach ($tags_arr as $tag_id) {
            if(empty($tag_id)) {
                continue;
            }
            $book_tag = new BookTag();
            $book_tag->book_id = $this->id;
            $book_tag->tag_id = $tag_id;
            $book_tag->save();
        }
    }

    public function add_tag($tag_name) {
        $tag = Tag::find()->where(array('name'=>$tag_name))->one();
        if(empty($tag)) {
            $tag = new Tag();
            $tag->name = $tag_name;
            $tag->slug = generate_key($tag_name);
            $tag->status = Tag::ACTIVE;
            $tag->save();
        }
        if(BookTag::find()->where(array('book_id'=>$this->id, 'tag_id'=>$tag->id))->count() > 0) {
            return true;
        }
        $book_tag = new BookTag();
        $book_tag->book_id = $this->id;
        $book_tag->tag_id = $tag->id;
        $book_tag->save();
    }

    public function get_image() {
        if(empty($this->image)) {
            $this->image = 'default.jpg';
        }
        if($this->image == 'default.jpg') {
            if(\Yii::$app->params['use_image_source']) {
                return $this->image_source;
            }
            return \Yii::$app->urlManager->createAbsoluteUrl(['/']).'uploads/books/'.$this->image;
        }
        return \Yii::$app->urlManager->createAbsoluteUrl(['/']).'uploads/books/'.$this->slug.'/'.$this->image;
    }
}
