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
use app\models\Server;
use app\models\Setting;
/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ScraperController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return int Exit code
     */
    public function actionIndex($page, $to_page)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $setting_model = new Setting();
        if($setting_model->get_setting('cron_running') != '') {
            return ExitCode::OK;
        }
        $setting_model->set_setting('cron_running', 'yes');
        $scraper = new Scraper();

        $servers = Server::find()->where(array('status'=>Server::ACTIVE))->all();
        foreach ($servers as $server) {
            $scraper->parse_server($server, $page, $to_page);
        }
        $setting_model->set_setting('cron_running', '');
        return ExitCode::OK;
    }
}