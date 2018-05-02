<?php

namespace app\models;

class Server extends ModelCommon
{
    public static function tableName(){
        return 'dl_servers';
    }

    const INACTIVE = 0;
    const ACTIVE = 1;
}
