<?php

namespace app\models;

use Yii;
use Sunra\PhpSimple\HtmlDomParser;

class Scraper
{
    public $via_proxy = true;
    public $proxyAuth = 'galvin24x7:egor99';
    public $echo = true;

    public function parse_server($server, $page=1, $to_page=1, $log, $skip = true)
    {
        if($page > $to_page) {
            return true;
        }
        if($this->echo)
            echo $server->slug . ' - page ' . $page . "\n";

        $url = $server->url;
        if(!empty($server->list_items_url)) {
            $url = $server->list_items_url;
        }
        $url = str_replace('{page}', $page, $url);
        $html = $this->curl_getcontent($url);
        $html_base = HtmlDomParser::str_get_html($html);
        if(empty($html_base)) {
            return true;
        }
        $list_items = $html_base->find($server->list_items_key);
        if(count($list_items) == 0) {
            $html_base->clear();
            unset($html_base);
            return true;
        }
        $dem = 0;
        foreach ($list_items as $stt => $item) {
            $a_href = $this->get_full_href($server, $item->href);
            $number_chapters = $this->parse_book($server, $a_href);
            $log->number_books++;
            $log->number_chapters += $number_chapters;
            $log->save();
            if($number_chapters == 0) {
                $dem++;
                if($skip && $dem >=3 ) { return true; }
            }
        }
        $html_base->clear();
        unset($html_base);
        $this->parse_server($server, $page + 1, $to_page, $log);
        return true;
    }

    private function get_full_href($server, $a_href) {
        if(substr($a_href,0,4) != 'http') {
            $a_href = $server->url.''.$a_href;
        }
        return $a_href;
    }

    private function parse_book($server, $a_href)
    {
        $book = Book::find()->where(array('url'=>$a_href))->one();
        if(!empty($book)) {
            if($this->echo)
                echo '---- ' . $book->slug . "\n";
            if($book->status == Book::INACTIVE) {
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
                    $this->send_push_notification($follow->user_id, $book);
                }
            }
            return $number_chapters;
        }

        $html = $this->curl_getcontent($a_href);
        $html_base = HtmlDomParser::str_get_html($html);
        if(empty($html_base)) {
            return 1;
        }
        $title = trim($html_base->find($server->title_key)[0]->plaintext);
        $title = $this->remove_symbols(html_entity_decode($title), true, true, false, true, '(),.:;?!_"\-\'');

        $new_slug = $slug = $this->createSlug($title);

        $check_slug = Book::find()->where(array('slug'=>$new_slug))->count();
        $tm = 1;
        while($check_slug > 0) {
            $tm++;
            $new_slug = $slug.'-'.$tm;
            $check_slug = Book::find()->where(array('slug'=>$new_slug))->count();
        }
        $slug = $new_slug;

        $image_src = $html_base->find($server->image_key)[0]->src;
        $image_dir = Yii::$app->params['app'].'/web/uploads/books/'.$server->slug.'/'.$slug;

        $image = $this->save_image($image_src, $image_dir);

        $tmp_li = $html_base->find('p.p_Content')[0]->parent();
        $tmp_p = $tmp_li->find('p')[1];
        $description = trim($this->remove_symbols(html_entity_decode(trim($tmp_p->plaintext)), true, true, false, true, '(),.:;?!_"\-\''));
        if($description == '' || strtolower(trim($description,'.')) == 'đang cập nhật') {
            $description = 'Chưa có thông tin';
        }

        $book = new Book();
        $book->status = Book::ACTIVE;
        $book->server_id = $server->id;
        $book->url = $a_href;
        $book->image_source = $image_src;
        $book->image = $image;
        $book->title = $title;
        $book->slug = $slug;
        $book->description = $description;
        $book->release_date = date('Y-m-d H:i:s');
        $book->save();

        $tags = $html_base->find($server->list_tags_key);
        foreach ($tags as $tag) {
            $book->add_tag(trim(html_entity_decode($tag->plaintext)));
        }

        $html_base->clear();
        unset($html_base);

        if($this->echo)
            echo '---- ' . $slug . "\n";

        $number_chapters = $this->get_chapters($book);
        if($number_chapters > 0) {
            $book->release_date = date('Y-m-d H:i:s');
            $book->save();
        }
        return $number_chapters;
    }

    private function get_chapters($book, $page=1, $skip=true, $dem=0) {
        $url = $book->url;
        if($page > 1) {
            $url = str_replace('.html','/trang.'.$page.'.html', $url);
        }

        $html = $this->curl_getcontent($url);
        $html_base = HtmlDomParser::str_get_html($html);
        if(empty($html_base)) {
            return $dem;
        }
        $chapters = $html_base->find($book->server->list_chapters_key);
        if(count($chapters) == 0) {
            return $dem;
        }
        $book_name = strtolower($book->title);
        foreach ($chapters as $num => $chapter) {
            $chapter_url = $this->get_full_href($book->server, $chapter->href);
            $name = trim(strtolower(html_entity_decode($chapter->plaintext)));
            $name = trim(str_replace($book_name,'',$name),' -–');
            $name = str_replace('chapter','chương',$name);
            $name = str_replace('chap','chương',$name);
            $name = str_replace('chuong','chương',$name);

            $chapter = Chapter::find()->where(array('url' => $chapter_url))->one();
            if(!empty($chapter) && $skip) {
                continue;
            }
            $dem++;

            if(empty($chapter)) {
                $chapter = new Chapter();
                $chapter->book_id = $book->id;
                $chapter->stt = ($page - 1) * 100 + $num+1;
                $chapter->url = $chapter_url;
                $chapter->name = $name;
                $chapter->status = Chapter::ACTIVE;
                $chapter->save();
            }
            $this->parse_chapter($chapter);
        }
        $html_base->clear();
        unset($html_base);
        $page++;
        return $this->get_chapters($book, $page , $skip, $dem);
    }

    private function parse_chapter($chapter, $way = 'curl')
    {
        if($this->echo)
            echo '-------- chapter ' . $chapter->stt;

        $dir = Yii::$app->params['app'].'/web/uploads/books/'.$chapter->book->server->slug.'/'.$chapter->book->slug.'/chap'.$chapter->id;

        $html = $this->curl_getcontent($chapter->url);
        $html_base = HtmlDomParser::str_get_html($html);
        $images = array();
        if(!empty($html_base)) {
            $images = $html_base->find($chapter->book->server->images_key);
        }
        if(count($images) == 0 || $way == 'phantom') {
            $way = 'phantom';
            $file_html = $this->parse_url_by_phantom($chapter->url);
            if($file_html != '') {
                $html_base = HtmlDomParser::str_get_html($file_html);
                $images = $html_base->find($chapter->book->server->images_key);
            } else {
                $way = 'curl';
            }
        }
        if($this->echo)
            echo ' - ' . $way;

        $dem = 0;
        $current_images = Image::find()->where(array('chapter_id'=>$chapter->id))->all();
        foreach ($current_images as $current_image) {
            $current_image->status = Image::INACTIVE;
            $current_image->save();
        }
        foreach ($images as $id=>$image) {
            $image_src = $image->src;
            if(empty($image_src)) {
                continue;
            }
            $image_name = $this->save_image($image_src, $dir, $id+1);
            $new_image = Image::find()->where(array('chapter_id'=>$chapter->id, 'image_source' => $image_src))->one();
            if(empty($new_image)) {
                $new_image = new Image();
                $new_image->chapter_id = $chapter->id;
                $new_image->image_source = $image_src;
            }
            $new_image->image = $image_name;
            $new_image->status = Image::ACTIVE;
            $new_image->stt = $id;
            $new_image->save();
            $dem++;
        }
        if($way == 'curl') {
            $html_base->clear();
        }
        unset($html_base);
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

    private function save_image($image_source, $image_dir, $stt=0) {
        $image = 'default.jpg';
        if($stt > 0) {
            $image = 'error.jpg';
        }
        return $image;
        $array = explode('?', $image_source);
        $tmp_extension = $array[0];
        $array = explode('.', $tmp_extension);
        $extension = end($array);
        if(strlen($extension) > 4 || strlen($extension) < 3) {
            $image = 'default.jpg';
            if($stt > 0) {
                $image = 'error.jpg';
            }
        } else {
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
        }
        return $image;
    }
    public function reload_book($book) {
        if($this->echo)
            echo '---- ' . $book->slug . "\n";
        $this->get_chapters($book,1, false);
    }
    public function reload_chapter($chapter, $way = 'curl') {
        if($this->echo) {
            echo '---- ' . $chapter->book->slug . "\n";
        }
        $this->parse_chapter($chapter, $way);
    }

    private function parse_url_by_phantom($url) {
        $time_stamp = time();

        $fetchScript = 'var fs = require("fs");
var page = require(\'webpage\').create();

page.open("%s", function (status) {
  fs.write("%s", page.content);
  phantom.exit();
});';
        $phantomPath = Yii::$app->params['app'].'/phantom/phantomjs';
        $fetchPath = Yii::$app->params['app'].'/phantom/fetch'.$time_stamp.'.js';
        $htmlPath = Yii::$app->params['app'].'/phantom/page'.$time_stamp.'.html';

        file_put_contents($fetchPath, sprintf($fetchScript, $url, $htmlPath ));
        exec($phantomPath . ' '.$fetchPath);

        $html='';
        if (file_exists($htmlPath)){
            $html = file_get_contents($htmlPath);
        }
        usleep(100000);
        if (file_exists($fetchPath)){
            unlink($fetchPath);
        }
        if (file_exists($htmlPath)){
            unlink($htmlPath);
        }
        return $html;
    }

    private function send_push_notification($user_id, $book) {
        $api_key = 'AAAAJAcz9dM:APA91bHGhUo2vCU6p53zMD5_YnfIKnZbFkCf5eoaMghSufF7yHN0qPokC5dIBa5tIYjGh4crDXf2KCHpsB0A24D3GHYeVfSlNgYltud7z9UG5kvJ5lrnMdJcO4VSk2vkVb3jz7Bgphw3';
        $device = Device::find()->where(array('user_id'=>$user_id))->one();
        if(empty($device)) {
            return true;
        }
        $device_id = $device->device_id;
        $fields = array(
            'registration_ids' => array($device_id),
            'data' => array(
                'title' => '',
                'message' => 'Truyện bạn đang theo dõi có cập nhật chương mới',
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
        $result = curl_exec($ch );
        curl_close( $ch );
    }
}
