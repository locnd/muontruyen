<?php

namespace app\controllers\admin;

use yii\web\Controller;
use app\models\Setting;
use Yii;

class SettingController extends Controller
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

    public function actionIndex()
    {
        $this->view->params['page_id'] = 'setting';
        $settings = Setting::find()->all();
        return $this->render('/admin/setting/index', array(
            'settings' => $settings
        ));
    }
}

