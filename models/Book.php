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
            $image_source = $this->image_source;
            $array = explode('?', $image_source);
            $tmp_extension = $array[0];
            $array = explode('.', $tmp_extension);
            $extension = trim(strtolower(end($array)));
            if($extension != 'png') {
                $extension = 'jpg';
            }
            $image = 'cover.'.$extension;
            $image_dir = \Yii::$app->params['app'].'/web/uploads/books/'.$this->slug;
            $dir_array = explode('/', $image_dir);
            $tmp_dir = '';
            foreach ($dir_array as $i => $folder) {
                $tmp_dir .= '/'.$folder;
                if($i > 3 && !file_exists($tmp_dir)) {
                    mkdir($tmp_dir, 0777);
                }
            }
            $image_dir = $image_dir.'/'.$image;

            $ch = curl_init($image_source);
            $fp = fopen($image_dir, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            $this->image = $image;
            $this->save();
        }
        return \Yii::$app->urlManager->createAbsoluteUrl(['/']) . 'uploads/books/' . $this->slug . '/' . $this->image;
    }
}
