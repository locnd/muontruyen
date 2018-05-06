<?php

namespace app\controllers\admin;

use yii\web\Controller;
use app\models\Report;
use Yii;

class ErrorController extends Controller
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

    public function actionChapter() {
        $this->view->params['page_id'] = 'chapter_error';

        $filters = array(
            'name' => trim(getParam('name')),
            'book_id' => trim(getParam('email')),
            'from_date' => trim(getParam('from_date')),
            'to_date' => trim(getParam('to_date', date('d-m-Y')))
        );

        $connection = Yii::$app->getDb();
        $cmd = "SELECT c.*, b.name as book_name FROM dl_chapters c
LEFT OUTER JOIN dl_books b ON (b.id = c.book_id)
LEFT OUTER JOIN dl_images i ON (c.id = i.chapter_id)
WHERE i.chapter_id IS NULL";
        if($filters['name'] != '') {
            $cmd .= ' AND c.name LIKE %'.$filters['name'].'%';
        }
        if($filters['book_id'] != '') {
            $cmd .= ' AND c.book_id ='.$filters['book_id'];
        }
        if($filters['from_date'] != '') {
            $cmd .= ' AND c.created_at >= "'.convert_to_mysql_time($filters['from_date'].' 00:00:00').'"';
        }
        if($filters['to_date'] != '') {
            $cmd .= ' AND c.created_at <= "'.convert_to_mysql_time($filters['to_date'].' 23:59:59').'"';
        }

        $command = $connection->createCommand($cmd);
        $chapters = $command->queryAll();

        return $this->render('/admin/error/index', array(
            'items' => $chapters,
            'filters' => $filters,
            'type' => 'chapter'
        ));
    }

    public function actionBook() {
        $this->view->params['page_id'] = 'book_error';

        $filters = array(
            'name' => trim(getParam('name')),
            'from_date' => trim(getParam('from_date')),
            'to_date' => trim(getParam('to_date', date('d-m-Y')))
        );

        $connection = Yii::$app->getDb();
        $cmd = "SELECT b.* FROM dl_books b
LEFT OUTER JOIN dl_chapters c ON (b.id = c.book_id)
WHERE c.book_id IS NULL";
        if($filters['name'] != '') {
            $cmd .= ' AND b.name LIKE %'.$filters['name'].'%';
        }
        if($filters['from_date'] != '') {
            $cmd .= ' AND b.created_at >= "'.convert_to_mysql_time($filters['from_date'].' 00:00:00').'"';
        }
        if($filters['to_date'] != '') {
            $cmd .= ' AND b.created_at <= "'.convert_to_mysql_time($filters['to_date'].' 23:59:59').'"';
        }

        $command = $connection->createCommand($cmd);
        $books = $command->queryAll();

        return $this->render('/admin/error/index', array(
            'items' => $books,
            'filters' => $filters,
            'type' => 'book'
        ));
    }
}

