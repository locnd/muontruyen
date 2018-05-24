<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

use app\models\Scraper;
use app\models\Server;
use app\models\Setting;
use app\models\Book;
/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DailyController extends Controller
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

        $count_book = Book::find()->count();
        $page=ceil($count_book/36);
        if($count_book % 36 == 0) {
            $page++;
        }
        $to_page = $page+1;
        if($page > 30) {
            $page = $setting_model->get_setting('daily_finished');
            if($page == '') {
                $page = 0;
            } else {
                $page = (int) $page;
            }
            $page++;
            $to_page = $page+1;
            $setting_model->set_setting('daily_finished', $to_page);
        }

        $scraper = new Scraper();
        $scraper->echo = false;

        $scraper->skip_book_existed = true;
        $servers = Server::find()->where(array('status'=>Server::ACTIVE))->all();
        foreach ($servers as $server) {
            $scraper->parse_server($server, $page, $to_page, true);
        }
        $setting_model->set_setting('cron_running', '');
        return ExitCode::OK;
    }
}