<?php

namespace app\controllers\front;

use yii\web\Controller;
use app\models\Book;
use Yii;

class HomeController extends Controller
{
    public $layout = 'front';

    public function actionError() {
        $error = Yii::$app->errorHandler;
        if(Yii::$app->params['debug']) {
            dump($error);
        }
        if (Yii::$app->request->isAjax) {
            return array(
                'success' => false,
                'message' => 'Đã xảy ra lỗi phía server'
            );
        }
        return $this->render('error', array('error' => $error));
    }

    public function actionIndex()
    {
        $limit = 20;
        $book_model = new Book();
        $books = $book_model->get_data(array('status'=>Book::ACTIVE), array(), array(
            'order' => array('release_date' => SORT_DESC),
            'limit' => $limit,
            'page' => max(1, (int) Yii::$app->request->get('page',1))
        ));
        $data = array();
        foreach ($books as $book) {
            $data[] = $book->to_array();
        }
        $count_books = $book_model->get_data(array('status'=>Book::ACTIVE), array(), array(), false, true);
        return $this->render('index', [
            'books' => $data,
            'count_pages' => ceil($count_books/$limit)
        ]);
    }

    public function actionLogin() {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect('/');
        }
        $data = array();
        if (Yii::$app->request->post()){
            $data = Yii::$app->request->post();
            $data['is_web'] = true;
            $model = new User();
            $user = $model->login($data);
            if(!empty($user->id)) {
                Yii::$app->user->login($user, !empty($data['remember_me']) ? 3600*24*30 : 0);
                Yii::$app->session->set('is_admin', $user->is_admin == 1);
                Yii::$app->session->set('name', $user->name);
                Yii::$app->session->set('username', $user->username);
                Yii::$app->session->set('email', $user->email);
                return $this->redirect('/admin');
            }
            foreach ($user as $k=>$v) {
                $data['error_'.$k] = $v;
            }
            unset($data['password']);
        }
        return $this->render('login', array('model' => $data));
    }

    public function actionLogout() {
        Yii::$app->user->logout();
        Yii::$app->session->destroy();
        return $this->redirect('/');
    }
}
