<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use Yii;

use yii\console\Controller;
use yii\console\ExitCode;

use app\models\Scraper;
use app\models\Book;
use app\models\Chapter;
use app\models\Setting;
use app\models\ScraperLog;
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
        ini_set('max_execution_time', 24*60*60);
        ini_set('memory_limit', '-1');

        $setting_model = new Setting();
        if($setting_model->get_setting('cron_running') != '') {
            return ExitCode::OK;
        }
        $setting_model->set_setting('cron_running', 'yes');

        $cron_time = time();

        $scraper = new Scraper();
        $scraper->echo = false;

        if($scraper->echo) {
            echo '---------- reload ---------'."\n";
        }
        $count_books = 0;
        $books = Book::find()->where(array('will_reload' => 1))->all();
        foreach ($books as $book) {
            $count_books++;
            $scraper->reload_book($book);
        }
        $count_chapters = 0;
        $chapters = Chapter::find()->where(array('will_reload' => 1))->all();
        foreach ($chapters as $chapter) {
            $count_chapters++;
            $scraper->reload_chapter($chapter);
        }
        if($count_books > 0 || $count_chapters > 0) {
            $log = new ScraperLog();
            $log->type='reload';
            $log->number_books = $count_books;
            $log->number_chapters = $count_chapters;
            $log->save();
        }

        if(time() > $cron_time + 1800) {
            $setting_model->set_setting('cron_running', '');
            return ExitCode::OK;
        }

        if($scraper->echo) {
            echo '---------- scraper ---------'."\n";
        }

        $servers = Server::find()->where(array('status'=>Server::ACTIVE))->all();
        $log = new ScraperLog();
        $log->type='scraper';
        $log->save();
        foreach ($servers as $server) {
            $log->number_servers++;
            $log->save();
            $scraper->parse_server($server, 1, 5, $log);
        }

        if(time() > $cron_time + 3600) {
            $setting_model->set_setting('cron_running', '');
            return ExitCode::OK;
        }

        if($scraper->echo) {
            echo '---------- daily ---------'."\n";
        }

        $page = $setting_model->get_setting('daily_page');
        if($page == '') {
            $page = 2;
        } else {
            $page = (int) $page;
        }
        if($page > 20) {
            $setting_model->set_setting('cron_running', '');
            return ExitCode::OK;
        }
        $servers = Server::find()->where(array('status'=>Server::ACTIVE))->all();
        $log = new ScraperLog();
        $log->type='daily';
        $log->save();

        while(time() < $cron_time + 5400) {
            $page++;
            foreach ($servers as $server) {
                $log->number_servers++;
                $log->save();
                $scraper->parse_server($server, $page, $page, $log, true);
            }
        }

        $setting_model->set_setting('cron_running', '');
        $setting_model->set_setting('daily_page', $page);
        return ExitCode::OK;
    }
}
