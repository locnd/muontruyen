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
        return parent::beforeSave($insert);
    }

    public function to_array($fields=array()) {
        $tmp_data = array();
        foreach ($this as $k => $v) {
            if(is_null($v) || (!empty($fields) && !in_array($k, $fields))) {
                continue;
            }
            $tmp_data[$k] = $v;
            if($k == 'image') {
                $tmp_data['image'] = $this->get_image();
            }
            if($k == 'release_date') {
                $tmp_data['release_date'] = date('d-m-Y H:i',strtotime($this->release_date));
            }
        }
        return $tmp_data;
    }
}