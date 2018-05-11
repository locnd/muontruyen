<?php

namespace app\controllers\admin;

use app\models\Device;
use yii\web\Controller;
use Yii;

class DeviceController extends Controller
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
        $this->view->params['page_id'] = 'device';

        $filters = array(
            'user_id' => trim(getParam('user_id')),
            'app_version' => trim(getParam('app_version')),
            'from_date' => trim(getParam('from_date')),
            'to_date' => trim(getParam('to_date', date('d-m-Y')))
        );
        $devices = Device::find()->where(['>', 'id', 0]);
        if ($filters['user_id'] != '') {
            $devices->andWhere(['=', 'user_id', $filters['user_id']]);
        }
        if ($filters['app_version'] != '') {
            $devices->andWhere(['=', 'app_version', $filters['app_version']]);
        }
        if ($filters['from_date'] != '') {
            $devices->andWhere(['>=', 'created_at', convert_to_mysql_time($filters['from_date'] . ' 00:00:00')]);
        }
        if ($filters['to_date'] != '') {
            $devices->andWhere(['<=', 'created_at', convert_to_mysql_time($filters['to_date'] . ' 23:59:59')]);
        }
        $total = $devices->count();

        $limit = get_limit();
        $total_page = ceil($total / $limit);
        $page = max((int)getParam('page', 1), 1);
        $page = min($page, $total_page);
        $offset = ($page - 1) * $limit;

        $devices->limit($limit)->offset($offset)->orderBy(['updated_at' => SORT_DESC, 'id' => SORT_DESC]);
        $devices = $devices->all();

        return $this->render('/admin/device/index', array(
            'devices' => $devices,
            'filters' => $filters,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ));
    }
}

