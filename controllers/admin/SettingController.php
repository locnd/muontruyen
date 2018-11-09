<?php

namespace app\controllers\admin;

use app\models\Chapter;
use app\models\Image;
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

    public function actionIndex() {
        if(!empty($_GET['action']) && $_GET['action'] == 'delete_images_base64') {
            $images = Image::find()->where(['like', 'image_source', 'data:image'])->all();
            $total = count($images);
            foreach ($images as $image) {
                $chapter = Chapter::find()->where(array('id'=>$image->chapter_id))->one();
                if(empty($chapter) || $chapter->will_reload == 1) {
                    continue;
                }
                $chapter->status = Chapter::INACTIVE;
                $chapter->will_reload = 1;
                $chapter->save();
                Yii::$app->db->createCommand()
                    ->delete('dl_images', ['chapter_id' => $chapter->id])
                    ->execute();
                Yii::$app->db->createCommand()
                    ->delete('dl_bookmarks', ['chapter_id' => $chapter->id])
                    ->execute();
                clear_book_cache($chapter->book);
                Yii::$app->cache->delete('chapter_detail_'.$chapter->id);
            }
            echo 'Make reload '.$total.' chapters';
            exit();
        }
        $this->view->params['page_id'] = 'setting';
        $settings = Setting::find()->all();
        return $this->render('/admin/setting/index', array(
            'settings' => $settings
        ));
    }
}

