<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

use app\models\Image;
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
class GetimagesController extends Controller
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

        $end_time = time() + 7200;
        $echo = true;

        $setting_model = new Setting();
        if($setting_model->get_setting('cron_running') != '') {
            return ExitCode::OK;
        }
        $setting_model->set_setting('cron_running', 'yes');

        $log = new ScraperLog();
        $log->type='get_images';
        $log->save();

        $books = Book::find()->where(array('status' => Book::ACTIVE, 'image_blob'=>null))->limit(50)->all();
        foreach ($books as $book) {
            if(time() > $end_time) {
                break;
            }
            if($echo) {
                echo 'Book - ' . $book->id . "\n";
            }
            $log->number_books++;
            $log->save();
            $book->image_blob = get_image_blob($book->get_image());
            $book->save();
        }
        if(time() > $end_time) {
            return ExitCode::OK;
        }
        $images = Image::find()->where(array('status' => Image::ACTIVE, 'image_blob'=>null))->limit(500)->all();
        foreach ($images as $image) {
            if(time() > $end_time) {
                break;
            }
            if($echo) {
                echo 'Image - ' . $image->id . "\n";
            }
            $log->number_chapters++;
            $log->save();
            $image->image_blob = get_image_blob($image->get_image());
            $image->save();
        }
        if(time() > $end_time) {
            return ExitCode::OK;
        }
        $images = Image::find()->where(array('status' => Image::ACTIVE, 'image_blob'=>null))->limit(200)->all();
        foreach ($images as $image) {
            if(time() > $end_time) {
                break;
            }
            if($echo) {
                echo 'Image - ' . $image->id . "\n";
            }
            $log->number_chapters++;
            $log->save();
            $image->image_blob = get_image_blob($image->get_image());
            $image->save();
        }

        $setting_model->set_setting('cron_running', '');
        return ExitCode::OK;
    }
}