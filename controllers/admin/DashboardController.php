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
        $options['Cronners'] = (int) $setting_model->get_setting('cronners');
        $book_cronning = BookCron::find()->where(array('status'=>1))->count();
        if($options['Cronners'] > $book_cronning) {
            $setting_model->set_setting('cronners', $book_cronning);
        }
        $options['Book Crons'] = $book_cronning.' - '.BookCron::find()->where(array('status'=>0))->count();

        if (Yii::$app->request->post()){
            $user_id = Yii::$app->request->post('user_id');
            $message = Yii::$app->request->post('message');
            if(!empty($user_id) && !empty($message)) {
                $scraper = new Scraper();
                $scraper->send_push_notification($user_id, $message);
            }
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
