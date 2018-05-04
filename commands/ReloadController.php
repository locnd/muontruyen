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

        $setting_model = new Setting();
        if($setting_model->get_setting('running_reload') != '') {
            return ExitCode::OK;
        }
        $setting_model->set_setting('running_reload', 'yes');

        $log = new ScraperLog();
        $log->type='reload';
        $log->save();

        $scraper = new Scraper();
        $book_model = new Book();
        $books = $book_model->get_data(array('will_reload' => 1));
        if(count($books) > 0) {
            foreach ($books as $book) {
                $log->number_books++;
                $scraper->reload_book($book);
                $book->will_reload = 0;
                $book->save();
            }
        }
        $chapter_model = new Chapter();
        $chapters = $chapter_model->get_data(array('will_reload' => 1));
        if(count($chapters) > 0) {
            foreach ($chapters as $chapter) {
                $log->number_chapters++;
                $scraper->reload_chapter($chapter);
                $chapter->will_reload = 0;
                $chapter->save();
            }
        }
        $log->save();
        $setting_model->set_setting('running_reload', '');
        return ExitCode::OK;
    }
}
