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

use app\models\Book;
use app\models\BookCron;
use app\models\Chapter;
use app\models\Image;
use app\models\Follow;
/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DoneController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     * @return int Exit code
     */
    public function actionIndex($book_id)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        ini_set('mysql.connect_timeout', 3600);
        ini_set('default_socket_timeout', 3600);

        $book = Book::find()->where(array('id'=>$book_id))->one();
        if(empty($book)) {
            return ExitCode::OK;
        }
        if(empty($book->slug)) {
            $new_slug = $slug = createSlug($book->name);
            $tm = 1;
            while (Book::find()->where(array('slug' => $new_slug))->count() > 0) {
                $tm++;
                $new_slug = $slug . '-' . $tm;
            }
            $book->slug = $new_slug;
            $book->save();
        }
        echo "----- ".$book->slug."\n";

        if((empty($book->image) || $book->image == 'default.jpg') && !empty($book->image_source)) {
            $image_dir = Yii::$app->params['app'].'/web/uploads/books/'.$book->slug;
            $array = explode('?', $book->image_source);
            $tmp_extension = $array[0];
            $array = explode('.', $tmp_extension);
            $extension = strtolower(end($array));
            if(!in_array($extension, array('jpg','png','jpeg','gif'))) {
                $extension = 'jpg';
            }
            $book->image = 'cover.'.$extension;
            $dir_array = explode('/', $image_dir);
            $tmp_dir = '';
            foreach ($dir_array as $i => $folder) {
                $tmp_dir .= '/'.$folder;
                if($i > 3 && !file_exists($tmp_dir)) {
                    umask(0);
                    mkdir($tmp_dir, 0777);
                }
            }
            $image_dir = $image_dir.'/'.$book->image;
            $ch = curl_init($book->image_source);
            $fp = fopen($image_dir, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }
        $chapters = Chapter::find()->where(array('book_id' => $book->id, 'status'=>Chapter::INACTIVE))->all();
        echo "----- chapters ".count($chapters)."\n";
        foreach ($chapters as $chapter) {
            $chapter->status = Chapter::ACTIVE;
            $chapter->will_reload = 0;
            if (Image::find()->where(array('chapter_id' => $chapter->id, 'status' => Image::ACTIVE))->count() == 0) {
                $chapter->status = Chapter::INACTIVE;
                $chapter->will_reload = 1;
            }
            $chapter->save();
            echo "----- ----- ".$chapter->name." - ".$chapter->status."\n";
        }
        $book->status = Book::ACTIVE;
        if(Chapter::find()->where(array('book_id'=>$book_id, 'status'=>Chapter::ACTIVE))->count() == 0) {
            $book->status = Book::INACTIVE;
        }

        $book->release_date = date('Y-m-d H:i:s');
        foreach ($book->follows as $follow) {
            $follow->status = Follow::UNREAD;
            $follow->save();
            Yii::$app->cache->delete('user_unread_' . $follow->user_id);
            send_push_notification($follow->user_id);
        }
        $book->save();

        $book_cron = BookCron::find()->where(array('book_url'=>$book->url))->one();
        $book_cron->status = 2;
        $book_cron->save();

        clear_book_cache($book);
        return ExitCode::OK;
    }
}