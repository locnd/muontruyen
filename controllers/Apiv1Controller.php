<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\Url;
use yii\filters\Cors;
use app\models\Book;
use app\models\Image;
use app\models\Chapter;
use app\models\User;
use app\models\Follow;
use app\models\Group;
use app\models\Scraper;
use app\models\Read;
use app\models\Server;
use app\models\ScraperLog;
use app\models\Setting;
use app\models\Tag;
use app\models\BookTag;

class Apiv1Controller extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'corsFilter'  => [
                'class' => Cors::className(),
                'cors'  => [
                    'Origin'                           => ['*'],
                    'Access-Control-Request-Headers'   => ['*'],
                    'Access-Control-Request-Method'    => ['POST','GET'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age'           => 3600
                ],
            ],

        ]);
    }
    public function actionHome()
    {
        $model = new Book();
        $books = $model->get_data(array('status'=>Book::ACTIVE), array(), array(
            'order' => array('release_date' => SORT_DESC, 'id' => SORT_DESC),
            'page' => max(1, (int) Yii::$app->request->get('page',1))
        ));
        $data = array();
        foreach ($books as $book) {
            $tmp = $book->to_array();
            if(!empty($book->chapters[0])) {
                $tmp['title'] .= ' - '.$book->chapters[0]->name;
            }
            $data[] = $tmp;
        }
        if(empty($data)) {
            return array(
                'success' => false,
                'message' => 'Không có truyện',
                'count_pages' => 0
            );
        }
        $count_books = Book::find()->where(array('status'=>Book::ACTIVE))->count();
        $limit = Yii::$app->params['limit'];
        return array(
            'success' => true,
            'data' => $data,
            'count_pages' => ceil($count_books/$limit)
        );
    }
    public function actionBook()
    {
        $model = new Book();
        $id = (int) Yii::$app->request->get('id',0);
        $count_books = Book::find()->where(array('status'=>Book::ACTIVE))->count();
        $book = $model->get_data(array('id'=>$id, 'status'=>Book::ACTIVE),array(), array(),true);
        if(empty($book)) {
            return array(
                'success' => false,
                'message' => 'Truyện này không khả dụng'
            );
        }
        $options = array(
            'is_following' => false,
            'make_read' => false,
        );
        $groups = array();
        $user = $this->check_user();
        if(!empty($user->id)) {
            $groups = $user->groups;
            $follow = Follow::find()->where(array('user_id'=>$user->id, 'book_id'=>$book->id))->one();
            if(!empty($follow)) {
                $options['is_following'] = true;
                if($follow->status == Follow::UNREAD) {
                    $follow->status = Follow::READ;
                    $follow->save();
                    $options['make_read'] = true;
                    $books_ids = array();
                    foreach ($user->follows as $follow) {
                        if ($follow->status == Follow::UNREAD) {
                            $books_ids[] = $follow->book_id;
                        }
                    }
                    $options['unread'] = Book::find()->where(array('id' => $books_ids, 'status' => Book::ACTIVE))->count();
                }
            }
        }
        $book->count_views = $book->count_views + 1;
        $book->save();
        $book_data = $book->to_array();
        $chapters = array();
        foreach ($book->chapters as $chapter) {
            if($chapter->status == Chapter::INACTIVE) {
                continue;
            }
            $tmp_data = $chapter->to_array();
            $tmp_data['read'] = false;
            $tmp_data['release_date'] = date('d-m-Y H:i', strtotime($chapter->created_at));
            if(!empty($user->id) && Read::find()->where(array('user_id'=>$user->id, 'chapter_id'=>$chapter->id))->count() > 0) {
                $tmp_data['read'] = true;
            }
            $chapters[] = $tmp_data;
        }
        $tags = Tag::find()->where(array('status' => Tag::ACTIVE))->all();
        $tag_data = array();
        foreach ($tags as $tag) {
            $tmp = $tag->to_array();
            $tmp['is_checked'] = false;
            if(BookTag::find()->where(array('tag_id' => $tag->id, 'book_id'=>$book->id))->count() > 0) {
                $tmp['is_checked'] = true;
            }
            $tag_data[] = $tmp;
        }
        return array(
            'success' => true,
            'data' => $book_data,
            'chapters' => $chapters,
            'options' => $options,
            'groups' => $groups,
            'tags' => $tag_data
        );
    }
    public function actionChapter()
    {
        $id = (int) Yii::$app->request->get('id',0);
        $chapter = Chapter::find()->where(array('id'=>$id, 'status'=>Chapter::ACTIVE))->one();
        if(empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Chương truyện này không khả dụng'
            );
        }
        $book = $chapter->book;
        $book_data = $chapter->book->to_array();
        $user = $this->check_user();
        if(!empty($user->id) && Read::find()->where(array('user_id'=>$user->id, 'chapter_id'=>$chapter->id))->count() == 0) {
            $read = new Read();
            $read->user_id = $user->id;
            $read->chapter_id = $chapter->id;
            $read->save();
        }
        $imgs = Image::find()->where(array('chapter_id'=>$chapter->id, 'status'=>Image::ACTIVE))->orderBy(['stt' => SORT_ASC])->all();
        $images = array();
        foreach ($imgs as $image) {
            $images[] = $image->to_array();
        }
        return array(
            'success' => true,
            'data' => $chapter,
            'chapters' => $book->chapters,
            'book' => $book_data,
            'images' => $images
        );
    }
    public function actionRegister() {
        $user_model = new User();
        $errors = $user_model->createUser(Yii::$app->request->post());
        if(!empty($errors->id)) {
            return array(
                'success' => true,
                'data' => $errors
            );
        }
        unset($errors->password);
        return array(
            'success' => false,
            'data' => $errors,
            'message' => 'Đăng ký thất bại'
        );
    }
    public function actionLogin() {
        $user_model = new User();
        $errors = $user_model->login(Yii::$app->request->post());
        if(!empty($errors->id)) {
            $books_ids = array();
            foreach ($errors->follows as $follow) {
                if ($follow->status == Follow::UNREAD) {
                    $books_ids[] = $follow->book_id;
                }
            }
            return array(
                'success' => true,
                'data' => $errors,
                'unread' => Book::find()->where(array('id' => $books_ids, 'status' => Book::ACTIVE))->count()
            );
        }
        unset($errors->password);
        return array(
            'success' => false,
            'data' => $errors,
            'message' => 'Đăng nhập thất bại'
        );
    }
    private function check_user() {
        $token = trim(Yii::$app->request->post('token',''));
        if(empty($token)) {
            $token = trim(Yii::$app->request->get('token',''));
            if(empty($token)) {
                return array(
                    'error' => 1,
                    'message' => 'Vui lòng đăng nhập'
                );
            }
        }
        $user = User::find()->where(array('token'=>$token))->one();
        if(empty($user)) {
            return array(
                'error' => 2,
                'message' => 'Vui lòng đăng nhập lại'
            );
        }
        if($user->status != User::ACTIVE) {
            return array(
                'error' => 3,
                'message' => 'Tài khoản của bạn đã bị khoá'
            );
        }
        return $user;
    }
    public function actionUnread() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $books_ids = array();
        foreach ($user->follows as $follow) {
            if ($follow->status == Follow::UNREAD) {
                $books_ids[] = $follow->book_id;
            }
        }
        return array(
            'success' => true,
            'data' => Book::find()->where(array('id' => $books_ids, 'status' => Book::ACTIVE))->count()
        );
    }
    public function actionMakefollow() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $book_id = (int) Yii::$app->request->post('book_id',0);
        $group_id = (int) Yii::$app->request->post('group_id',0);
        $group_name = Yii::$app->request->post('group_name','');

        $check_book = Book::find()->where(array('status'=>Book::ACTIVE, 'id'=>$book_id))->count();
        if($check_book == 0) {
            return array(
                'success' => false,
                'message' => 'Truyện không khả dụng'
            );
        }
        if($group_id > 0) {
            $check_group = Group::find()->where(array('status'=>Group::ACTIVE, 'user_id'=>$user->id))->count();
            if($check_group == 0) {
                return array(
                    'success' => false,
                    'message' => 'Nhóm không khả dụng'
                );
            }
        }
        $follow = Follow::find()->where(array('user_id'=>$user->id, 'book_id'=>$book_id))->one();
        if(!empty($follow)) {
            $follow->book->count_follows = max(0,$follow->book->count_follows-1);
            $follow->book->save();
            $follow->delete();
            return array(
                'success' => true,
                'data' => false
            );
        }
        $follow = new Follow();
        $follow->user_id = $user->id;
        $follow->book_id = $book_id;
        $follow->status = Follow::READ;
        if($group_id > 0) {
            $follow->group_id = $group_id;
        } else {
            if(count($user->groups) >= 5) {
                return array(
                    'success' => false,
                    'message' => 'Chỉ có thể tạo tối đa 5 nhóm'
                );
            }
            $group = new Group();
            $group->user_id = $user->id;
            $group->name = $group_name;
            $group->status = Group::ACTIVE;
            $group->save();
            $follow->group_id = $group->id;
        }
        $follow->save();
        $follow->book->count_follows = $follow->book->count_follows+1;
        $follow->book->save();

        return array(
            'success' => true,
            'data' => true,
            'groups' => $user->groups
        );
    }
    public function actionFollow() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $books_ids = array();
        $tab = (int) Yii::$app->request->get('tab',0);
        if($tab > 0) {
            foreach ($user->follows as $follow) {
                if ($follow->group_id == $tab) {
                    $books_ids[] = $follow->book_id;
                }
            }
        } else {
            foreach ($user->follows as $follow) {
                if ($follow->status == Follow::UNREAD) {
                    $books_ids[] = $follow->book_id;
                }
            }
        }
        $books = array();
        if(!empty($books_ids)) {
            $books = Book::find()->where(array('id' => $books_ids, 'status' => Book::ACTIVE))->orderBy(['release_date' => SORT_DESC])->all();
        }
        $data = array();
        foreach ($books as $book) {
            $data[] = $book->to_array();
        }
        $groups = array();
        foreach ($user->groups as $group) {
            $books_ids = array();
            foreach ($user->follows as $follow) {
                if ($follow->group_id == $group->id) {
                    $books_ids[] = $follow->book_id;
                }
            }
            $groups[] = array(
                'id' => $group->id,
                'name' => $group->name,
                'count' => Book::find()->where(array('id' => $books_ids, 'status' => Book::ACTIVE))->count()
            );
        }
        return array(
            'success' => true,
            'data' => $data,
            'groups' => $groups
        );
    }
    public function actionDisable()
    {
        $user = $this->check_user();
        if (!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        if (empty($user->is_admin)) {
            return array(
                'success' => false,
                'message' => 'Không có quyền thực hiện'
            );
        }
        $book_id = (int)Yii::$app->request->post('book_id', 0);
        $chapter_id = (int)Yii::$app->request->post('chapter_id', 0);
        if($book_id > 0 && $chapter_id == 0) {
            $book = Book::find()->where(array('id'=>$book_id, 'status' => Book::ACTIVE))->one();
            if(empty($book)) {
                return array(
                    'success' => false,
                    'message' => 'Không tìm thấy truyện'
                );
            }
            $book->status = Book::INACTIVE;
            $book->save();
        }elseif($book_id == 0 && $chapter_id > 0) {
            $chapter = Chapter::find()->where(array('id'=>$chapter_id, 'status' => Chapter::ACTIVE))->one();
            if(empty($chapter)) {
                return array(
                    'success' => false,
                    'message' => 'Không tìm thấy chương truyện'
                );
            }
            $chapter->status = Chapter::INACTIVE;
            $chapter->save();
        } else {
            return array(
                'success' => false,
                'message' => 'Không có truyện'
            );
        }
        return array(
            'success' => true
        );
    }
    public function actionReload() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        if(empty($user->is_admin)) {
            return array(
                'success' => false,
                'message' => 'Không có quyền thực hiện'
            );
        }
        $book_id = (int) Yii::$app->request->post('book_id',0);
        $chapter_id = (int) Yii::$app->request->post('chapter_id',0);
        if($book_id > 0 && $chapter_id == 0) {
            $book = Book::find()->where(array('id'=>$book_id, 'status' => Book::ACTIVE))->one();
            if(empty($book)) {
                return array(
                    'success' => false,
                    'message' => 'Không tìm thấy truyện'
                );
            }
            $book->will_reload = 1;
            $book->save();
        }elseif($book_id == 0 && $chapter_id > 0) {
            $chapter = Chapter::find()->where(array('id'=>$chapter_id, 'status' => Chapter::ACTIVE))->one();
            if(empty($chapter)) {
                return array(
                    'success' => false,
                    'message' => 'Không tìm thấy chương truyện'
                );
            }
            $chapter->will_reload = 1;
            $chapter->save();
        } else {
            return array(
                'success' => false,
                'message' => 'Không có truyện'
            );
        }
        return array(
            'success' => true
        );
    }
    public function actionTags() {
        $tags = Tag::find()->where(array('status'=>Tag::ACTIVE))->orderBy(array('slug' => SORT_ASC, 'id' => SORT_DESC))->all();
        $data = array();
        foreach ($tags as $tag) {
            $tmp = $tag->to_array();
            $tmp['count'] = BookTag::find()->where(array('tag_id'=>$tag->id))->count();
            $data[] = $tmp;
        }
        if(empty($data)) {
            return array(
                'success' => true,
                'message' => 'Không có thẻ tag',
                'total' => 0
            );
        }
        return array(
            'success' => true,
            'data' => $data,
            'total' => count($data)
        );
    }
    public function actionProfile() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $options = array();
        if($user->is_admin == 1) {
            $options['users'] = User::find()->count();
            $options['books'] = Book::find()->count();
            $options['in_active_books'] = Book::find()->where(array('status'=>Book::INACTIVE))->count();
            $options['chapters'] = Chapter::find()->count();
            $options['in_active_chapters'] = Chapter::find()->where(array('status'=>Chapter::INACTIVE))->count();
            $options['images'] = Image::find()->count();
            $options['running_scraper'] = 'stop';
            $options['running_reload'] = 'stop';
            $setting_model = new Setting();
            if($setting_model->get_setting('running_scraper') != '') {
                $options['running_scraper'] = 'running';
            }
            if($setting_model->get_setting('running_reload') != '') {
                $options['running_reload'] = 'running';
            }
        }
        return array(
            'success' => true,
            'data' => array(
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'follows' => count($user->follows),
                'reads' => count($user->reads)
            ),
            'options' => $options
        );
    }
    public function actionChangepassword() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $errors = $user->change_password(Yii::$app->request->post());
        if($errors === true) {
            return array(
                'success' => true,
                'data' => $errors
            );
        }
        return array(
            'success' => false,
            'data' => $errors,
            'message' => 'Thay đổi mật khẩu thất bại'
        );
    }
    public function actionMovechapter()
    {
        $user = $this->check_user();
        if (!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        if (empty($user->is_admin)) {
            return array(
                'success' => false,
                'message' => 'Không có quyền thực hiện'
            );
        }
        $chapter_id = (int)Yii::$app->request->post('chapter_id', 0);
        $is_down = (int)Yii::$app->request->post('is_down', 0);

        $chapter = Chapter::find()->where(array('id'=>$chapter_id, 'status' => Chapter::ACTIVE))->one();
        if(empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Không tìm thấy chương truyện'
            );
        }
        $chapters = Chapter::find()->where(array('book_id'=>$chapter->book_id,'status' => Chapter::ACTIVE))->orderBy(['stt' => SORT_DESC, 'id' => SORT_DESC])->all();
        for($i=0;$i<count($chapters);$i++) {
            if($chapters[$i]->id == $chapter_id) {
                if($is_down == 1 && !empty($chapters[$i+1])) {
                    $tmp = $chapters[$i]->stt;
                    $chapters[$i]->stt = $chapters[$i+1]->stt;
                    $chapters[$i]->save();
                    if($chapters[$i+1]->stt == $tmp) {
                        $chapters[$i+1]->stt = $tmp-1;
                    } else {
                        $chapters[$i+1]->stt = $tmp;
                    }
                    $chapters[$i+1]->save();
                }
                if($is_down == 0 && !empty($chapters[$i-1])) {
                    $tmp = $chapters[$i]->stt;
                    $chapters[$i]->stt = $chapters[$i-1]->stt;
                    $chapters[$i]->save();
                    if($chapters[$i-1]->stt == $tmp) {
                        $chapters[$i-1]->stt = $tmp+1;
                    } else {
                        $chapters[$i-1]->stt = $tmp;
                    }
                    $chapters[$i-1]->save();
                }
                break;
            }
        }
        return array(
            'success' => true,
            'data' => Chapter::find()->where(array('book_id'=>$chapter->book_id,'status' => Chapter::ACTIVE))->orderBy(['stt' => SORT_ASC, 'id' => SORT_DESC])->all()
        );
    }
    public function actionCreatetag()
    {
        $user = $this->check_user();
        if (!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        if (empty($user->is_admin)) {
            return array(
                'success' => false,
                'message' => 'Không có quyền thực hiện'
            );
        }
        $tag_name = trim(Yii::$app->request->post('tag_name', ''));
        if(empty($tag_name)) {
            return array(
                'success' => false,
                'message' => 'Hãy điền tên thẻ tag'
            );
        }
        $tag = Tag::find()->where(array('name'=>$tag_name, 'status' => Tag::ACTIVE))->one();
        if(empty($image)) {
            $tag = new Tag();
            $tag->name = $tag_name;
            $tag->slug = generate_key($tag_name);
            $tag->status = Tag::ACTIVE;
            $tag->save();
        }
        return array(
            'success' => true,
            'data' => $tag
        );
    }
    public function actionSavetags()
    {
        $user = $this->check_user();
        if (!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        if (empty($user->is_admin)) {
            return array(
                'success' => false,
                'message' => 'Không có quyền thực hiện'
            );
        }
        $book_id = (int) Yii::$app->request->post('book_id', 0);
        $book = Book::find()->where(array('id'=>$book_id, 'status' => Book::ACTIVE))->one();
        if(empty($book)) {
            return array(
                'success' => false,
                'message' => 'Không tìm thấy truyện'
            );
        }
        $tag_ids = strtolower(trim(Yii::$app->request->post('tag_ids', '')));
        $book->save_tags($tag_ids);
        return array(
            'success' => true
        );
    }
    public function actionMoveimage()
    {
        $user = $this->check_user();
        if (!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        if (empty($user->is_admin)) {
            return array(
                'success' => false,
                'message' => 'Không có quyền thực hiện'
            );
        }
        $image_id = (int)Yii::$app->request->post('image_id', 0);
        $is_down = (int)Yii::$app->request->post('is_down', 0);

        $image = Image::find()->where(array('id'=>$image_id, 'status' => Image::ACTIVE))->one();
        if(empty($image)) {
            return array(
                'success' => false,
                'message' => 'Không tìm thấy hình ảnh'
            );
        }
        $images = Image::find()->where(array('chapter_id'=>$image->chapter_id,'status' => Image::ACTIVE))->orderBy(['stt' => SORT_ASC, 'id' => SORT_DESC])->all();
        for($i=0;$i<count($images);$i++) {
            if($images[$i]->id == $image_id) {
                if($is_down == 1 && !empty($images[$i+1])) {
                    $tmp = $images[$i]->stt;
                    $images[$i]->stt = $images[$i+1]->stt;
                    $images[$i]->save();
                    if($images[$i+1]->stt == $tmp) {
                        $images[$i+1]->stt = $tmp-1;
                    } else {
                        $images[$i+1]->stt = $tmp;
                    }
                    $images[$i+1]->save();
                }
                if($is_down == 0 && !empty($images[$i-1])) {
                    $tmp = $images[$i]->stt;
                    $images[$i]->stt = $images[$i-1]->stt;
                    $images[$i]->save();
                    if($images[$i-1]->stt == $tmp) {
                        $images[$i-1]->stt = $tmp+1;
                    } else {
                        $images[$i-1]->stt = $tmp;
                    }
                    $images[$i-1]->save();
                }
                break;
            }
        }
        $imgs = Image::find()->where(array('chapter_id'=>$image->chapter_id,'status' => Image::ACTIVE))->orderBy(['stt' => SORT_ASC, 'id' => SORT_DESC])->all();
        $data = array();
        foreach ($imgs as $img) {
            $data[] = $img->to_array();
        }
        return array(
            'success' => true,
            'data' =>$data
        );
    }
    public function actionEditchapter() {
        $user = $this->check_user();
        if (!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        if (empty($user->is_admin)) {
            return array(
                'success' => false,
                'message' => 'Không có quyền thực hiện'
            );
        }
        $chapter_id = (int)Yii::$app->request->post('chapter_id', 0);
        $name = Yii::$app->request->post('name', '');

        $chapter = Chapter::find()->where(array('id'=>$chapter_id, 'status' => Chapter::ACTIVE))->one();
        if(empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Không tìm thấy chương truyện'
            );
        }
        if($name == '') {
            return array(
                'success' => false,
                'message' => 'Hãy điền thông tin'
            );
        }
        $chapter->name = $name;
        $chapter->save();
        return array(
            'success' => true
        );
    }
    public function actionEdit()
    {
        $user = $this->check_user();
        if (!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        if (empty($user->is_admin)) {
            return array(
                'success' => false,
                'message' => 'Không có quyền thực hiện'
            );
        }
        $book_id = (int)Yii::$app->request->post('book_id', 0);
        $action = Yii::$app->request->post('action', '');
        $content = Yii::$app->request->post('content', '');

        $book = Book::find()->where(array('id'=>$book_id, 'status' => Book::ACTIVE))->one();
        if(empty($book)) {
            return array(
                'success' => false,
                'message' => 'Không tìm thấy truyện'
            );
        }
        if($content == '') {
            return array(
                'success' => false,
                'message' => 'Hãy điền thông tin'
            );
        }
        if($action == 'title') {
            $book->title = $content;
            $book->save();
        }elseif($action == 'description') {
            $book->description = $content;
            $book->save();
        } else {
            return array(
                'success' => false,
                'message' => 'Hãy chọn thông tin'
            );
        }
        return array(
            'success' => true
        );
    }

    public function actionSearch() {
        $keyword = Yii::$app->request->get('keyword','');
        $books = Book::find()->where(array('status'=>Book::ACTIVE))->andFilterWhere([
            'or',
            ['like', 'title', $keyword],
            ['like', 'slug', $keyword],
        ])->orderBy(array('release_date' => SORT_DESC, 'id' => SORT_DESC))->all();

        $data = array();
        foreach ($books as $book) {
            $tmp = $book->to_array();
            if(!empty($book->chapters[0])) {
                $tmp['title'] .= ' - '.$book->chapters[0]->name;
            }
            $data[] = $tmp;
        }
        if(empty($data)) {
            return array(
                'success' => true,
                'message' => 'Không có truyện',
                'total' => 0
            );
        }
        return array(
            'success' => true,
            'data' => $data,
            'total' => count($data)
        );
    }

    public function actionTag() {
        $tag_id = (int) Yii::$app->request->get('tag_id',0);
        $tag = Tag::find()->where(array(
            'id' => $tag_id, 'status' => Tag::ACTIVE
        ))->one();
        if(empty($tag)) {
            return array(
                'success' => false,
                'message' => 'Thẻ tag không khả dụng'
            );
        }
        $book_ids = array();
        foreach ($tag->bookTags as $book_tag) {
            if(!in_array($book_tag->book_id, $book_ids)) {
                $book_ids[] = $book_tag->book_id;
            }
        }
        $books = Book::find()->where(array('id'=>$book_ids, 'status'=>Book::ACTIVE))->orderBy(array('release_date' => SORT_DESC, 'id' => SORT_DESC))->all();
        $data = array();
        foreach ($books as $book) {
            $tmp = $book->to_array();
            if(!empty($book->chapters[0])) {
                $tmp['title'] .= ' - '.$book->chapters[0]->name;
            }
            $data[] = $tmp;
        }
        if(empty($data)) {
            return array(
                'success' => true,
                'message' => 'Không có truyện',
                'total' => 0
            );
        }
        return array(
            'success' => true,
            'data' => $data,
            'tag' => $tag,
            'total' => count($data)
        );
    }
}
