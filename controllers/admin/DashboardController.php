<?php

namespace app\controllers\admin;

use app\models\BookCron;
use app\models\Scraper;
use yii\web\Controller;
use app\models\User;
use app\models\Book;
use app\models\Chapter;
use app\models\Setting;
use app\models\Image;
use app\models\Report;
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

        $options = array();
        $options['Số thành viên'] = User::find()->count();
        $options['Số truyện'] = Book::find()->count();
        $options['Số truyện bị ẩn'] = Book::find()->where(array('status'=>Book::INACTIVE))->count();
        $options['Số chương'] = Chapter::find()->count();
        $options['Số chương bị ẩn'] = Chapter::find()->where(array('status'=>Chapter::INACTIVE))->count();
        $options['Số ảnh'] = Image::find()->count();
        $options['Số ảnh bị ẩn'] = Image::find()->where(array('status'=>Image::INACTIVE))->count();
        $options['Will Reload'] = Book::find()->where(array('will_reload'=>1))->count().' - '.Chapter::find()->where(array('will_reload'=>1))->count();

        $options['Số báo lỗi'] = Report::find()->count();
        $options['Số báo lỗi mới'] = Report::find()->where(array('status'=>Report::STATUS_NEW))->count();

        $options['Cron'] = 'stop';
        $setting_model = new Setting();
        if ($setting_model->get_setting('cron_running') != '') {
            $options['Cron'] = 'running';
        }
        $options['Book Crons'] = BookCron::find()->where(array('status'=>1))->count().' - '.BookCron::find()->where(array('status'=>0))->count();

        if(BookCron::find()->where(array('status'=>1))->count() > 0) {
            $book_crons = BookCron::find()->where(array('status'=>1))->all();
            foreach ($book_crons as  $book_cron) {
                $book = Book::find()->where(array('url'=>$book_cron->book_url))->one();
                $need_check = true;
                if(!empty($book)) {
                    $chapters = Chapter::find()->where(array('book_id'=>$book->id,'status'=>Chapter::INACTIVE))->all();
                    foreach ($chapters as $chapter) {
                        if(Image::find()->where(array('chapter_id'=>$chapter->id))->count() == 0) {
                            $need_check = false;
                            break;
                        }
                    }
                } else {
                    $need_check = false;
                }
                if($need_check) {
                    $options['Inactive #'.$book->id] = '<a target="_blank" href="http://34.219.200.77/api/v1/clearcache?token=l2o4c0n7g1u9y8e8n&book_id='.$book->id.'">Active '.$book->name.'</a>';
                } else {
                    $options['Inactive #'.$book->id] = 'Cronning <a target="_blank" href="http://34.219.200.77/api/v1/clearcache?token=l2o4c0n7g1u9y8e8n&book_id='.$book->id.'">Active '.$book->name.'</a>';
                }
            }
        }

        if (Yii::$app->request->post()){
            $user_id = Yii::$app->request->post('user_id');
            $message = Yii::$app->request->post('message');
            if(!empty($user_id) && !empty($message)) {
                send_push_notification($user_id, $message);
            }
        }

        $clear_cache = trim(getParam('clear_cache',''));
        if($clear_cache == 'all') {
            Yii::$app->cache->flush();
        } elseif ($clear_cache != '') {
            Yii::$app->cache->delete('book_detail_'.$clear_cache);
        }

        return $this->render('index', array(
            'options' => $options,
            'users' => User::find()->all()
        ));
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
