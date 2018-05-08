<?php

namespace app\controllers\admin;

use yii\web\Controller;
use app\models\Report;
use Yii;

class ReportController extends Controller
{
    public $layout = 'admin';

    public function beforeAction($action)
    {
        if (!Yii::$app->user->isGuest) {
            if(!Yii::$app->session->get('is_admin', 0)) {
                Yii::$app->session->addFlash('error', 'Bạn không có quyền truy cập trang này');
                return $this->redirect('/admin/logout');
            }
        } else {
            return $this->redirect('/admin/login');
        }
        return parent::beforeAction($action);
    }
    
    public function actionIndex() {
        $this->view->params['page_id'] = 'report';

        $filters = array(
            'user_id' => trim(getParam('user_id')),
            'book_id' => trim(getParam('email')),
            'status' => trim(getParam('status')),
            'from_date' => trim(getParam('from_date')),
            'to_date' => trim(getParam('to_date', date('d-m-Y')))
        );
        $reports = Report::find()->where(['>', 'id', 0]);
        if($filters['user_id'] != '') {
            $reports->andWhere(array('user_id'=>$filters['user_id']));
        }
        if($filters['book_id'] != '') {
            $reports->andWhere(array('book_id'=>$filters['book_id']));
        }
        if($filters['status'] != '') {
            $reports->andWhere(['=', 'status', $filters['status']]);
        }
        if($filters['from_date'] != '') {
            $reports->andWhere(['>=', 'created_at', convert_to_mysql_time($filters['from_date'].' 00:00:00')]);
        }
        if($filters['to_date'] != '') {
            $reports->andWhere(['<=', 'created_at', convert_to_mysql_time($filters['to_date'].' 23:59:59')]);
        }

        $total = $reports->count();

        $limit = get_limit();
        $total_page = ceil($total / $limit);
        $page = max((int) getParam('page', 1),1);
        $page = min($page, $total_page);
        $offset = ($page - 1) * $limit;

        $reports->limit($limit)->offset($offset)->orderBy(['id' => SORT_DESC]);
        $reports = $reports->all();

        return $this->render('/admin/report/index', array(
            'reports' => $reports,
            'filters' => $filters,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ));
    }
}

