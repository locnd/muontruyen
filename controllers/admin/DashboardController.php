<?php

namespace app\controllers\admin;

use yii\web\Controller;
use app\models\User;
use Yii;

class DashboardController extends Controller
{
    public $layout = 'admin';

    public function actionIndex()
    {
        if (!Yii::$app->user->isGuest) {
            if(!Yii::$app->session->get('is_admin', 0)) {
                Yii::$app->session->addFlash('error', 'Bạn không có quyền truy cập trang này');
                return $this->redirect('/admin/logout');
            }
        } else {
            return $this->redirect('/admin/login');
        }
        return $this->render('index');
    }

    public function actionLogin() {
        if (!Yii::$app->user->isGuest) {
            if(Yii::$app->session->get('is_admin', 0)) {
                return $this->redirect('/admin');
            } else {
                Yii::$app->session->addFlash('error', 'Hãy thoát ra trước khi đăng nhập quản trị viên');
                return $this->redirect('/');
            }
        }
        $this->layout = 'admin_login';
        $data = array();
        if (Yii::$app->request->post()){
            $data = Yii::$app->request->post();
            $data['is_web'] = true;
            $data['is_admin'] = true;
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
        return $this->redirect('/admin/login');
    }
}
