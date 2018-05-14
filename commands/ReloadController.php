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
        ini_set('max_execution_time', 24*60*60);
        ini_set('memory_limit', '-1');

        $setting_model = new Setting();
        if($setting_model->get_setting('cron_running') != '') {
            return ExitCode::OK;
        }
        $setting_model->set_setting('cron_running', 'yes');

        $log = new ScraperLog();
        $log->type='reload';
        $log->save();

        $scraper = new Scraper();
        $scraper->echo = false;

        $books = Book::find()->where(array('will_reload' => 1))->all();
        foreach ($books as $book) {
            $log->number_books++;
            $log->save();
            $scraper->reload_book($book);
        }

        $chapters = Chapter::find()->where(array('will_reload' => 1))->all();
        foreach ($chapters as $chapter) {
            $log->number_chapters++;
            $log->save();
            $scraper->reload_chapter($chapter);
        }

        $setting_model->set_setting('cron_running', '');
        return ExitCode::OK;
    }
}
