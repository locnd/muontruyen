<?php

function getParam($key, $default='', $method='get') {
    if(strtolower($method)=='get') {
        return Yii::$app->request->get($key, $default);
    }
    if(strtolower($method)=='post') {
        return Yii::$app->request->post($key, $default);
    }
    return '';
}

function show_number($num, $dec=0) {
    if(is_numeric($num)) {
        return number_format($num, $dec, ',','.');
    }
    return $num;
}

function get_limit($key='backend_limit') {
    $setting_model = new app\models\Setting();
    $limit = $setting_model->get_setting($key);
    if($limit != '') {
        $limit = (int) $limit;
    } else {
        $limit = Yii::$app->params['limit'];
        $setting_model->set_setting($key, $limit);
    }
    return $limit;
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
    if($option['type'] == 'textarea') {
        $html_tag = '<textarea ';
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
    if($option['type'] == 'textarea') {
        $html_tag .= $default;
        $html_tag .= '</textarea>';
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

function make_page_url($url, $filters, $sorts='', $page=1) {
    if(empty($filters) && empty($sorts) && $page == 1) {
        return $url;
    }
    $url .= '?';
    foreach ($filters as $key => $filter) {
        if(!empty($filter)) {
            if(is_string($filter)) {
                $url .= $key.'='.$filter.'&';
            } elseif(is_array($filter)) {
                foreach ($filter as $v) {
                    $url .= $key.'[]='.$v.'&';
                }
            }
        }
    }
    if(is_array($sorts)) {
        foreach ($sorts as $key => $sort) {
            if(!empty($sort)) {
                $url .= $key.'='.$sort.'&';
            }
        }
    } elseif(is_string($sorts) && $sorts!='') {
        $url .= 'sort='.$sorts.'&';
    }
    if($page == 1) {
        return substr($url, 0, -1);
    }
    $url .= 'page='.$page;
    return $url;
}

function show_date($date, $is_time=true, $has_second = true) {
    if(empty($date)) {
        return '';
    }
    $format = 'd-m-Y';
    if($is_time) {
        $format = 'd-m-Y H:i:s';
        if(!$has_second) {
            $format = 'd-m-Y H:i';
        }
    }
    return date($format, strtotime($date));
}
function remove_symbols($string, $is_number=true, $is_vietnamese=true, $is_lower=false, $is_space=true, $special='', $only_number = false){
    $basic = 'a-zA-Z';
    if($only_number) {
        $basic ='';
    }
    if($is_number) {
        $basic .= '0-9';
    }
    $utf8 = '';
    if($is_vietnamese) {
        $utf8 = 'áàảãạăắặằẳẵâấầẩẫậđéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵÁÀẢÃẠĂẮẶẰẲẴÂẤẦẨẪẬĐÉÈẺẼẸÊẾỀỂỄỆÍÌỈĨỊÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢÚÙỦŨỤƯỨỪỬỮỰÝỲỶỸỴ';
    }
    if($is_lower) {
        $string = strtolower($string);
    }
    if(!$is_space) {
        $string = str_replace(' ','',$string);
    }
    $string = preg_replace( '/^[^'.$basic . $utf8 .'\s+]+/iu', '', $string );
    $string = preg_replace( '/[^'.$basic . $utf8 . $special . '\s+]+/iu', '', $string );
    return $string;
}

function get_book_detail($id) {
    $data = Yii::$app->cache->getOrSet('book_detail_'.$id, function () use ($id) {
        $book = app\models\Book::find()->where(array('id'=>$id))->one();
        if(empty($book)) {
            return null;
        }
        $tmp = $book->to_array();
        $tmp['tags'] = array();
        $tmp['authors'] = array();
        $tmp['last_chapter_read'] = false;
        $tmp['last_chapter_id'] = 0;
        $tmp['last_chapter_name'] = '';
        foreach($book->bookTags as $book_tag) {
            $tmp_tag = $book_tag->tag;
            if($tmp_tag->status == 0) { continue; }
            if($tmp_tag->type == 0) {
                $tmp['tags'][] = $tmp_tag->to_array(array('id', 'name'));
            } else {
                $tmp['authors'][] = $tmp_tag->to_array(array('id', 'name'));
            }
        }
        $tmp['chapters'] = array();
        foreach ($book->chapters as $stt => $chapter) {
            if($chapter->status == 0) { continue; }
            if($stt == 0) {
                $tmp['last_chapter_id'] = $chapter->id;
                $tmp['last_chapter_name'] = $chapter->name;
            }
            $tmp_chapter = $chapter->to_array(array('id','name'));
            $tmp_chapter['read'] = false;
            $tmp_chapter['release_date'] = date('d-m-Y H:i', strtotime($chapter->created_at));
            $tmp['chapters'][] = $tmp_chapter;
        }
        return $tmp;
    });
    if(empty($data)) {
        Yii::$app->cache->delete('book_detail_'.$id);
    }
    return $data;
}

function get_user_groups($user_id) {
    return Yii::$app->cache->getOrSet('user_groups_'.$user_id, function () use ($user_id) {
        $db_groups = app\models\Group::find()->where(array('user_id'=>$user_id, 'status'=>1))->all();
        $groups = array();
        foreach ($db_groups as $group) {
            $tmp_group = $group->to_array(array('id', 'name'));
            $book_ids = array();
            foreach ($group->follows as $follow) {
                $book_ids[] = $follow->book_id;
            }
            $count = app\models\Book::find()->where(array('id'=>$book_ids,'status'=>1))->count();
            $tmp_group['name'] .= ' ('.$count.')';
            $groups[] = $tmp_group;
        }
        return $groups;
    });
}

function get_tags() {
    return Yii::$app->cache->getOrSet('tags_list', function () {
        $tag_fields = array('id', 'name', 'stt', 'type');
        $tags = app\models\Tag::find()->select($tag_fields)->where(array('status' => 1))->orderBy(array('stt' => SORT_ASC, 'name' => SORT_ASC, 'id' => SORT_DESC))->all();
        $tag_data = array();
        foreach ($tags as $tag) {
            $tmp = $tag->to_array(array('id', 'name', 'type'));
            $tmp['is_checked'] = false;
            $book_ids = array();
            foreach ($tag->bookTags as $book_tag) {
                $book_ids[] = $book_tag->book_id;
            }
            $tmp['count'] = app\models\Book::find()->where(array('id'=>$book_ids,'status'=>1))->count();
            $tag_data[] = $tmp;
        }
        return $tag_data;
    });
}
function filter_values($data, $options) {
    $result = array();
    foreach ($data as $k => $v) {
        if(!empty($options) && !in_array($k, $options)) {
            continue;
        }
        $result[$k] = $v;
    }
    return $result;
}
function get_chapter_detail($id) {
    $data = Yii::$app->cache->getOrSet('chapter_detail_'.$id, function () use ($id) {
        $chapter = app\models\Chapter::find()->where(array('id'=>$id, 'status'=>1))->one();
        if(empty($chapter)) {
            return null;
        }
        $tmp = $chapter->to_array();
        $tmp['book'] = array(
            'id' => $chapter->book->id,
            'name' => $chapter->book->name,
            'make_read' => false,
            'is_following' => false,
            'unread' => 0
        );
        $tmp['images'] = array();
        foreach ($chapter->images as $image) {
            if($image->status == 0) { continue; }
            $tmp['images'][] = $image->to_array(array('id', 'image'));
        }
        return $tmp;
    });
    if(empty($data)) {
        Yii::$app->cache->delete('chapter_detail_'.$id);
    }
    return $data;
}