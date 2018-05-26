<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\BookCron;
use Yii;

use yii\console\Controller;
use yii\console\ExitCode;

use app\models\Scraper;
use app\models\Book;
use app\models\Chapter;
use app\models\Setting;
use app\models\Server;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class CronController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return int Exit code
     */
    public function actionIndex()
    {
        ini_set('memory_limit', '-1');

        $setting_model = new Setting();
        if($setting_model->get_setting('cron_running') != '') {
            return ExitCode::OK;
        }
        $setting_model->set_setting('cron_running', 'yes');

        $scraper = new Scraper();
        if(!Yii::$app->params['debug']) {
            $scraper->echo = false;
        }

        if($scraper->echo) {
            echo '---------- reload ---------'."\n";
        }

        $books = Book::find()->where(array('will_reload' => 1))->all();
        $book_urls = array();
        $db_books = array();
        $db_servers = array();
        foreach ($books as $book) {
            $server = $book->server;
            if(empty($db_servers[$server->id])) {
                $db_servers[$server->id] = '';
                $db_books[$server->id] = array();
                $book_urls[$server->id] = array();
            }
            $book_urls[$server->id][] = $book->url;
            $db_books[$server->id][] = $book;
            $db_servers[$server->id] = $book->server;
        }
        if($scraper->echo) {
            echo '- reload books ' . count($books) . "\n";
        }
        if(count($books) > 0) {
            $scraper->skip_chapter_existed = false;
            foreach ($db_servers as $i => $db_server) {
                $scraper->parse_books($db_server, $book_urls[$i], $db_books[$i]);
            }
        }

        $chapters = Chapter::find()->where(array('will_reload' => 1))->all();
        $chapter_urls = array();
        $db_chapters = array();
        $db_books = array();
        foreach ($chapters as $chapter) {
            $book = $chapter->book;
            if(empty($db_books[$book->id])) {
                $db_books[$book->id] = '';
                $db_chapters[$book->id] = array();
                $chapter_urls[$book->id] = array();
            }
            $db_chapters[$book->id][] = $chapter;
            $chapter_urls[$book->id][] = $chapter->url;
            $db_books[$book->id] = $book;
        }
        if($scraper->echo) {
            echo '- reload chapters ' . count($chapters) . "\n";
        }
        if(count($chapters) > 0) {
            foreach ($db_books as $i => $db_book) {
                $scraper->parse_chapters($db_book->server, $chapter_urls[$i], $db_chapters[$i], $db_book);
                Yii::$app->cache->delete('book_detail_'.$db_book->id);
            }
        }

        if($scraper->echo) {
            echo '---------- scraper ---------'."\n";
        }
        $servers = Server::find()->where(array('status'=>Server::ACTIVE))->all();

        $scraper->skip_book_existed = false;
        $scraper->skip_chapter_existed = true;
        foreach ($servers as $server) {
            $scraper->parse_server($server, 1, 2);
        }

        if(BookCron::find()->where(array('status' => 0))->count() < 5) {
            $count_book = BookCron::find()->count();
            $page=ceil($count_book/36);
            if($count_book % 36 == 0) {
                $page++;
            }
            if($page > 30) {
                $page = (int) $setting_model->get_setting('daily_finished');
                $page++;
                $setting_model->set_setting('daily_finished', $page);
            }
            $scraper->skip_book_existed = true;
            $servers = Server::find()->where(array('status'=>Server::ACTIVE))->all();
            foreach ($servers as $server) {
                $scraper->parse_server($server, $page, $page, true);
            }
        }

        $setting_model->set_setting('cron_running', '');
        return ExitCode::OK;
    }
}
