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
use app\models\ScraperLog;
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
        ini_set('max_execution_time', 24*60*60);

        $setting_model = new Setting();
        if($setting_model->get_setting('running_daily') != '') {
            return ExitCode::OK;
        }
        $setting_model->set_setting('running_daily', 'yes');
        $setting_model->set_setting('running_scraper', 'yes');

        $book_count = Book::find()->count();
        $page = ceil($book_count / 36);
        if($book_count % 36 == 0) {
            $page++;
        }

        $scraper = new Scraper();
        $servers = Server::find()->where(array('status'=>Server::ACTIVE))->all();
        $log = new ScraperLog();
        $log->type='daily';
        $log->save();
        foreach ($servers as $server) {
            $log->number_servers++;
            $log->save();
            $scraper->parse_server($server, $page, $page, $log, false);
        }
        $setting_model->set_setting('running_daily', '');
        $setting_model->set_setting('running_scraper', '');
        return ExitCode::OK;
    }
}
