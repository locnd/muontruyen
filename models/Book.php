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
        return $this->hasMany(Chapter::className(), ['book_id' => 'id'])->where(array('dl_chapters.status'=>Chapter::ACTIVE))->orderBy(['stt' => SORT_ASC, 'id' => SORT_DESC]);
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

    public function update_follows_notify() {
        foreach ($this->follows as $follow) {
            $follow->status = Follow::UNREAD;
            $follow->updated_at = date('Y-m-d H:i:s');
            $follow->save();
        }
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

    public function addScraperTag($tag_name) {
        switch ($tag_name) {
            case 'action':
                $this->add_tag('Hành động');
                break;
            case '18+':
            case 'mature':
            case 'smut':
                $this->add_tag('18+');
                break;
            case 'adult':
                $this->add_tag('Người lớn');
                break;
            case 'adventure':
                $this->add_tag('Phiêu lưu');
                break;
            case 'ecchi':
            case '16+':
                $this->add_tag('Ecchi');
                break;
            case 'fantasy':
            case 'sci-fi':
                $this->add_tag('Ảo tưởng');
                break;
            case 'comedy':
                $this->add_tag('Trong sáng');
                break;
            case 'game':
                $this->add_tag('Trò chơi');
                break;
            case 'gender bender':
                $this->add_tag('Chuyển giới');
                break;
            case 'harem':
                $this->add_tag('Harem');
                break;
            case 'historical':
                $this->add_tag('Lịch sử');
                break;
            case 'horror':
                $this->add_tag('Kinh dị');
                break;
            case 'isekai/dị giới':
                $this->add_tag('Dị giới');
                break;
            case 'josei':
            case 'romance':
            case 'shoujo':
                $this->add_tag('Lãng mạn');
                break;
            case 'magic':
                $this->add_tag('Phép thuật');
                break;
            case 'manga':
                $this->add_tag('Nhật Bản');
                break;
            case 'manhua':
                $this->add_tag('Trung Quốc');
                break;
            case 'comic':
                $this->add_tag('Âu Mỹ');
                break;
            case 'manhwa':
                $this->add_tag('Hàn Quốc');
                break;
            case 'martial arts':
                $this->add_tag('Võ thuật');
                break;
            case 'mecha':
                $this->add_tag('Robot');
                break;
            case 'mystery':
                $this->add_tag('Bí ẩn');
                break;
            case 'nấu ăn':
                $this->add_tag('Nấu ăn');
                break;
            case 'ntr':
                $this->add_tag('NTR');
                break;
            case 'one shot':
                $this->add_tag('1 tập');
                break;
            case 'psychological':
                $this->add_tag('Tâm lý');
                break;
            case 'school life':
                $this->add_tag('Học đường');
                break;
            case 'shoujo ai':
            case 'soft yuri':
            case 'yuri':
                $this->add_tag('Luyến nữ');
                break;
            case 'shounen ai':
            case 'soft yaoi':
            case 'yaoi':
                $this->add_tag('Luyến nam');
                break;
            case 'slice of life':
                $this->add_tag('Đời thường');
                break;
            case 'sports':
                $this->add_tag('Thể thao');
                break;
            case 'supernatural':
                $this->add_tag('Siêu nhiên');
                break;
            case 'tạp chí truyện tranh':
                $this->add_tag('Tạp chí');
                break;
            case 'trap (crossdressing)':
                $this->add_tag('Giả gái');
                break;
            case 'trinh thám':
                $this->add_tag('Trinh thám');
                break;
            case 'vncomic':
                $this->add_tag('Việt Nam');
                break;
            default:
                return true;
        }
    }
}
