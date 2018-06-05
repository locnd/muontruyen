<?php

namespace app\models;

class Setting extends ModelCommon
{
    public static function tableName(){
        return 'dl_settings';
    }

    public function get_setting($key, $default = '') {
        $setting = Setting::find()->where(array('name'=> $key))->one();
        if(empty($setting->value)) {
            return $default;
        }
        return $setting->value;
    }
    public function set_setting($key, $value) {
        $setting = Setting::find()->where(array('name'=> $key))->one();
        if(empty($setting)) {
            $setting = new Setting();
            $setting->name = $key;
        }
        $setting->value = $value;
        $setting->save();
    }
    public function delete_setting($key) {
        $setting = Setting::find()->where(array('name'=> $key))->one();
        if(!empty($setting)) {
            $setting->delete();
        }
    }
}
