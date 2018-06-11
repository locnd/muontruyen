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
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $scraper = new Scraper();
        echo '---------- scraper new ---------'."\n";
        $servers = Server::find()->where(array('status'=>Server::ACTIVE))->all();
        foreach ($servers as $server) {
            $scraper->parse_server($server, 1, 1);
        }
        echo '---------- daily ---------'."\n";
        $setting_model = new Setting();
        $page = (int) $setting_model->get_setting('daily_page');
        if($page < 35) {
            $page++;
            foreach ($servers as $server) {
                $scraper->parse_server($server, $page, $page);
                $scraper->parse_server($server, $page, $page, true);
            }
            $setting_model->set_setting('daily_page', $page);
        }
        return ExitCode::OK;
    }
}
