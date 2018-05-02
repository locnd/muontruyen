<?php

namespace app\controllers\admin;

use yii\web\Controller;
use app\models\User;
use Yii;

class UserController extends Controller
{
    public $layout = 'admin';
    
    public function actionIndex() {
        if (!Yii::$app->user->isGuest) {
            if(!Yii::$app->session->get('is_admin', 0)) {
                Yii::$app->session->addFlash('error', 'Bạn không có quyền truy cập trang này');
                return $this->redirect('/admin/logout');
            }
        } else {
            return $this->redirect('/admin/login');
        }
        $this->view->params['page_id'] = 'user_list';

        $filters = array(
            'username' => trim(getParam('username')),
            'email' => trim(getParam('email')),
            'name' => trim(getParam('name')),
            'is_admin' => trim(getParam('is_admin')),
            'status' => trim(getParam('status')),
            'from_date' => trim(getParam('from_date')),
            'to_date' => trim(getParam('to_date', date('d-m-Y')))
        );
        $users = User::find()->where(['>', 'id', 0]);
        if($filters['username'] != '') {
            $users->andWhere(['like', 'username', $filters['username']]);
        }
        if($filters['email'] != '') {
            $users->andWhere(['like', 'email', $filters['email']]);
        }
        if($filters['name'] != '') {
            $users->andWhere(['like', 'name', $filters['name']]);
        }
        if($filters['is_admin'] != '') {
            $users->andWhere(['=', 'is_admin', $filters['is_admin']]);
        }
        if($filters['status'] != '') {
            $users->andWhere(['=', 'status', $filters['status']]);
        }
        if($filters['from_date'] != '') {
            $users->andWhere(['>=', 'created_at', convert_to_mysql_time($filters['from_date'].' 00:00:00')]);
        }
        if($filters['to_date'] != '') {
            $users->andWhere(['<=', 'created_at', convert_to_mysql_time($filters['to_date'].' 23:59:59')]);
        }
        $users = $users->all();

        return $this->render('/admin/user/index', array(
            'users' => $users,
            'filters' => $filters
        ));
    }
}

