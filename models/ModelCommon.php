<?php

namespace app\models;

use Yii;
use yii\helpers\Url;

class ModelCommon extends \yii\db\ActiveRecord
{
    public function beforeSave($insert){
        $this->updated_at = date('Y-m-d H:i:s');
        if($insert) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        Yii::$app->cache->flush();
        return parent::beforeSave($insert);
    }

    public function get_data($params = array(), $with = array(), $pagging = array(), $get_one = false,$is_count = false) {
        $cache_key = '';
        if(Yii::$app->params['use_cache']) {
            $cache_key = make_cache_key('list-'.$this->tableName(),$params,$with,$pagging,$is_count);
            $data=Yii::$app->cache->get($cache_key);
            if($data!==false){
                return $data;
            }
        }
        $model = self::find();
        if(!empty($params)) {
            $model->where($params);
        }
        if($is_count) {
            $data = $model->count();
        } else {
            if (!empty($pagging['page'])) {
                $limit = Yii::$app->params['limit'];
                if (!empty($pagging['limit'])) {
                    $limit = $pagging['limit'];
                }
                $pagging['limit'] = $limit;
                $pagging['offset'] = ($pagging['page'] - 1) * $limit;
            }
            if (!empty($pagging['limit'])) {
                $model->limit($pagging['limit']);
            }
            if (!empty($pagging['offset'])) {
                $model->offset($pagging['offset']);
            }
            if (!empty($pagging['order'])) {
                $model->orderBy($pagging['order']);
            }
            if($get_one) {
                $data = $model->one();
            } else {
                $data = $model->all();
            }
        }
        if(Yii::$app->params['use_cache']) {
            Yii::$app->cache->set($cache_key, $data);
        }
        return $data;
    }

    public function to_array() {
        $tmp_data = array();
        foreach ($this as $k => $v) {
            $tmp_data[$k] = $v;
            if($k == 'image') {
                $tmp_data['image'] = $this->get_image();
            }
            if($k == 'release_date') {
                $tmp_data['release_date'] = date('d-m-Y H:i',strtotime($this->release_date));
            }
            if($k == 'name' && !empty($this->vn_name)) {
                $tmp_data['name'] = $this->vn_name;
            }
        }
        return $tmp_data;
    }
}