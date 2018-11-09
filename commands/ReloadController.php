<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\BookCron;
use app\models\Chapter;
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
class ReloadController extends Controller
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

        $setting_model = new Setting();
        $reloading = $setting_model->get_setting('reloading');
        if($reloading != '') {
            echo 'chapter is reloading..';
            return ExitCode::OK;
        }
        // $setting_model->set_setting('reloading', 'yes');

        $chapter = Chapter::find()->where('will_reload', 1)->one();
        if(empty($chapter)) {
            $setting_model->set_setting('reloading', '');
            echo 'No chapter need reload..';
            return ExitCode::OK;
        }

        $chapters = Chapter::find()->where(array('will_reload'=>1, 'book_id'=>$chapter->book_id))->all();

        $scraper = new Scraper();
        echo '---------- scraper reload ---------' . "\n";
        $scraper->reload_chapters($chapters);
        return ExitCode::OK;
    }
}
