<?php

namespace app\models;

use Yii;
use Sunra\PhpSimple\HtmlDomParser;

class Scraper
{
    public $via_proxy = true;
    public $proxyAuth = 'galvin24x7:egor99';
    public $echo = true;
    public $skip_book_existed = false;
    public $skip_chapter_existed = true;

    public function parse_server($server, $page=1, $to_page=1, $is_daily=false) {
        $url = $server->url;
        if(!empty($server->list_items_url)) {
            $url = $server->list_items_url;
        }
        if($is_daily && !empty($server->daily_url)) {
            // $url = $server->daily_url;
        }
        $page_urls = array();
        for($i=$page;$i<=$to_page;$i++) {
            $page_urls[$i] = str_replace('{page}', $i, $url);
        }
        if(empty($page_urls)) {
            return true;
        }
        $this->parse_pages($server, $page_urls);
    }

    public function parse_pages($server, $page_urls)
    {
        $pages_data = $this->run_curl_multiple($page_urls);
        foreach ($pages_data as $stt => $page_html) {
            if ($this->echo) echo $server->slug . ' - page ' . $stt;
            $html_base = HtmlDomParser::str_get_html($page_html);
            unset($page_html);
            $pages_data[$stt] = '';
            if (empty($html_base)) {
                $html_base = $this->get_html_base($page_urls[$stt], '', true);
                if (empty($html_base)) {
                    if ($this->echo) echo ' - can not get html1'."\n";
                    continue;
                }
            }
            $list_books = $html_base->find($server->list_items_key);
            if (count($list_books) == 0) {
                $html_base->clear();
                unset($html_base);
                $html_base = $this->get_html_base($page_urls[$stt], 'phantom', true);
                if (empty($html_base)) {
                    if ($this->echo) echo ' - can not get html2'."\n";
                    continue;
                }
                $list_books = $html_base->find($server->list_items_key);
            }
            if (count($list_books) == 0) {
                if ($this->echo) echo ' - can not get html3'."\n";
                continue;
            }
            if ($this->echo) echo '' . "\n";

            $skip_books = 0;
            foreach ($list_books as $book) {
                if($skip_books > 5) {
                    break 2;
                }
                $book_url = $this->get_full_href($server, $book->href);
                if($this->skip_book_existed) {
                    if(Book::find()->where(array('url'=>$book_url))->count() > 0) {
                        continue;
                    }
                }

                $first_chapter = $book->parent()->parent()->find('li.chapter')[0]->find('a')[0];
                $first_chapter_url = $this->get_full_href($server, $first_chapter->href);
                if(Chapter::find()->where(array('url'=>$first_chapter_url))->count() > 0) {
                    $skip_books++;
                    continue;
                }
                if ($this->echo) {
                    echo ' ----- ' . $book_url . "\n";
                }
                $cron = BookCron::find()->where(array(
                    'book_url'=>$book_url
                ))->one();
                if(empty($cron)) {
                    $cron = new BookCron();
                    $cron->book_url = $book_url;
                    $cron->status = 0;
                    $cron->save();
                }
                if($cron->status == 2) {
                    $cron->status = 0;
                    $cron->save();
                }
            }
            $html_base->clear();
            unset($html_base);
        }
    }
    public function parse_books($server, $book_urls, $db_books) {
        $books_data = $this->run_curl_multiple($book_urls);
        foreach ($books_data as $stt => $book_html) {
            $book_html_base = HtmlDomParser::str_get_html($book_html);
            unset($book_html);
            $books_data[$stt] = '';
            if (empty($book_html_base)) {
                $book_html_base = $this->get_html_base($book_urls[$stt], '', true);
                if (empty($book_html_base)) {
                    if ($this->echo) echo '----- Book ' . $book_urls[$stt] . ' can not get html1' . "\n";
                    continue;
                }
            }
            $chapters = $book_html_base->find($server->list_chapters_key);
            if (count($chapters) == 0) {
                $book_html_base->clear();
                unset($book_html_base);
                $book_html_base = $this->get_html_base($book_urls[$stt], 'phantom', true);
                if (empty($book_html_base)) {
                    if ($this->echo) echo '----- Book ' . $book_urls[$stt] . ' can not get html2' . "\n";
                    continue;
                }
                $chapters = $book_html_base->find($server->list_chapters_key);
            }
            if (count($chapters) == 0) {
                if ($this->echo) echo '----- Book ' . $book_urls[$stt] . ' can not get html3' . "\n";
                continue;
            }

            if (!empty($db_books[$stt])) {
                $book = $db_books[$stt];
                if ($book->status == Book::INACTIVE && $book->will_reload == 0) {
                    continue;
                }
            } else {
                $book = new Book();
                $book->server_id = $server->id;
                $book->url = $book_urls[$stt];
                $book->release_date = date('Y-m-d H:i:s');
            }

            if(empty($book->name)) {
                $book->name = $this->get_text($book_html_base->find($server->title_key)[0]->plaintext);
            }
            if(empty($book->slug)) {
                $new_slug = $slug = createSlug($book->name);
                $tm = 1;
                while (Book::find()->where(array('slug' => $new_slug))->count() > 0) {
                    $tm++;
                    $new_slug = $slug . '-' . $tm;
                }
                $book->slug = $new_slug;
            }
            if(strpos($book->slug,'raw') !== false) {
                if ($this->echo) echo '----- ----- skip for RAW'. "\n";
                continue;
            }
            if ($this->echo) echo '----- ' . $book->slug . "\n";

            if(empty($book->image)|| $book->image == 'default.jpg') {
                $image_dir = Yii::$app->params['app'] . '/web/uploads/books/' . $book->slug;
                if(!empty($book->image_source)) {
                    $image_src = $book->image_source;
                } else {
                    $image_src = $book_html_base->find($server->image_key)[0]->src;
                    if (substr($image_src, 0, 2) == '//') {
                        $image_src = 'http:' . $image_src;
                    }
                    $image_src = str_replace('https:', 'http:', $image_src);
                    $book->image_source = $image_src;
                }
                $book->image = $this->save_image($image_src, $image_dir);
            }
            if(empty($book->description)) {
                $book->description = $this->get_text($book_html_base->find($server->description_key)[0]->plaintext);
            }

            $book->save();

            if(BookTag::find()->where(array('book_id'=>$book->id))->count() == 0) {
                $tags_arr = $book_html_base->find($server->list_tags_key);
                $tags = array();
                foreach ($tags_arr as $tag) {
                    $tags[] = strtolower(trim($this->get_text($tag->plaintext), ' .,-'));
                }
                $authors_arr = $book_html_base->find('ul.list-info li.author p.col-xs-8 a');
                foreach ($authors_arr as $author) {
                    $author_str = strtolower(trim($this->get_text($author->plaintext), ' .,-'));
                    if ($author_str != 'đang cập nhật' && $author_str != 'chưa cập nhật') {
                        $tags[] = 'author:' .$author_str;
                    }
                }
                $status = $book_html_base->find($server->status_key)[0];
                $status_str = strtolower(trim($this->get_text($status->plaintext), ' .,-'));
                if ($status_str == 'hoàn thành') {
                    $tags[] = $status_str;
                }
                if (in_array('one shot', $tags) && !in_array('hoàn thành', $tags)) {
                    $tags[] = 'hoàn thành';
                }
                foreach ($tags as $tag) {
                    $book->add_tag($tag);
                }
            }
            $db_chapters = array();
            $chapter_urls = array();
            $chapter_skip = 0;
            $has_new_chapter = false;
            foreach ($chapters as $num => $chapter) {
                if($chapter_skip > 2) { break; }
                $chapter_url = $this->get_full_href($server, $chapter->href);
                $db_chapter = Chapter::find()->where(array('url' => $chapter_url))->one();
                if(!empty($db_chapter) && $db_chapter->status == Chapter::INACTIVE && $chapter->will_reload == 0) {
                    continue;
                }
                if(!empty($db_chapter) && $this->skip_chapter_existed) {
                    $chapter_skip++;
                    continue;
                }
                if(empty($db_chapter)) {
                    $name = strtolower($this->get_text($chapter->plaintext));
                    $name = str_replace('chapter','chương',$name);
                    $name = str_replace('chap','chương',$name);
                    $name = trim(str_replace('chuong','chương',$name));
                    if (strpos($name, 'raw') !== false) {
                        continue;
                    }
                    $has_new_chapter = true;
                    $db_chapter = new Chapter();
                    $db_chapter->book_id = $book->id;
                    $db_chapter->stt = count($chapters) - $num + 1; // should not + 1
                    $db_chapter->url = $chapter_url;
                    $db_chapter->name = ucfirst($name);
                    $db_chapter->status = Chapter::INACTIVE;
                    $db_chapter->will_reload = 0;
                    $db_chapter->save();
                }
                $db_chapters[$num] = $db_chapter;
                $chapter_urls[$num] = $chapter_url;
            }
            $book_html_base->clear();
            unset($book_html_base);
            if(!empty($chapter_urls)) {
                $this->parse_chapters($server, $chapter_urls, $db_chapters, $book);
            }
            if($has_new_chapter) {
                $book->release_date = date('Y-m-d H:i:s');
                foreach ($book->follows as $follow) {
                    $follow->status = Follow::UNREAD;
                    $follow->save();
                    send_push_notification($follow->user_id);
                    Yii::$app->cache->delete('user_unread_'.$follow->user_id);
                }
            }
            $current_status = $book->status;
            $book->status = Book::ACTIVE;
            $book->will_reload = 0;
            if(Chapter::find()->where(array('book_id'=>$book->id, 'status'=>Chapter::ACTIVE))->count() == 0) {
                $book->status = Book::INACTIVE;
                $book->will_reload = 1;
            }
            $book->save();
            Yii::$app->cache->delete('book_detail_'.$book->id);
            if($current_status != $book->status) {
                Yii::$app->cache->delete('tags_list');
                Yii::$app->cache->delete('book_searchs');
            }
        }
    }
    public function parse_chapters($server, $chapter_urls, $db_chapters, $book) {
        $chapters_data = $this->run_curl_multiple($chapter_urls);
        foreach ($chapters_data as $num => $chapter_html) {
            if($this->echo)
                echo '----- ----- '.$db_chapters[$num]->name;
            $dir = Yii::$app->params['app'].'/web/uploads/books/'.$book->slug.'/chapter_'.$db_chapters[$num]->id;

            $chapter_html_base = HtmlDomParser::str_get_html($chapter_html);
            unset($chapter_html);
            $chapters_data[$num] = '';
            if(empty($chapter_html_base)) {
                $chapter_html_base = $this->get_html_base($chapter_urls[$num],'',true);
                if(empty($chapter_html_base)) {
                    if($this->echo) echo ' - can not get html1'."\n";
                    continue;
                }
            }

            $images = $chapter_html_base->find($server->images_key);
            if(count($images) == 0) {
                $chapter_html_base->clear();
                unset($chapter_html_base);
                $chapter_html_base = $this->get_html_base($chapter_urls[$num], 'phantom',true);
                if(empty($chapter_html_base)) {
                    if($this->echo) echo ' - can not get html2'."\n";
                    continue;
                }
                $images = $chapter_html_base->find($server->images_key);
            }
            if(count($images) == 0) {
                if($this->echo) echo ' - can not get html3'."\n";
                continue;
            }

            $image_urls = array();
            foreach ($images as $id=>$image) {
                $image_src = $image->src;
                if(empty($image_src)) {
                    continue;
                }
                if(substr($image_src, 0, 2) == '//') {
                    $image_src = 'http:'.$image_src;
                }
                $image_urls[$id] = str_replace('https:','http:', $image_src);
            }

            $current_images = Image::find()->where(array('chapter_id'=>$db_chapters[$num]->id))->all();
            foreach ($current_images as $current_image) {
                $current_image->status = Image::INACTIVE;
                $current_image->save();
            }
            $dem = 0;
            foreach ($image_urls as $id=>$image_src) {
                $image_name = $this->save_image($image_src, $dir, $id+1);
                $new_image = Image::find()->where(array('chapter_id'=>$db_chapters[$num]->id, 'image_source' => $image_src))->one();
                if(empty($new_image)) {
                    $new_image = new Image();
                    $new_image->chapter_id = $db_chapters[$num]->id;
                    $new_image->image_source = $image_src;
                }
                $new_image->image = $image_name;
                $new_image->status = Image::ACTIVE;
                $new_image->stt = $id+1;
                $new_image->save();
                $dem++;
            }
            if($this->echo) echo ' - ' . $dem . ' images' . "\n";

            $inactive_images = Image::find()->where(array('chapter_id'=>$db_chapters[$num]->id, 'status'=>Image::INACTIVE))->all();
            foreach ($inactive_images as $inactive_image) {
                $inactive_image->delete();
            }

            $db_chapters[$num]->status = Chapter::ACTIVE;
            $db_chapters[$num]->will_reload = 0;
            if(Image::find()->where(array('chapter_id'=>$db_chapters[$num]->id, 'status'=>Image::ACTIVE))->count() == 0) {
                $db_chapters[$num]->status = Chapter::INACTIVE;
                $db_chapters[$num]->will_reload = 1;
            }
            $db_chapters[$num]->save();
            Yii::$app->cache->delete('chapter_detail_'.$db_chapters[$num]->id);
        }
    }

    public function get_text($txt) {
        return remove_symbols(html_entity_decode($txt), true, true, false, true, '(),.:;?!_"\-\'');
    }

    public function run_curl_multiple($urls) {
        $headers = array();
        $headers[] = "Accept-Encoding: gzip, deflate";
        $headers[] = "Accept-Language: en-US,en;q=0.9";
        $headers[] = "Upgrade-Insecure-Requests: 1";
        $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
        $headers[] = "Cache-Control: max-age=0";
        $headers[] = "Connection: keep-alive";
        $dem = 0;
        $result = array();
        while($dem < count($urls)) {
            $curls = array();
            $mh = curl_multi_init();
            $stt = -1;
            foreach ($urls as $id => $url) {
                $stt++;
                if($stt<$dem) {
                    continue;
                }
                if ($stt > $dem+17) {
                    $dem = $stt;
                    break;
                }
                $curls[$id] = curl_init();
                curl_setopt($curls[$id], CURLOPT_URL, $url);
                curl_setopt($curls[$id], CURLOPT_HEADER, 0);
                curl_setopt($curls[$id], CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curls[$id], CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curls[$id], CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($curls[$id], CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($curls[$id], CURLOPT_ENCODING, 'gzip, deflate');
                if ($this->via_proxy) {
                    curl_setopt($curls[$id], CURLOPT_PROXY, 'http://' . $this->getProxy());
                    curl_setopt($curls[$id], CURLOPT_PROXYUSERPWD, $this->proxyAuth);
                }
                curl_multi_add_handle($mh, $curls[$id]);
                if($stt==count($urls)-1) {
                    $dem = count($urls);
                    break;
                }
            }
            $running = null;
            do {
                usleep (10000);
                curl_multi_exec($mh, $running);
            } while ($running > 0);
            foreach ($curls as $id => $c) {
                $result[$id] = curl_multi_getcontent($c);
                curl_multi_remove_handle($mh, $c);
            }
            curl_multi_close($mh);
        }
        return $result;
    }

    private function get_full_href($server, $a_href) {
        if(substr($a_href,0,4) != 'http') {
            $a_href = $server->url.''.$a_href;
        }
        return str_replace('https:','http:', $a_href);
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
                    umask(0);
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
        usleep(1000);
        exec($phantomPath . ' '.$fetchPath);

        $html='';
        usleep(1000);
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
            echo ' - '.$still_way.' ';
        }
        return $html_base;
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
}
