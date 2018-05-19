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
    public function getLastChapter()
    {
        return $this->hasOne(Chapter::className(), ['book_id' => 'id'])->where(array('status'=>Chapter::ACTIVE))->orderBy(['stt' => SORT_DESC, 'id' => SORT_DESC]);
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
            if($book_tag->tag->type == 0) {
                $book_tag->delete();
            }
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
        $type = 0;
        if(strpos($tag_name, 'Author:') !== false) {
            $tag_name = str_replace('Author:','',$tag_name);
            $type = 1;
        }
        if($tag_name == 'Chưa cập nhật') {
            return true;
        }
        $tag_name = str_replace('Đ','đ',$tag_name);
        $tag = Tag::find()->where(array('name'=>$tag_name, 'type'=>$type))->one();
        if(empty($tag)) {
            $tag = new Tag();
            $tag->name = $tag_name;
            $tag->slug = generate_key($tag_name);
            $tag->status = Tag::ACTIVE;
            $tag->type = $type;
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
        $image_dir = \Yii::$app->params['app'].'/web/uploads/books/'.$this->slug;
        if($this->image != 'default.jpg' && !empty($this->slug)
            && file_exists($image_dir.'/'.$this->image)
            && filesize($image_dir.'/'.$this->image) > 0 ) {
            return \Yii::$app->urlManager->createAbsoluteUrl(['/']) . 'uploads/books/' . $this->slug . '/' . $this->image;
        }
        return \Yii::$app->urlManager->createAbsoluteUrl(['/']) . 'uploads/books/default.jpg';
    }
}
