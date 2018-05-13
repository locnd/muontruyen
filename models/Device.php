<?php

namespace app\models;

class Device extends ModelCommon
{
    public static function tableName(){
        return 'dl_devices';
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function add_device($device_id, $app_version, $device_type) {
        $device = Device::find()->where(array('device_id' => $device_id))->one();
        if(empty($device)) {
            $device = new Device();
            $device->device_id = $device_id;
        }
        $device->type = $device_type;
        $device->app_version = $app_version;
        $device->save();

        $db_devices = Device::find()->where(array('device_id' => $device_id))->all();
        foreach ($db_devices as $db_device) {
            if ($db_device->id != $device->id) {
                $db_device->delete();
            }
        }
    }
}
