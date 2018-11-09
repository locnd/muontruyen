<?php

namespace app\models;

use Yii;
use Sunra\PhpSimple\HtmlDomParser;

class Scraper
{
    public $via_proxy = true;
    public $proxyAuth = 'galvin24x7:egor99';

    public function parse_server($server, $page=1, $to_page=1, $is_daily=false) {
        $url = $server->url;
        if(!empty($server->list_items_url)) {
            $url = $server->list_items_url;
        }
        if($is_daily) {
            if(!empty($server->daily_url)) {
                $url = $server->daily_url;
            } else {
                return true;
            }
        }
        $page_urls = array();
        for($i=$page;$i<=$to_page;$i++) {
            $page_urls[$i] = str_replace('{page}', $i, $url);
        }
        if(empty($page_urls)) {
            return true;
        }
        $this->parse_pages($server, $page_urls);
        return true;
    }

    public function parse_pages($server, $page_urls)
    {
        $pages_data = $this->run_curl_multiple($page_urls);
        foreach ($pages_data as $stt => $page_html) {
            echo $server->slug . ' - page ' . $stt;
            $html_base = HtmlDomParser::str_get_html($page_html);
            unset($page_html);
            $pages_data[$stt] = '';
            if (empty($html_base)) {
                $html_base = $this->get_html_base($page_urls[$stt], '', true);
                if (empty($html_base)) {
                    echo ' - can not get html1'."\n";
                    continue;
                }
            }
            $list_books = $html_base->find($server->list_items_key);
            if (count($list_books) == 0) {
                $html_base->clear();
                unset($html_base);
                $html_base = $this->get_html_base($page_urls[$stt], 'phantom', true);
                if (empty($html_base)) {
                    echo ' - can not get html2'."\n";
                    continue;
                }
                $list_books = $html_base->find($server->list_items_key);
            }
            if (count($list_books) == 0) {
                echo ' - can not get html3'."\n";
                continue;
            }
            echo '' . "\n";
            foreach ($list_books as $book) {
                $first_chapter = $book->parent()->parent()->find('li.chapter')[0]->find('a')[0];
                $first_chapter_url = $this->get_full_href($server, $first_chapter->href);
                if(Chapter::find()->where(array('url'=>$first_chapter_url))->count() > 0) {
                    continue;
                }
                $book_url = $this->get_full_href($server, $book->href);
                echo ' ----- ' . $book_url . "\n";
                $cron = BookCron::find()->where(array(
                    'book_url'=>$book_url
                ))->one();
                if(empty($cron)) {
                    $cron = new BookCron();
                    $cron->book_url = $book_url;
                    $cron->status = 0;
                    if(substr($book_url,-4) == '-raw') {
                        $cron->status = 3;
                    }
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
        if($show_way) {
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

    public function reload_chapters($chapters) {
        $chapter_urls = array();
        $book = array(); $success = false;
        foreach($chapters as $chapter) {
            if(empty($book)) $book = $chapter->book;
            $chapter->status = Chapter::INACTIVE;
            if($chapter->reload_time >=3) {
                $chapter->will_reload=0;
                $chapter->reload_time=0;
                $chapter->save();
                continue;
            }
            $chapter->reload_time++;
            $chapter->save();
            $chapter_urls[] = $chapter->url;
            echo $chapter->url."\n";
            Yii::$app->db->createCommand()
                ->delete('dl_images', ['chapter_id' => $chapter->id])
                ->execute();
            Yii::$app->cache->delete('chapter_detail_'.$chapter->id);
        }
        if(empty($chapter_urls)) {
            return true;
        }
        $server = Server::find()->where(array('slug'=>'nettruyen'))->one();
        $chapters_data = $this->run_curl_multiple($chapter_urls);
        foreach ($chapters_data as $stt => $chapter_html) {
            $chapter = '';
            foreach($chapters as $in_chapter) {
                if($chapter_urls[$stt] == $in_chapter->url) {
                    $chapter = $in_chapter; break;
                }
            }
            $html_base = HtmlDomParser::str_get_html($chapter_html);
            unset($chapter_html);
            $chapters_data[$stt] = '';
            if (empty($html_base)) {
                $html_base = $this->get_html_base($chapter_urls[$stt], '', true);
                if (empty($html_base)) {
                    echo ' - can not get html1'."\n";
                    continue;
                }
            }
            $list_images = $html_base->find('.page-chapter');
            if (count($list_images) == 0) {
                $html_base->clear();
                unset($html_base);
                $html_base = $this->get_html_base($chapter_urls[$stt], 'phantom', true);
                if (empty($html_base)) {
                    echo ' - can not get html2'."\n";
                    continue;
                }
                $list_images = $html_base->find('.page-chapter');
            }
            if (count($list_images) == 0) {
                $html_base->clear();
                echo ' - can not get html3'."\n";
                continue;
            }
            echo '' . "\n";
            foreach ($list_images as $ind => $image) {
                $src = $image->find('img')[0]->src;
                $db_img = new Image();
                $db_img->chapter_id = $chapter->id;
                $db_img->image_source = $this->get_full_href($server, $src);
                $db_img->image ="error.jpg";
                $db_img->stt = $ind +1;
                $db_img->status = 1;
                $db_img->save();
            }
            if(count($list_images) > 0) {
                $chapter->status = Chapter::ACTIVE;
                $chapter->will_reload = 0;
                $chapter->reload_time=0;
                $chapter->save();
                echo "----- ----- ".$chapter->name." - ".$chapter->status."\n";
                Yii::$app->cache->delete('chapter_detail_'.$chapter->id);
                $success = true;
            }
            $html_base->clear();
            unset($html_base);
        }
        if($success) {
            $book->release_date = date('Y-m-d H:i:s');
            foreach ($book->follows as $follow) {
                $follow->status = Follow::UNREAD;
                $follow->save();
                send_push_notification($follow->user_id);
            }
            $book->status = Book::ACTIVE;
            $book->save();
            clear_book_cache($book);
        }
    }
}
