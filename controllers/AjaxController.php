<?php

namespace app\controllers;

use app\models\Scraper;
use app\models\Setting;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Book;
use app\models\Chapter;
use app\models\Image;

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
        $value = Yii::$app->request->post('value','');
        if($key === '' || $value === '') {
            return array(
                'success' => false,
                'message' => 'Chưa điền thông tin'
            );
        }
        $book->$key = $value;
        $book->save();
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
        foreach ($book->chapters as$chapter) {
            $chapter_name = str_replace($book_name, '', trim(strtolower($chapter->name)));
            $chapter_name = trim($chapter_name, ' -–');
            if($chapter->name != $chapter_name) {
                $chapter->name = $chapter_name;
                $chapter->save();
            }
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
        $urls = Yii::$app->request->post('url', array());
        $stts = Yii::$app->request->post('stt', array());
        $scraper = new Scraper();
        foreach($urls as $num => $image_src) {
            if(empty($image_src) || empty($stts[$num])) {
                continue;
            }
            if(Image::find()->where(array('chapter_id'=>$chapter_id, 'image_source' => $image_src))->count() > 0) {
                continue;
            }
            $id = (int) $stts[$num];
            $dir = Yii::$app->params['app'].'/web/uploads/books/'.$chapter->book->server->slug.'/'.$chapter->book->slug.'/chap'.$chapter->id;
            $image_name = $scraper->save_image($image_src, $dir, $id);

            $new_image = new Image();
            $new_image->chapter_id = $chapter->id;
            $new_image->image_source = $image_src;
            $new_image->image = $image_name;
            $new_image->status = Image::ACTIVE;
            $new_image->stt = $id;
            $new_image->save();
        }
        return array(
            'success' => true
        );
    }
}
