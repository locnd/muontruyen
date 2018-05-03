<?php

namespace app\controllers\front;

use yii\web\Controller;
use Yii;

class HomeController extends Controller
{
    public $layout = 'front';

    public function actionError() {
        $error = Yii::$app->errorHandler;
        if(Yii::$app->params['debug']) {
            dump($error);
        }
        if (Yii::$app->request->isAjax) {
            return array(
                'success' => false,
                'message' => 'Đã xảy ra lỗi phía server'
            );
        }
        return $this->render('error', array('error' => $error));
    }

    public function actionIndex()
    {
        return $this->redirect('/admin');
    }
}
