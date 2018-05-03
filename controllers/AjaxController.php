<?php

namespace app\controllers;

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
}
