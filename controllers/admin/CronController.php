<?php

namespace app\controllers\admin;

use yii\web\Controller;
use app\models\ScraperLog;
use app\models\Setting;
use Yii;

class CronController extends Controller
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
        $this->view->params['page_id'] = 'cron';

        $settings = Setting::find()->all();
        $logs = ScraperLog::find()->limit(50)->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])->all();

        return $this->render('/admin/cron/index', array(
            'settings' => $settings,
            'logs' => $logs,
        ));
    }
}

