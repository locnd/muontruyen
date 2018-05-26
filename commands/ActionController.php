<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Scraper;
use app\models\Server;
use Yii;

use yii\console\Controller;
use yii\console\ExitCode;

use app\models\BookCron;
use app\models\Book;
use app\models\Setting;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ActionController extends Controller
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
        $cronners = (int) $setting_model->get_setting('cronners');
        if($cronners >= 4) {
            return ExitCode::OK;
        }
        $cronners++;
        $setting_model->set_setting('cronners', $cronners);
        $book_cron = BookCron::find()->where(array('status'=>0))->orderBy(array('level'=>SORT_DESC))->one();

        if(!empty($book_cron)) {
            $book = Book::find()->where(array('url' => $book_cron->book_url))->one();
            if (!empty($book) && $book->status == Book::INACTIVE && $book->will_reload == 0) {
                $book_cron->status = 2;
                $book_cron->save();
            } else {
                $book_cron->status = 1;
                $book_cron->save();

                $server = Server::find()->where(array('status'=>1))->one();
                $scraper = new Scraper();
                if(!Yii::$app->params['debug']) {
                    $scraper->echo = false;
                }
                $scraper->parse_books($server, array($book_cron->book_url), array($book));
                usleep(100000);

                $book_cron->status = 2;
                $book_cron->save();
            }
        }
        $cronners = (int) $setting_model->get_setting('cronners');
        $cronners--;
        $setting_model->set_setting('cronners', max($cronners,0));
        return ExitCode::OK;
    }
}
