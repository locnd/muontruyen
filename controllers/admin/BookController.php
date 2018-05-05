<?php

namespace app\controllers\admin;

use app\models\Chapter;
use yii\web\Controller;
use app\models\Book;
use Yii;

class BookController extends Controller
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
        $this->view->params['page_id'] = 'book_list';

        $filters = array(
            'name' => trim(getParam('name')),
            'url' => trim(getParam('url')),
            'status' => trim(getParam('status')),
            'from_date' => trim(getParam('from_date')),
            'to_date' => trim(getParam('to_date', date('d-m-Y')))
        );
        $books = Book::find()->where(['>', 'id', 0]);
        if($filters['name'] != '') {
            $books->andWhere(['like', 'name', $filters['name']]);
        }
        if($filters['url'] != '') {
            $books->andWhere(['like', 'url', $filters['url']]);
        }
        if($filters['status'] != '') {
            $books->andWhere(['=', 'status', $filters['status']]);
        }
        if($filters['from_date'] != '') {
            $books->andWhere(['>=', 'created_at', convert_to_mysql_time($filters['from_date'].' 00:00:00')]);
        }
        if($filters['to_date'] != '') {
            $books->andWhere(['<=', 'created_at', convert_to_mysql_time($filters['to_date'].' 23:59:59')]);
        }
        $total = $books->count();

        $limit = 20;
        $total_page = ceil($total / $limit);
        $page = max((int) getParam('page', 1),1);
        $page = min($page, $total_page);
        $offset = ($page - 1) * $limit;

        $books->limit($limit)->offset($offset)->orderBy(['release_date' => SORT_DESC, 'id' => SORT_DESC]);
        $books = $books->all();

        return $this->render('/admin/book/index', array(
            'books' => $books,
            'filters' => $filters,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ));
    }
    public function actionDetail($id) {
        $this->view->params['page_id'] = 'book_detail';
        $book = Book::find()->where(array('id'=>$id))->one();
        if(empty($book)) {
            throw new \yii\base\Exception( "Book not found" );
        }
        return $this->render('/admin/book/detail', array(
            'book' => $book
        ));
    }
    public function actionChapter($id) {
        $this->view->params['page_id'] = 'chapter_detail';
        $chapter = Chapter::find()->where(array('id'=>$id))->one();
        if(empty($chapter)) {
            throw new \yii\base\Exception( "Chapter not found" );
        }
        return $this->render('/admin/book/chapter', array(
            'chapter' => $chapter
        ));
    }
}

