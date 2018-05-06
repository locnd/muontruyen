<?php

namespace app\controllers\admin;

use app\models\Chapter;
use yii\web\Controller;
use app\models\Book;
use app\models\Image;
use Yii;

class ChapterController extends Controller
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
        $this->view->params['page_id'] = 'chapter_list';

        $filters = array(
            'name' => trim(getParam('name')),
            'url' => trim(getParam('url')),
            'book_id' => trim(getParam('book_id')),
            'status' => trim(getParam('status')),
            'from_date' => trim(getParam('from_date')),
            'to_date' => trim(getParam('to_date', date('d-m-Y')))
        );
        $chapters = Chapter::find()->where(['>', 'id', 0]);
        if($filters['name'] != '') {
            $chapters->andWhere(['like', 'name', $filters['name']]);
        }
        if($filters['url'] != '') {
            $chapters->andWhere(['like', 'url', $filters['url']]);
        }
        if($filters['book_id'] != '') {
            $chapters->andWhere(['=', 'book_id', $filters['book_id']]);
        }
        if($filters['status'] != '') {
            $chapters->andWhere(['=', 'status', $filters['status']]);
        }
        if($filters['from_date'] != '') {
            $chapters->andWhere(['>=', 'created_at', convert_to_mysql_time($filters['from_date'].' 00:00:00')]);
        }
        if($filters['to_date'] != '') {
            $chapters->andWhere(['<=', 'created_at', convert_to_mysql_time($filters['to_date'].' 23:59:59')]);
        }
        $total = $chapters->count();

        $limit = 20;
        $total_page = ceil($total / $limit);
        $page = max((int) getParam('page', 1),1);
        $page = min($page, $total_page);
        $offset = ($page - 1) * $limit;

        $chapters->limit($limit)->offset($offset)->orderBy(['book_id' => SORT_DESC, 'id' => SORT_DESC]);
        $chapters = $chapters->all();

        return $this->render('/admin/chapter/index', array(
            'chapters' => $chapters,
            'filters' => $filters,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ));
    }
}

