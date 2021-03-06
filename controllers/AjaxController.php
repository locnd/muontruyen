<?php

namespace app\controllers;

use app\models\BookCron;
use app\models\Report;
use app\models\Scraper;
use app\models\Setting;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Book;
use app\models\Chapter;
use app\models\Image;
use yii\web\UploadedFile;

class AjaxController extends Controller
{
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (!Yii::$app->user->isGuest) {
            if(!Yii::$app->session->get('is_admin', 0)) {
                return array(
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập trang này'
                );
            }
        } else {
            return array(
                'success' => false,
                'message' => 'Bạn chưa đăng nhập'
            );
        }
        return parent::beforeAction($action);
    }
    public function actionEditbook()
    {
        $book_id = (int) Yii::$app->request->post('book_id',0);
        $book = Book::find()->where(array('id'=>$book_id))->one();
        if(empty($book)) {
            return array(
                'success' => false,
                'message' => 'Book not found'
            );
        }
        $key = Yii::$app->request->post('key','');
        if($key == 'image') {
            $image = UploadedFile::getInstanceByName("value");
            if (!empty($image)) {
                $ext = pathinfo($image , PATHINFO_EXTENSION);
                $fileName = "cover." . $ext;
                $dir = Yii::$app->params['app'].'/web/uploads/books/'.$book->slug;

                $dir_array = explode('/', $dir);
                $tmp_dir = '';
                foreach ($dir_array as $i => $folder) {
                    $tmp_dir .= '/'.$folder;
                    if($i > 3 && !file_exists($tmp_dir)) {
                        mkdir($tmp_dir, 0777);
                    }
                }

                if ($image->saveAs($dir . '/' . $fileName)) {
                    $value = $fileName;
                }
            }
            if (empty($value)) {
                return array(
                    'success' => false,
                    'message' => 'Không thể upload ảnh'
                );
            }
        } else {
            $value = Yii::$app->request->post('value', '');
            if ($key === '' || $value === '') {
                return array(
                    'success' => false,
                    'message' => 'Chưa điền thông tin'
                );
            }
        }
        $book->$key = $value;
        $book->save();
        if($key == 'name' || $key == 'status') {
            clear_book_cache($book);
        } else {
            Yii::$app->cache->delete('book_detail_'.$book->id);
        }
        return array(
            'success' => true
        );
    }
    public function actionEditchapter()
    {
        $chapter_id = (int) Yii::$app->request->post('chapter_id',0);
        $chapter = Chapter::find()->where(array('id'=>$chapter_id))->one();
        if(empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Chapter not found'
            );
        }
        $key = Yii::$app->request->post('key','');
        $value = Yii::$app->request->post('value','');
        if($key === '' || $value === '') {
            return array(
                'success' => false,
                'message' => 'Chưa điền thông tin'
            );
        }
        $chapter->$key = $value;
        $chapter->save();
        Yii::$app->cache->delete('chapter_detail_'.$chapter->id);
        if($key == 'name' || $key == 'status') {
            Yii::$app->cache->delete('book_detail_'.$chapter->book_id);
        }
        return array(
            'success' => true
        );
    }
    public function actionDeletechapter()
    {
        $chapter_id = (int)Yii::$app->request->post('chapter_id', 0);
        $chapter = Chapter::find()->where(array('id' => $chapter_id))->one();
        if (empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Chapter not found'
            );
        }
        Yii::$app->db->createCommand()
            ->delete('dl_images', ['chapter_id' => $chapter->id])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete('dl_bookmarks', ['chapter_id' => $chapter->id])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete('dl_readed', ['chapter_id' => $chapter->id])
            ->execute();
        clear_book_cache($chapter->book);
        Yii::$app->cache->delete('chapter_detail_'.$chapter->id);
        $chapter->delete();
        return array(
            'success' => true
        );
    }
    public function actionSortchapters()
    {
        $book_id = (int) Yii::$app->request->post('book_id',0);
        $book = Book::find()->where(array('id'=>$book_id))->count();
        if($book == 0) {
            return array(
                'success' => false,
                'message' => 'Book not found'
            );
        }
        $chapters = Chapter::find()->where(array('book_id'=>$book_id))->all();
        foreach ($chapters as $stt =>$chapter) {
            $chapter->stt = $stt+1;
            $chapter->save();
        }
        Yii::$app->cache->delete('book_detail_'.$book->id);
        return array(
            'success' => true
        );
    }
    public function actionResetchaptername()
    {
        $book_id = (int)Yii::$app->request->post('book_id', 0);
        $book = Book::find()->where(array('id' => $book_id))->one();
        if (empty($book)) {
            return array(
                'success' => false,
                'message' => 'Book not found'
            );
        }
        $book_name = trim(strtolower($book->name));
        $tmp_name = trim(strtolower(Yii::$app->request->post('tmp_name', '')));
        if($tmp_name != '') {
            $book_name = $tmp_name;
        }
        $check = false;
        foreach ($book->chapters as$chapter) {
            $chapter_name = str_replace($book_name, '', trim(strtolower($chapter->name)));
            $chapter_name = trim($chapter_name, ' -–');
            if($chapter->name != $chapter_name) {
                $chapter->name = $chapter_name;
                $chapter->save();
                $check = true;
                Yii::$app->cache->delete('chapter_detail_'.$chapter->id);
            }
        }
        if($check) {
            Yii::$app->cache->delete('book_detail_' . $book->id);
        }
        return array(
            'success' => true
        );
    }
    public function actionEditsetting()
    {
        $setting_id = (int)Yii::$app->request->post('setting_id', 0);
        $setting = Setting::find()->where(array('id' => $setting_id))->one();
        if (empty($setting)) {
            return array(
                'success' => false,
                'message' => 'Setting not found'
            );
        }
        $value = Yii::$app->request->post('value', '');
        $setting->value = trim($value);
        $setting->save();

        return array(
            'success' => true
        );
    }
    function actionDeleteitem() {
        $type = Yii::$app->request->post('item_type', '');
        $item_id = (int)Yii::$app->request->post('item_id', 0);
        if($type == 'chapter') {
            $item = Chapter::find()->where(array('id' => $item_id))->one();
        }
        if(empty($item)) {
            return array(
                'success' => false,
                'message' => 'Item not found'
            );
        }
        if($type == 'chapter') {
            Yii::$app->db->createCommand("
                DELETE FROM dl_images 
                WHERE chapter_id = '$item->id'
            ")->execute();
            Yii::$app->cache->delete('chapter_detail_'.$item->id);
            Yii::$app->cache->delete('book_detail_'.$item->book_id);
        }
        $item->delete();
        return array(
            'success' => true
        );
    }
    function actionFixed() {
        $reports = Report::find()->where(array(
            'book_id' => (int)Yii::$app->request->post('book_id', 0),
            'chapter_id' => (int)Yii::$app->request->post('chapter_id', 0),
            'status' => Report::STATUS_NEW,
        ))->all();
        if(empty($reports)) {
            return array(
                'success' => false,
                'message' => 'Không có báo lỗi nào cho truyện này'
            );
        }
        foreach ($reports as $report) {
            $report->status = Report::STATUS_FIXED;
            $report->save();
            send_push_notification($report->user_id, 'Truyện đã được sửa lỗi');
        }
        return array(
            'success' => true
        );
    }
    function actionReloadchapter() {
        $chapter_id = (int)Yii::$app->request->post('chapter_id', 0);
        $chapter = Chapter::find()->where(array('id' => $chapter_id))->one();
        if(empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Chapter not found'
            );
        }
        if($chapter->will_reload == 0) {
            $chapter->will_reload = 1;
            $chapter->reload_time = 0;
        } else {
            $chapter->will_reload = 0;
        }
        $chapter->save();
        return array(
            'success' => true
        );
    }
    public function actionAddbook()
    {
        $book_url = trim(Yii::$app->request->post('book_url',''));
        if($book_url == '') {
            return array(
                'success' => false,
                'message' => 'Hãy điền url của truyện'
            );
        }
        $book = Book::find()->where(array('url'=>$book_url))->count();
        if($book > 0) {
            return array(
                'success' => false,
                'message' => 'Truyện đã tồn tại'
            );
        }
        $book = new Book();
        $book->server_id = 1;
        $book->url = $book_url;
        $book->status = Book::INACTIVE;
        $book->will_reload = 1;
        $book->release_date = date('Y-m-d H:i:s');
        $book->save();
        return array(
            'success' => true
        );
    }

    public function actionAddimage() {
        $chapter_id = (int) Yii::$app->request->post('chapter_id', 0);
        $chapter = Chapter::find()->where(array('id' => $chapter_id))->one();
        if(empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Chapter not found'
            );
        }
        $list_images = Yii::$app->request->post('list_images', '');
        if($list_images == '') {
            return array(
                'success' => false,
                'message' => 'Please input list images'
            );
        }
        Yii::$app->db->createCommand()
            ->delete('dl_images', ['chapter_id' => $chapter->id])
            ->execute();
        $image_srcs = explode(',',$list_images);
        $stt = 0;
        foreach ($image_srcs as $image_src) {
            $src= trim($image_src);
            if(empty($src)) continue;
            $src = str_replace('https://','http://', $src);
            $stt++;
            $new_image = new Image();
            $new_image->chapter_id = $chapter->id;
            $new_image->image_source = $src;
            $new_image->image = 'error.jpg';
            $new_image->status = Image::ACTIVE;
            $new_image->stt = $stt;
            $new_image->save();
        }
        $chapter->status = Chapter::ACTIVE;
        $chapter->save();
        Yii::$app->cache->delete('chapter_detail_'.$chapter_id);
        clear_book_cache($chapter->book);
        return array(
            'success' => true
        );
    }
    public function actionIgnorebook() {
        $book_id = (int) Yii::$app->request->post('book_id',0);
        $book = Book::find()->where(array('id'=>$book_id))->one();
        if(empty($book)) {
            return array(
                'success' => false,
                'message' => 'Book not found'
            );
        }
        $book_cron = BookCron::find()->where(array('book_url'=>$book->url))->one();
        if(!empty($book_cron)) {
            $book_cron->status = 3;
            $book_cron->save();
        }
        $chapters = Chapter::find()->where(array('book_id'=>$book_id))->all();
        $chapter_ids = array();
        foreach ($chapters as $stt =>$chapter) {
            $chapter_ids[] = $chapter->id;
        }
        Yii::$app->db->createCommand()
            ->delete('dl_images', ['chapter_id' => $chapter_ids])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete('dl_chapters', ['book_id' => $book_id])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete('dl_bookmarks', ['book_id' => $book_id])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete('dl_reports', ['book_id' => $book_id])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete('dl_book_tag', ['book_id' => $book_id])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete('dl_follows', ['book_id' => $book_id])
            ->execute();
        Yii::$app->db->createCommand()
            ->delete('dl_readed', ['book_id' => $book_id])
            ->execute();

        clear_book_cache($book);
        $book->delete();
        return array(
            'success' => true
        );
    }
}
