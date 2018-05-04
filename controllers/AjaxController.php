<?php

namespace app\controllers;

use app\models\Setting;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Book;
use app\models\Chapter;

class AjaxController extends Controller
{
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }
    public function actionEditbook()
    {
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
    public function actionSortchapters()
    {
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
        if (!Yii::$app->user->isGuest) {
            if (!Yii::$app->session->get('is_admin', 0)) {
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

        $book_id = (int)Yii::$app->request->post('book_id', 0);
        $book = Book::find()->where(array('id' => $book_id))->one();
        if (empty($book)) {
            return array(
                'success' => false,
                'message' => 'Book not found'
            );
        }
        $book_name = trim(strtolower($book->title));
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
        if (!Yii::$app->user->isGuest) {
            if (!Yii::$app->session->get('is_admin', 0)) {
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
        if (!Yii::$app->user->isGuest) {
            if (!Yii::$app->session->get('is_admin', 0)) {
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
}
