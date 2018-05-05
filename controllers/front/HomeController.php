<?php

namespace app\controllers\front;

use yii\web\Controller;
use Yii;
use yii\web\Response;

class HomeController extends Controller
{
    public $layout = 'front';

    public function actionError() {
        $error = Yii::$app->errorHandler;
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return array(
                'success' => false,
                'message' => 'Đã xảy ra lỗi phía server'
            );
        }
        if(Yii::$app->params['debug']) {
            dump($error);
        }
        return $this->render('error', array('error' => $error));
    }

    public function actionIndex()
    {
        return $this->redirect('/admin');
    }
}
