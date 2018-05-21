<?php

namespace app\models;

use Yii;
use Sunra\PhpSimple\HtmlDomParser;

class Scraper
{
    public $via_proxy = true;
    public $proxyAuth = 'galvin24x7:egor99';
    public $echo = true;

    public function parse_server($server, $page=1, $to_page=1, $log=array(), $is_daily = false)
    {
        if($page > $to_page) {
            return true;
        }
        if($this->echo)
            echo $server->slug . ' - page ' . $page;

        $url = $server->url;
        if(!empty($server->list_items_url)) {
            $url = $server->list_items_url;
        }
        if($is_daily && !empty($server->daily_url)) {
            // $url = $server->daily_url;
        }
        $url = str_replace('{page}', $page, $url);

        $html_base = $this->get_html_base($url);
        if(empty($html_base)) {
            return true;
        }
        $list_items = $html_base->find($server->list_items_key);
        if(count($list_items) == 0) {
            $html_base->clear();
            unset($html_base);
            $html_base = $this->get_html_base($url, 'phantom');
            if(empty($html_base)) {
                return true;
            }
        }
        if($this->echo)
            echo ' - '.count($list_items).' truyen'."\n";
        $dem = 0;
        $urls = array();
        foreach ($list_items as $stt => $item) {
            $urls[] = $this->get_full_href($server, $item->href);
        }
        $html_base->clear();
        unset($html_base);

        foreach ($urls as $url) {
            $check_book = Book::find()->where(array('url'=>$url))->count();
            if($is_daily && $check_book > 0) {
                continue;
            }
            if(!empty($log)) {
                $log->number_books++;
                $log->save();
            }
            $number_chapters = $this->parse_book($server, $url);
            if(!empty($log)) {
                $log->number_chapters += $number_chapters;
                $log->save();
            }
            if($number_chapters == 0) {
                $dem++;
                if(!$is_daily && $dem >=5 ) { return true; }
            }
        }
        $this->parse_server($server, $page + 1, $to_page, $log);
        return true;
    }

    private function get_full_href($server, $a_href) {
        if(substr($a_href,0,4) != 'http') {
            $a_href = $server->url.''.$a_href;
        }
        return str_replace('https:','http:', $a_href);
    }

    private function parse_book($server, $a_href)
    {
        $book = Book::find()->where(array('url'=>$a_href))->one();
        if(!empty($book)) {
            if($this->echo)
                echo '---- ' . $book->slug . "\n";
            if($book->status == Book::INACTIVE || $book->will_reload == 1) {
                return 1;
            }
            $number_chapters = $this->get_chapters($book);
            if($number_chapters > 0) {
                $book->release_date = date('Y-m-d H:i:s');
                $book->save();
                foreach ($book->follows as $follow) {
                    $follow->status = Follow::UNREAD;
                    $follow->updated_at = date('Y-m-d H:i:s');
                    $follow->save();
                    $this->send_push_notification($follow->user_id);
                }
            }
            if(Chapter::find()->where(array('book_id'=>$book->id))->count() == 0) {
                $book->will_reload = 1;
                $book->status = Book::INACTIVE;
                $book->save();
            }
            return $number_chapters;
        }

        $html_base = $this->get_html_base($a_href);
        if(empty($html_base)) {
            return 1;
        }
        $titles = $html_base->find($server->title_key);
        if(count($titles) == 0) {
            $html_base->clear();
            unset($html_base);
            $html_base = $this->get_html_base($a_href, 'phantom');
            if(empty($html_base)) {
                return 1;
            }
        }
        $title = trim($html_base->find($server->title_key)[0]->plaintext);
        $title = $this->remove_symbols(html_entity_decode($title), true, true, false, true, '(),.:;?!_"\-\'');

        $new_slug = $slug = $this->createSlug($title);
        $tm = 1;
        while(Book::find()->where(array('slug'=>$new_slug))->count() > 0) {
            $tm++;
            $new_slug = $slug.'-'.$tm;
        }
        $slug = $new_slug;

        if($this->echo)
            echo '---- ' . $slug . "\n";

        $image_src = $html_base->find($server->image_key)[0]->src;
        $image_dir = Yii::$app->params['app'].'/web/uploads/books/'.$slug;
        if(substr($image_src, 0, 2) == '//') {
            $image_src = 'http:'.$image_src;
        }
        $image_src = str_replace('https:','http:', $image_src);
        $image = $this->save_image($image_src, $image_dir);

        $description = ucfirst(strtolower(trim($this->remove_symbols(html_entity_decode(trim($html_base->find($server->description_key)[0]->plaintext)), true, true, false, true, '(),.:;?!_"\-\''))));

        $tags_arr = $html_base->find($server->list_tags_key);
        $tags = array();
        foreach ($tags_arr as $tag) {
            $tags[] = strtolower(trim(html_entity_decode($tag->plaintext), ' .,-'));
        }

        $author = $html_base->find($server->list_authors_key)[0];
        $author_str = strtolower(trim(html_entity_decode($author->plaintext), ' .,-'));
        if($author_str != 'đang cập nhật') {
            $authors = $author->find('a');
            foreach ($authors as $author) {
                $tags[] = 'author:'.trim(html_entity_decode($author->plaintext), ' .,-');
            }
        }

        $status = $html_base->find($server->status_key)[0];
        $status_str = strtolower(trim(html_entity_decode($status->plaintext), ' .,-'));
        if($status_str == 'hoàn thành') {
            $tags[] = $status_str;
        }

        if(in_array('one shot', $tags) && !in_array('hoàn thành', $tags)) {
            $tags[] = 'hoàn thành';
        }

        $html_base->clear();
        unset($html_base);

        $book = new Book();
        $book->status = Book::ACTIVE;
        $book->server_id = $server->id;
        $book->url = $a_href;
        $book->image_source = $image_src;
        $book->image = $image;
        $book->name = $title;
        $book->slug = $slug;
        $book->description = $description;
        $book->release_date = date('Y-m-d H:i:s');
        $book->save();

        foreach ($tags as $tag) {
            $book->add_tag($tag);
        }

        $number_chapters = $this->get_chapters($book);
        if($number_chapters > 0) {
            $book->release_date = date('Y-m-d H:i:s');
            $book->save();
        }
        if(Chapter::find()->where(array('book_id'=>$book->id))->count() == 0) {
            $book->will_reload = 1;
            $book->status = Book::INACTIVE;
            $book->save();
        }
        return $number_chapters;
    }

    private function get_chapters($book, $skip=true) {
        $html_base = $this->get_html_base($book->url);
        if(empty($html_base)) {
            return 0;
        }
        $server = $book->server;

        $chapters = $html_base->find($server->list_chapters_key);
        if(count($chapters) == 0) {
            $html_base->clear();
            unset($html_base);
            $html_base = $this->get_html_base($book->url, 'phantom', true);
            if(empty($html_base)) {
                return 0;
            }
        }

        $status = $html_base->find($server->status_key)[0];
        $status_str = strtolower(trim(html_entity_decode($status->plaintext), ' .,-'));
        if($status_str == 'hoàn thành') {
            $book->add_tag($status_str);
        }

        $chapters = $html_base->find($server->list_chapters_key);
        if(count($chapters) == 0) {
            $html_base->clear();
            unset($html_base);
            return 0;
        }
        $db_chapters = array();
        $dem = 0;
        $chap_skip = 0;
        foreach ($chapters as $num => $chapter) {
            $url = $this->get_full_href($server, $chapter->href);
            $db_chapter = Chapter::find()->where(array('url' => $url))->one();
            if(!empty($db_chapter) &&
                ($db_chapter->status == Chapter::INACTIVE || $db_chapter->will_reload == 1)) {
                continue;
            }
            if(!empty($db_chapter) && $skip) {
                $chap_skip++;
                if($chap_skip >= 3) { break; }
                continue;
            }
            if(empty($db_chapter)) {
                $name = strtolower(html_entity_decode($chapter->plaintext));
                $name = str_replace('chapter','chương',$name);
                $name = str_replace('chap','chương',$name);
                $name = trim(str_replace('chuong','chương',$name));

                if (strpos($name, 'raw') !== false) {
                    continue;
                }
                $dem++;

                $db_chapter = new Chapter();
                $db_chapter->book_id = $book->id;
                $db_chapter->stt = count($chapters) - $num + 1;
                $db_chapter->url = $url;
                $db_chapter->name = ucfirst($name);
                $db_chapter->status = Chapter::ACTIVE;
            }
            $db_chapters[] = $db_chapter;
        }
        $html_base->clear();
        unset($html_base);
        foreach ($db_chapters as $db_chapter) {
            $db_chapter->save();
            $this->parse_chapter($db_chapter);
            if(Image::find()->where(array('chapter_id'=>$db_chapter->id))->count() == 0) {
                $db_chapter->will_reload = 1;
                $db_chapter->status = Chapter::INACTIVE;
                $db_chapter->save();
            }
        }
        return $dem;
    }

    private function parse_chapter($chapter)
    {
        if($this->echo)
            echo '-------- ' . $chapter->name;

        $book = $chapter->book;
        $server = $book->server;

        $dir = Yii::$app->params['app'].'/web/uploads/books/'.$book->slug.'/chap'.$chapter->id;

        $image_urls = array();
        $html_base = $this->get_html_base($chapter->url, '', true);
        if(!empty($html_base)) {
            $images = $html_base->find($server->images_key);
            foreach ($images as $id=>$image) {
                $image_src = $image->src;
                if(empty($image_src)) {
                    continue;
                }
                $image_urls[$id] = $image_src;
            }
            $html_base->clear();
            unset($html_base);
        }

        $current_images = Image::find()->where(array('chapter_id'=>$chapter->id))->all();
        foreach ($current_images as $current_image) {
            $current_image->status = Image::INACTIVE;
            $current_image->save();
        }
        unset($current_images);
        $dem = 0;
        foreach ($image_urls as $id=>$image_src) {
            if(substr($image_src, 0, 2) == '//') {
                $image_src = 'http:'.$image_src;
            }
            $image_src = str_replace('https:','http:', $image_src);
            $image_name = $this->save_image($image_src, $dir, $id+1);
            $new_image = Image::find()->where(array('chapter_id'=>$chapter->id, 'image_source' => $image_src))->one();
            if(empty($new_image)) {
                $new_image = new Image();
                $new_image->chapter_id = $chapter->id;
                $new_image->image_source = $image_src;
            }
            $new_image->image = $image_name;
            $new_image->status = Image::ACTIVE;
            $new_image->stt = $id+1;
            $new_image->save();
            $dem++;
        }
        if($this->echo)
            echo ' - ' . $dem . ' images' . "\n";
        return true;
    }

    private function createSlug($str, $delimiter = '-'){
        $slug = trim(mb_strtolower($str));
        $slug = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $slug);
        $slug = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $slug);
        $slug = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $slug);
        $slug = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $slug);
        $slug = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $slug);
        $slug = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $slug);
        $slug = preg_replace('/(đ)/', 'd', $slug);
        $slug = preg_replace('/[^a-z0-9-\s]/', $delimiter, $slug);
        $slug = preg_replace('/([\s]+)/', $delimiter, $slug);
        $slug = preg_replace('/--/', $delimiter, $slug);
        return trim($slug,$delimiter);
    }

    private function remove_symbols($string, $is_number=true, $is_vietnamese=true, $is_lower=false, $is_space=true, $special='', $only_number = false){
        $basic = 'a-zA-Z';
        if($only_number) {
            $basic ='';
        }
        if($is_number) {
            $basic .= '0-9';
        }
        $utf8 = '';
        if($is_vietnamese) {
            $utf8 = 'áàảãạăắặằẳẵâấầẩẫậđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵÁÀẢÃẠĂẮẶẰẲẴÂẤẦẨẪẬĐÉÈẺẼẸÊẾỀỂỄỆÍÌỈĨỊÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢÚÙỦŨỤƯỨỪỬỮỰÝỲỶỸỴ';
        }
        if($is_lower) {
            $string = strtolower($string);
        }
        if(!$is_space) {
            $string = str_replace(' ','',$string);
        }
        $string = preg_replace( '/^[^'.$basic . $utf8 .'\s+]+/iu', '', $string );
        $string = preg_replace( '/[^'.$basic . $utf8 . $special . '\s+]+/iu', '', $string );
        return $string;
    }

    private function curl_getcontent($url,$count = 0)
    {
        $headers = array();
        $headers[] = "Accept-Encoding: gzip, deflate";
        $headers[] = "Accept-Language: en-US,en;q=0.9";
        $headers[] = "Upgrade-Insecure-Requests: 1";
        $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
        $headers[] = "Cache-Control: max-age=0";
        $headers[] = "Connection: keep-alive";

        $ch = curl_init();
        if($this->via_proxy) {
            curl_setopt($ch, CURLOPT_PROXY, 'http://' . $this->getProxy());
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyAuth);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        $content = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        if((($status != 200 && $status != 404) || trim($content)=='') && $count<5 ) {
            return $this->curl_getcontent($url, $count+1);
        }
        return $content;
    }

    private function getProxy()
    {
        $f_contents = file(Yii::$app->params['app'].'/web/proxies.txt');
        $line = trim($f_contents[rand(0, count($f_contents) - 1)]);
        return $line;
    }

    public function save_image($image_source, $image_dir, $stt=0) {
        $image = 'error.jpg';
        if($stt == 0) {
            $array = explode('?', $image_source);
            $tmp_extension = $array[0];
            $array = explode('.', $tmp_extension);
            $extension = strtolower(end($array));
            if(!in_array($extension, array('jpg','png','jpeg','gif'))) {
                $extension = 'jpg';
            }
            $image = 'cover.'.$extension;
            if($stt > 0) {
                $ten = ''.$stt;
                if($stt < 10) { $ten = '0'.$ten; }
                if($stt < 100) { $ten = '0'.$ten; }
                if($stt < 1000) { $ten = '0'.$ten; }
                $image = $ten.'.'.$extension;
            }
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
            if(file_exists($image_dir) && filesize($image_dir) == 0) {
                unlink($image_dir);
                $image = 'default.jpg';
                if($stt > 0) {
                    $image = 'error.jpg';
                }
            }
        }
        return $image;
    }

    public function reload_book($book) {
        if($this->echo) {
            echo '---- ' . $book->slug . "\n";
        }
        if(empty($book->slug)) {
            $server = $book->server;

            $html_base = $this->get_html_base($book->url);
            if(empty($html_base)) {
                return true;
            }
            $titles = $html_base->find($server->title_key);
            if(count($titles) == 0) {
                $html_base->clear();
                unset($html_base);
                $html_base = $this->get_html_base($book->url, 'phantom');
                if(empty($html_base)) {
                    return 1;
                }
            }
            $title = trim($html_base->find($server->title_key)[0]->plaintext);
            $title = $this->remove_symbols(html_entity_decode($title), true, true, false, true, '(),.:;?!_"\-\'');

            $new_slug = $slug = $this->createSlug($title);
            $tm = 1;
            while(Book::find()->where(array('slug'=>$new_slug))->count() > 0) {
                $tm++;
                $new_slug = $slug.'-'.$tm;
            }
            $slug = $new_slug;

            $image_src = $html_base->find($server->image_key)[0]->src;
            if(substr($image_src, 0, 2) == '//') {
                $image_src = 'http:'.$image_src;
            }
            $image_src = str_replace('https:','http:', $image_src);
            $image_dir = Yii::$app->params['app'].'/web/uploads/books/'.$slug;
            $image = $this->save_image($image_src, $image_dir);

            $description = ucfirst(strtolower(trim($this->remove_symbols(html_entity_decode(trim($html_base->find($server->description_key)[0]->plaintext)), true, true, false, true, '(),.:;?!_"\-\''))));

            $tags_arr = $html_base->find($server->list_tags_key);
            $tags = array();
            foreach ($tags_arr as $tag) {
                $tags[] = strtolower(trim(html_entity_decode($tag->plaintext), ' .,-'));
            }

            $author = $html_base->find($server->list_authors_key)[0];
            $author_str = strtolower(trim(html_entity_decode($author->plaintext), ' .,-'));
            if($author_str != 'đang cập nhật') {
                $authors = $author->find('a');
                foreach ($authors as $author) {
                    $tags[] = 'author:'.trim(html_entity_decode($author->plaintext), ' .,-');
                }
            }

            $status = $html_base->find($server->status_key)[0];
            $status_str = strtolower(trim(html_entity_decode($status->plaintext), ' .,-'));
            if($status_str == 'hoàn thành') {
                $tags[] = $status_str;
            }

            if(in_array('one shot', $tags) && !in_array('hoàn thành', $tags)) {
                $tags[] = 'hoàn thành';
            }

            $html_base->clear();
            unset($html_base);

            foreach ($tags as $tag) {
                $book->add_tag($tag);
            }

            $book->image_source = $image_src;
            $book->image = $image;
            $book->name = $title;
            $book->slug = $slug;
            $book->description = $description;
        }
        $book->release_date = date('Y-m-d H:i:s');
        $book->will_reload = 0;
        $book->status = Book::ACTIVE;
        $this->get_chapters($book, false);
        if(Chapter::find()->where(array('book_id'=>$book->id))->count() == 0) {
            $book->will_reload = 1;
            $book->status = Book::INACTIVE;
        }
        $book->save();
    }
    public function reload_chapter($chapter) {
        if($this->echo) {
            echo '---- ' . $chapter->book->slug . "\n";
        }
        if(empty($chapter->stt) && !empty($chapter->book_id)) {
            if($chapter->stt == 0) {
                $last_chap = Chapter::find()->where(['>', 'stt', '0'])->andWhere(array('book_id' => $chapter->book_id))->orderBy(['stt' => SORT_DESC])->one();
                if(!empty($last_chap)) {
                    $chapter->stt = $last_chap->stt + 1;
                }
            }
            $chapter->save();
        }
        $chapter->status = Chapter::ACTIVE;
        $chapter->will_reload = 0;
        $this->parse_chapter($chapter);
        if(Image::find()->where(array('chapter_id'=>$chapter->id))->count() == 0) {
            $chapter->will_reload = 1;
            $chapter->status = Chapter::INACTIVE;
        }
        $chapter->save();
    }

    private function parse_url_by_phantom($url) {
        $time_stamp = time();

        $fetchScript = 'var fs = require("fs");
var page = require(\'webpage\').create();

page.onError = function(msg, trace) {
  fs.write("%s", \'\');
  fs.write("%s", msg);
  phantom.exit();
};

page.open("%s", function (status) {
  fs.write("%s", page.content);
  phantom.exit();
});';
        $phantomPath = Yii::$app->params['app'].'/phantom/phantomjs';
        $fetchPath = Yii::$app->params['app'].'/phantom/'.$time_stamp.'_fetch.js';
        $htmlPath = Yii::$app->params['app'].'/phantom/'.$time_stamp.'_page.html';
        $errorPath = Yii::$app->params['app'].'/phantom/error.txt';

        file_put_contents($fetchPath, sprintf($fetchScript, $htmlPath, $errorPath, $url, $htmlPath ));
        usleep(100000);
        exec($phantomPath . ' '.$fetchPath);

        $html='';
        usleep(100000);
        if (file_exists($htmlPath)){
            $html = file_get_contents($htmlPath);
        }
        if (file_exists($fetchPath)){
            unlink($fetchPath);
        }
        if (file_exists($htmlPath)){
            unlink($htmlPath);
        }
        return $html;
    }

    public function send_push_notification($user_id, $message = '') {
        $api_key = 'AAAAJAcz9dM:APA91bHGhUo2vCU6p53zMD5_YnfIKnZbFkCf5eoaMghSufF7yHN0qPokC5dIBa5tIYjGh4crDXf2KCHpsB0A24D3GHYeVfSlNgYltud7z9UG5kvJ5lrnMdJcO4VSk2vkVb3jz7Bgphw3';
        $device = Device::find()->where(array('user_id'=>$user_id))->one();
        if(empty($device)) {
            return true;
        }
        $device_id = $device->device_id;
        if($message == '') {
            $message = 'Truyện bạn đang theo dõi có cập nhật chương mới';
        }
        $fields = array(
            'registration_ids' => array($device_id),
            'data' => array(
                'title' => 'Mượn Truyện',
                'message' => $message,
                'vibrate' => 1,
                'sound' => 1
            )
        );
        $headers = array(
            'Authorization: key=' . $api_key,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        curl_exec($ch);
        curl_close($ch);
    }

    private function get_html_base($url, $way='',$show_way=false) {
        $still_way = ''; $html_base = array();
        if($way == '' ||  $way=='curl') {
            $still_way = 'curl';
            $html = $this->curl_getcontent($url);
            $html_base = HtmlDomParser::str_get_html($html);
            unset($html);
        }
        if(($way == '' && empty($html_base)) || $way=='phantom') {
            $still_way = 'phantom';
            $file_html = $this->parse_url_by_phantom($url);
            if($file_html != '') {
                $html_base = HtmlDomParser::str_get_html($file_html);
                unset($file_html);
            }
        }
        if($show_way && $this->echo) {
            echo ' - '.$still_way;
        }
        return $html_base;
    }
}
