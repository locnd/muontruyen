<?php

$params = [
    'app' => '/var/www/truyentranh/truyentranh',
//    'app' => '/home/cp486787/public_html',

    'adminEmail' => 'loc.nd247@gmail.com',
    'meta_title' => 'Mượn truyện',
    'meta_description' => 'Mượn truyện về đọc chút nhé',
    'meta_author' => 'Lộc Nguyễn',
    'meta_keywords' => 'mượn truyện, truyện tranh, truyện hay',

    'use_cache' => false,
    'limit' => 10,
    'use_image_source' => true,
    'debug' => true,
];

function getParam($key, $default='', $method='get') {
    if(strtolower($method)=='get') {
        return Yii::$app->request->get($key, $default);
    }
    if(strtolower($method)=='post') {
        return Yii::$app->request->post($key, $default);
    }
    return '';
}

function dump($data, $exit = true) {
    \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    echo json_encode($data);
    if($exit) { exit(); }
}

function make_cache_key($key, $params=array(), $withs=array(), $pagging=array(), $count) {
    $key = $key.'-'.json_encode($params).'-'.json_encode($withs).'-'.json_encode($pagging).'-'.json_encode($count);
    return generate_key($key);
}

function generate_key($key, $special_char=true) {
    $key = trim(mb_strtolower($key));
    $key = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $key);
    $key = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $key);
    $key = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $key);
    $key = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $key);
    $key = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $key);
    $key = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $key);
    $key = preg_replace('/(đ)/', 'd', $key);
    if($special_char) {
        $key = preg_replace('/[^a-z0-9-\s]/', '', $key);
        $key = preg_replace('/([\s]+)/', '-', $key);
    }
    return $key;
}

function echo_input($option = array(), $select_options = array(), $default = '') {
    $html_tag = '';
    if(in_array($option['type'], array('email', 'text', 'password', 'checkbox', 'hidden', 'number'))) {
        $html_tag = '<input ';
    }
    if($option['type'] == 'select') {
        $html_tag = '<select ';
    }
    foreach ($option as $key => $value) {
        if(in_array($key, array('required', 'checked'))) {
            if($value) {
                $html_tag .= $key.' ';
            }
        } else {
            $html_tag .= $key.'="'.$value.'" ';
        }
    }
    $html_tag .= '>';
    if($option['type'] == 'select') {
        foreach ($select_options as $key => $value) {
            if("$key" === "$default") {
                $html_tag .= '<option value="'.$key.'" selected>'.$value.'</option>';
            } else {
                $html_tag .= '<option value="'.$key.'">'.$value.'</option>';
            }
        }
        $html_tag .= '</select>';
    }
    echo $html_tag;
}

function form_error($model, $error_key) {
    if(!empty($model['error_'.$error_key])) {
        echo '<div class="validation-error">'.$model['error_'.$error_key].'</div>';
    }
}

function convert_to_mysql_time($time) {
    $tmp = explode(' ',$time);
    $tmp_time = !empty($tmp[1]) ? $tmp[1] : '';
    $tmp_date = !empty($tmp[0]) ? $tmp[0] : $time;
    $tmp_date_parse = explode('-', $tmp_date);
    return trim($tmp_date_parse[2].'-'.$tmp_date_parse[1].'-'.$tmp_date_parse[0].' '.$tmp_time);
}

return $params;
