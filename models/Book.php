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
        $tag_name = strtolower($tag_name);
        if($tag_name == 'chưa cập nhật') {
            return true;
        }
        $type = 0;
        if(strpos($tag_name, 'author:') !== false) {
            $tag_name = str_replace('author:','',$tag_name);
            $type = 1;
        }
        $new_name = '';
        $name_arr = explode(' ', $tag_name);
        foreach ($name_arr as $tmp_name) {
            if(!empty($tmp_name)) {
                if($new_name == '' || $type == 1) {
                    $new_name .= ucfirst(strtolower($tmp_name)).' ';
                } else {
                    $new_name .= strtolower($tmp_name).' ';
                }
            }
        }
        $tag_name = trim($new_name);
        $slug = generate_key($tag_name);
        $tag = Tag::find()->where(array('slug'=>$slug, 'type'=>$type))->one();
        if(empty($tag)) {
            $tag = new Tag();
            $tag->name = $tag_name;
            $tag->slug = $slug;
            $tag->status = Tag::ACTIVE;
            $tag->type = $type;
            $tag->save();
        }
        if($tag->name != $tag_name) {
            $tag->name = $tag_name;
            $tag->save();
        }
        if(BookTag::find()->where(array('book_id'=>$this->id, 'tag_id'=>$tag->id))->count() > 0) {
            return true;
        }
        $book_tag = new BookTag();
        $book_tag->book_id = $this->id;
        $book_tag->tag_id = $tag->id;
        $book_tag->save();
        \Yii::$app->cache->delete('tags_list');
    }

    public function get_image() {
        $image_dir = \Yii::$app->params['app'].'/web/uploads/books/'.$this->slug;
        if(!empty($this->image) && $this->image != 'default.jpg' && !empty($this->slug)
            && file_exists($image_dir.'/'.$this->image)
            && filesize($image_dir.'/'.$this->image) > 0 ) {
            return \Yii::$app->urlManager->createAbsoluteUrl(['/']) . 'uploads/books/' . $this->slug . '/' . $this->image;
        }
        if((empty($this->image) || $this->image == 'default.jpg') && !empty($this->image_source)) {
            $array = explode('?', $this->image_source);
            $tmp_extension = $array[0];
            $array = explode('.', $tmp_extension);
            $extension = strtolower(end($array));
            if(!in_array($extension, array('jpg','png','jpeg','gif'))) {
                $extension = 'jpg';
            }
            $this->image = 'cover.'.$extension;
            $dir_array = explode('/', $image_dir);
            $tmp_dir = '';
            foreach ($dir_array as $i => $folder) {
                $tmp_dir .= '/'.$folder;
                if($i > 3 && !file_exists($tmp_dir)) {
                    mkdir($tmp_dir, 0777);
                }
            }
            $image_dir = $image_dir.'/'.$this->image;
            $ch = curl_init($this->image_source);
            $fp = fopen($image_dir, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            $this->save();
            return \Yii::$app->urlManager->createAbsoluteUrl(['/']) . 'uploads/books/' . $this->slug . '/' . $this->image;
        }
        return \Yii::$app->urlManager->createAbsoluteUrl(['/']) . 'uploads/books/default.jpg';
    }
}
