<?php

namespace app\controllers;

use app\models\Bookmark;
use app\models\Report;
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
use app\models\Device;

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
        ini_set('memory_limit', '-1');
        $device_id = Yii::$app->request->get('device_id','');
        $app_version = Yii::$app->request->get('app_version','');
        $device_type = Yii::$app->request->get('device_type','');
        if(!empty($device_id)) {
            $device_model = new Device();
            $device_model->add_device($device_id, $app_version, $device_type);
        }

        $fields = array(
            'id','name','image', 'release_date', 'slug'
        );
        $books = Book::find()->select($fields)->where(array('status'=>Book::ACTIVE));
        $total = $books->count();

        $limit = get_limit('mobile_limit');
        $total_page = ceil($total / $limit);
        $page = max((int) getParam('page', 1),1);
        $page = min($page, $total_page);
        $offset = ($page - 1) * $limit;

        $books->limit($limit)->offset($offset);

        $sort = (int) Yii::$app->request->get('sort',0);
        $sort_array = array();
        if($sort == 1) {
            $sort_array['count_views'] = SORT_DESC;
        }
        if($sort == 2) {
            $sort_array['count_follows'] = SORT_DESC;
        }
        if($sort == 3) {
            $sort_array['name'] = SORT_ASC;
        }
        if($sort == 4) {
            $sort_array['name'] = SORT_DESC;
        }
        $sort_array['release_date'] = SORT_DESC;
        $sort_array['id'] = SORT_DESC;
        $books->orderBy($sort_array);
        $books = $books->all();

        $data = array();
        foreach ($books as $book) {
            $tmp = $book->to_array(array('id','name', 'image', 'release_date'));
            if(!empty($book->lastChapter)) {
                $tmp['last_chapter_id'] = $book->lastChapter->id;
                $tmp['last_chapter_name'] = $book->lastChapter->name;
            }
            $tmp['tags'] = array();
            $tmp['authors'] = array();
            foreach($book->bookTags as $book_tag) {
                if($book_tag->tag->status == Tag::INACTIVE) {
                    continue;
                }
                $pos = strpos($book_tag->tag->name, 'Tác giả');
                if($pos === false) {
                    $tmp['tags'][] = $book_tag->tag->to_array(array('id', 'name'));
                } else {
                    $tmp_tag = $book_tag->tag->to_array(array('id', 'name'));
                    $tmp_tag['name'] = str_replace('Tác giả: ', '', $tmp_tag['name']);
                    $tmp['authors'][] = $tmp_tag;
                }
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
        return array(
            'success' => true,
            'data' => $data,
            'count_pages' => $total_page
        );
    }
    public function actionBook()
    {
        ini_set('memory_limit', '-1');
        $id = (int) Yii::$app->request->get('id',0);
        $fields = array(
            'id','name','description', 'image', 'count_views', 'slug'
        );
        $book = Book::find()->select($fields)->where(array('id'=>$id, 'status'=>Book::ACTIVE))->one();
        if(empty($book)) {
            return array(
                'success' => false,
                'message' => 'Truyện này không khả dụng'
            );
        }
        $options = array(
            'is_following' => false,
            'make_read' => false,
            'unread' => 0
        );
        $groups = array();
        $user = $this->check_user();
        if(!empty($user->id)) {
            foreach ($user->groups as $group) {
                if($group->status == Group::INACTIVE) { continue; }
                $groups[] = $group->to_array(array('id', 'name'));
            }
            $books_ids = array();
            foreach ($user->follows as $follow) {
                if($follow->book_id == $book->id) {
                    $options['is_following'] = true;
                    if($follow->status == Follow::UNREAD) {
                        $follow->status = Follow::READ;
                        $follow->save();
                        $options['make_read'] = true;
                    }
                }
                if ($follow->status == Follow::UNREAD) {
                    $books_ids[] = $follow->book_id;
                }
            }
            if($options['make_read']) {
                $options['unread'] = Book::find()->where(array('id' => $books_ids, 'status' => Book::ACTIVE))->count();
            }
        }
        $book->count_views = $book->count_views + 1;
        $book->save();
        $book_data = $book->to_array(array('id','name','description', 'image', 'count_views'));
        $chapters = array();
        foreach ($book->chapters as $chapter) {
            if($chapter->status == Chapter::INACTIVE) {
                continue;
            }
            $tmp_data = $chapter->to_array(array('id','name'));
            $tmp_data['read'] = false;
            $tmp_data['release_date'] = date('d-m-Y H:i', strtotime($chapter->created_at));
            if(!empty($user->id) && Read::find()->where(array('user_id'=>$user->id, 'chapter_id'=>$chapter->id))->count() > 0) {
                $tmp_data['read'] = true;
            }
            $chapters[] = $tmp_data;
        }

        if(!empty($user->id) && $user->is_admin) {
            $tag_fields = array(
                'id', 'name', 'vn_name'
            );
            $tags = Tag::find()->select($tag_fields)->where(array('status' => Tag::ACTIVE))->orderBy(array('stt' => SORT_ASC, 'slug' => SORT_ASC, 'id' => SORT_DESC))->all();
            $tag_data = array();
            foreach ($tags as $tag) {
                $tmp = $tag->to_array(array('id', 'name', 'stt'));
                $tmp['is_checked'] = false;
                if (BookTag::find()->where(array('tag_id' => $tag->id, 'book_id' => $book->id))->count() > 0) {
                    $tmp['is_checked'] = true;
                }
                $tag_data[] = $tmp;
            }
        } else {
            $book_tags = BookTag::find()->where(array('book_id' => $book->id))->all();
            $tag_data = array();
            foreach ($book_tags as $book_tag) {
                $tmp = $book_tag->tag->to_array(array('id', 'name', 'stt', 'status'));
                if($tmp['status'] == Tag::INACTIVE) {
                    continue;
                }
                $tmp['is_checked'] = true;
                $tag_data[] = $tmp;
            }
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
        ini_set('memory_limit', '-1');
        $id = (int) Yii::$app->request->get('id',0);
        $fields = array('id', 'book_id','name');
        $chapter = Chapter::find()->select($fields)->where(array('id'=>$id, 'status'=>Chapter::ACTIVE))->one();
        if(empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Chương truyện này không khả dụng'
            );
        }
        $book = $chapter->book;
        $book_data = $chapter->book->to_array(array('id','name'));
        $user = $this->check_user();
        if(!empty($user->id) && Read::find()->where(array('user_id'=>$user->id, 'chapter_id'=>$chapter->id))->count() == 0) {
            $read = new Read();
            $read->user_id = $user->id;
            $read->chapter_id = $chapter->id;
            $read->save();
        }
        $book_data['make_read'] = false;
        $book_data['unread'] = 0;
        if(!empty($user->id)) {
            $follow = Follow::find()->where(array('book_id'=>$book->id,'user_id'=>$user->id))->one();
            if(!empty($follow)) {
                $follow->status = Follow::READ;
                $follow->save();
                $book_data['make_read'] = true;
                $books_ids = array();
                foreach ($user->follows as $u_follow) {
                    if ($u_follow->status == Follow::UNREAD) {
                        $books_ids[] = $u_follow->book_id;
                    }
                }
                $book_data['unread'] = Book::find()->where(array('id'=>$books_ids,'status'=>Book::ACTIVE))->count();
            }
        }
        $images = array();
        foreach ($chapter->images as $image) {
            $images[] = $image->to_array(array('id', 'image'));
        }
        $book->count_views = $book->count_views + 1;
        $book->save();
        $check_bookmark = 0;
        if(!empty($user->id)) {
            $check_bookmark = Bookmark::find()->where(array(
                'user_id' => $user->id, 'chapter_id' => $chapter->id, 'status' => 1))->count();
        }
        $chapters = array();
        foreach ($book->chapters as $chap) {
            $chapters[] = $chap->to_array(array('id', 'name'));
        }
        return array(
            'success' => true,
            'data' => $chapter,
            'chapters' => $chapters,
            'book' => $book_data,
            'images' => $images,
            'is_bookmark' => $check_bookmark > 0 ? true : false
        );
    }
    public function actionBookforsearch() {
        ini_set('memory_limit', '-1');
        $books = Book::find()->select(['id','name'])->where(array('status'=>Book::ACTIVE))->all();
        return array(
            'success' => true,
            'data' => $books
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
    public function actionMakebookmark() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $book_id = (int) Yii::$app->request->post('book_id',0);
        $chapter_id = (int) Yii::$app->request->post('chapter_id',0);
        $check_book = Book::find()->where(array('status'=>Book::ACTIVE, 'id'=>$book_id))->count();
        if($check_book == 0) {
            return array(
                'success' => false,
                'message' => 'Truyện không khả dụng'
            );
        }
        $check_chapter = Chapter::find()->where(array('status'=>Chapter::ACTIVE, 'id'=>$chapter_id))->count();
        if($check_chapter == 0) {
            return array(
                'success' => false,
                'message' => 'Truyện không khả dụng'
            );
        }
        $bookmark = Bookmark::find()->where(array('book_id'=>$book_id, 'chapter_id'=>$chapter_id))->one();
        if(empty($bookmark)) {
            $bookmark = new Bookmark();
            $bookmark->user_id = $user->id;
            $bookmark->book_id = $book_id;
            $bookmark->chapter_id = $chapter_id;
            $bookmark->status = 1;
            $bookmark->save();
            $is_bookmark = true;
        } else {
            if($bookmark->status == 1) {
                $bookmark->status = 0;
                $is_bookmark = false;
            } else {
                $bookmark->status = 1;
                $is_bookmark = true;
            }
            $bookmark->save();
        }
        return array(
            'success' => true,
            'data' => $is_bookmark
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
        ini_set('memory_limit', '-1');
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
        $total_page = 1;
        $limit = get_limit('mobile_limit');
        $page = max((int) getParam('page', 1),1);
        if(!empty($books_ids)) {
            $fields = array('id','name', 'image', 'release_date', 'slug');
            $books = Book::find()->select($fields)->where(array('id' => $books_ids, 'status' => Book::ACTIVE));
            $total = $books->count();
            $total_page = ceil($total / $limit);
            $page = min($page, $total_page);
            $offset = ($page - 1) * $limit;
            $books->limit($limit)->offset($offset)->orderBy(['release_date' => SORT_DESC, 'id' => SORT_DESC]);
            $books = $books->all();
        }
        $data = array();
        foreach ($books as $book) {
            $tmp = $book->to_array(array('id','name', 'image', 'release_date'));
            if(!empty($book->lastChapter)) {
                $tmp['last_chapter_id'] = $book->lastChapter->id;
                $tmp['last_chapter_name'] = $book->lastChapter->name;
            }
            $data[] = $tmp;
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
            'groups' => $groups,
            'count_pages' => $total_page
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
        $fields = array('id', 'name', 'vn_name');
        $tags = Tag::find()->select($fields)->where(array('status'=>Tag::ACTIVE))->orderBy(array('stt'=>SORT_ASC,'slug' => SORT_ASC, 'id' => SORT_DESC))->all();
        $data = array();
        foreach ($tags as $tag) {
            $tmp = $tag->to_array(array('id', 'name'));
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
        $user_options = array();
        $user_options['Số truyện theo dõi'] = count($user->follows);
        $user_options['Số chương đã đọc'] = count($user->reads);

        $options = array();
        if($user->is_admin == 1) {
            $options['Số thành viên'] = User::find()->count();
            $options['Số truyện'] = Book::find()->count();
            $options['Số truyện bị ẩn'] = Book::find()->where(array('status'=>Book::INACTIVE))->count();
            $options['Số chương'] = Chapter::find()->count();
            $options['Số chương bị ẩn'] = Chapter::find()->where(array('status'=>Chapter::INACTIVE))->count();
            $options['Số hình ảnh'] = Image::find()->count();

            $options['Cron'] = 'stop';
            $setting_model = new Setting();
            if($setting_model->get_setting('cron_running') != '') {
                $options['Cron'] = 'running';
            }
            $options['Will Reload'] = Book::find()->where(array('will_reload'=>1))->count().' - '.Chapter::find()->where(array('will_reload'=>1))->count();

            $options['Số báo lỗi'] = Report::find()->count();
            $options['Số báo lỗi mới'] = Report::find()->where(array('status'=>Report::STATUS_NEW))->count();
        }
        return array(
            'success' => true,
            'data' => array(
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'options' => $user_options
            ),
            'options' => $options
        );
    }
    public function actionReport() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }

        $content = strtolower(trim(Yii::$app->request->post('content', '')));
        if($content == '') {
            return array(
                'success' => false,
                'message' => 'Không có nội dung báo lỗi'
            );
        }

        $book_id = (int)Yii::$app->request->post('book_id', 0);
        $book = Book::find()->where(array('id'=>$book_id, 'status' => Book::ACTIVE))->count();
        if($book == 0) {
            return array(
                'success' => false,
                'message' => 'Không tìm thấy truyện'
            );
        }

        $chapter_id = (int)Yii::$app->request->post('chapter_id', 0);
        if($chapter_id > 0) {
            $chapter = Chapter::find()->where(array('id' => $chapter_id, 'status' => Chapter::ACTIVE))->count();
            if ($chapter == 0) {
                return array(
                    'success' => false,
                    'message' => 'Không tìm thấy chương truyện'
                );
            }
        }

        $count = Report::find()->where(array(
            'user_id' => $user->id,
            'book_id' => $book_id,
            'chapter_id' => $chapter_id,
        ))->count();
        if($count > 3) {
            return array(
                'success' => false,
                'message' => 'Bạn đã báo lỗi nhiều lần cho truyện này'
            );
        }
        $report = new Report();
        $report->user_id = $user->id;
        $report->book_id = $book_id;
        $report->chapter_id = $chapter_id;
        $report->content = $content;
        $report->save();
        return array(
            'success' => true
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
        ini_set('memory_limit', '-1');
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

        $chapter = Chapter::find()->select(array('id', 'book_id'))->where(array('id'=>$chapter_id, 'status' => Chapter::ACTIVE))->one();
        if(empty($chapter)) {
            return array(
                'success' => false,
                'message' => 'Không tìm thấy chương truyện'
            );
        }
        $chapters = Chapter::find()->select(array('id','name','stt','created_at'))->where(array('book_id'=>$chapter->book_id,'status' => Chapter::ACTIVE))->orderBy(['stt' => SORT_DESC, 'id' => SORT_DESC])->all();
        $chapters_data = array();
        $id1=0;$id2=0;
        for($i=0;$i<count($chapters);$i++) {
            $chapters_data[$i] = $chapters[$i]->to_array();
            $chapters_data[$i]['release_date'] = date('d-m-Y H:i', strtotime($chapters[$i]->created_at));
            if($chapters[$i]->id == $chapter_id) {
                if($is_down == 1 && !empty($chapters[$i+1])) {
                    $id1 = $i;
                    $id2 = $i+1;
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
                    $id1 = $i;
                    $id2 = $i-1;
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
            }
        }
        $tmp2 = $chapters_data[$id1];
        $chapters_data[$id1] = $chapters_data[$id2];
        $chapters_data[$id2] = $tmp2;
        return array(
            'success' => true,
            'data' => $chapters_data
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
        ini_set('memory_limit', '-1');
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
        if($action == 'name') {
            $book->name = $content;
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
        ini_set('memory_limit', '-1');
        $keyword = Yii::$app->request->get('keyword','');

        $fields = array('id','name', 'image', 'release_date', 'slug');
        $books = Book::find()->select($fields)->where(array('status'=>Book::ACTIVE))->andFilterWhere([
            'or',
            ['like', 'name', $keyword],
            ['like', 'slug', $keyword],
        ]);

        $is_full = (int) Yii::$app->request->get('is_full',0);
        if($is_full == 1) {
            $tag = Tag::find()->select(array('id'))->where(array('slug'=>'hoan-thanh'))->one();
            $book_tags = BookTag::find()->select(array('book_id'))->where(array('tag_id'=>$tag->id))->all();
            $book_ids = array();
            foreach($book_tags as $book_tag) {
                $book_ids[] = $book_tag->book_id;
            }
            $books->andWhere(array('id'=>$book_ids));
        }

        $total = $books->count();

        $setting_model = new Setting();
        $limit = $setting_model->get_setting('mobile_limit');
        if($limit != '') {
            $limit = (int) $limit;
        } else {
            $limit = Yii::$app->params['limit'];
            $setting_model->get_setting('mobile_limit', $limit);
        }

        $total_page = ceil($total / $limit);
        $page = max((int) getParam('page', 1),1);
        $page = min($page, $total_page);
        $offset = ($page - 1) * $limit;

        $books->limit($limit)->offset($offset)->orderBy(['release_date' => SORT_DESC, 'id' => SORT_DESC]);
        $books = $books->all();

        $data = array();
        foreach ($books as $book) {
            $tmp = $book->to_array();
            if(!empty($book->lastChapter)) {
                $tmp['last_chapter_id'] = $book->lastChapter->id;
                $tmp['last_chapter_name'] = $book->lastChapter->name;
            }
            $tmp['tags'] = array();
            $tmp['authors'] = array();
            foreach($book->bookTags as $book_tag) {
                if($book_tag->tag->status == Tag::INACTIVE) {
                    continue;
                }
                $pos = strpos($book_tag->tag->name, 'Tác giả');
                if($pos === false) {
                    $tmp['tags'][] = $book_tag->tag->to_array(array('id', 'name'));
                } else {
                    $tmp_tag = $book_tag->tag->to_array(array('id', 'name'));
                    $tmp_tag['name'] = str_replace('Tác giả: ', '', $tmp_tag['name']);
                    $tmp['authors'][] = $tmp_tag;
                }
            }
            $data[] = $tmp;
        }
        if(empty($data)) {
            return array(
                'success' => false,
                'message' => 'Không có truyện',
                'total' => 0,
                'count_pages' => 0
            );
        }
        return array(
            'success' => true,
            'data' => $data,
            'total' => $total,
            'count_pages' => $total_page
        );
    }

    public function actionTag() {
        ini_set('memory_limit', '-1');
        $tag_id = (int) Yii::$app->request->get('tag_id',0);
        $fields = array('id','name','vn_name');
        $tag = Tag::find()->select($fields)->where(array(
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
            $book_ids[] = $book_tag->book_id;
        }

        $is_full = (int) Yii::$app->request->get('is_full',0);
        if($is_full == 1) {
            $f_tag = Tag::find()->select(array('id'))->where(array('slug'=>'hoan-thanh'))->one();
            $book_tags = BookTag::find()->select(array('book_id'))->where(array('tag_id'=>$f_tag->id))->all();
            $f_book_ids = array();
            foreach($book_tags as $book_tag) {
                if(in_array($book_tag->book_id, $book_ids)) {
                    $f_book_ids[] = $book_tag->book_id;
                }
            }
            $book_ids = $f_book_ids;
        }

        $fields = array(
            'id','name', 'image', 'release_date', 'slug'
        );
        $books = Book::find()->select($fields)->where(array('id'=>$book_ids, 'status'=>Book::ACTIVE));

        $total = $books->count();

        $setting_model = new Setting();
        $limit = $setting_model->get_setting('mobile_limit');
        if($limit != '') {
            $limit = (int) $limit;
        } else {
            $limit = Yii::$app->params['limit'];
            $setting_model->get_setting('mobile_limit', $limit);
        }

        $total_page = ceil($total / $limit);
        $page = max((int) getParam('page', 1),1);
        $page = min($page, $total_page);
        $offset = ($page - 1) * $limit;

        $books->limit($limit)->offset($offset)->orderBy(['release_date' => SORT_DESC, 'id' => SORT_DESC]);
        $books = $books->all();

        $data = array();
        foreach ($books as $book) {
            $tmp = $book->to_array(array(
                'id','name', 'image', 'release_date'
            ));
            if(!empty($book->lastChapter)) {
                $tmp['last_chapter_id'] = $book->lastChapter->id;
                $tmp['last_chapter_name'] = $book->lastChapter->name;
            }
            $tmp['tags'] = array();
            $tmp['authors'] = array();
            foreach($book->bookTags as $book_tag) {
                if($book_tag->tag->status == Tag::INACTIVE) {
                    continue;
                }
                $pos = strpos($book_tag->tag->name, 'Tác giả');
                if($pos === false) {
                    $tmp['tags'][] = $book_tag->tag->to_array(array('id', 'name'));
                } else {
                    $tmp_tag = $book_tag->tag->to_array(array('id', 'name'));
                    $tmp_tag['name'] = str_replace('Tác giả: ', '', $tmp_tag['name']);
                    $tmp['authors'][] = $tmp_tag;
                }
            }
            $data[] = $tmp;
        }

        if(empty($data)) {
            return array(
                'success' => true,
                'message' => 'Không có truyện',
                'tag' => $tag->to_array(),
                'total' => 0
            );
        }
        return array(
            'success' => true,
            'data' => $data,
            'tag' => $tag->to_array(),
            'total' => $total,
            'count_pages' => $total_page
        );
    }

    public function actionSavebook() {
        ini_set('max_execution_time', 24*60*60);
        ini_set('memory_limit', '-1');
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $id = (int) Yii::$app->request->get('id',0);
        $fields = array(
            'id','name','description', 'image', 'release_date', 'slug'
        );
        $book = Book::find()->select($fields)->where(array('id'=>$id, 'status'=>Book::ACTIVE))->one();
        if(empty($book)) {
            return array(
                'success' => false,
                'message' => 'Truyện này không khả dụng'
            );
        }
        $book_data = $book->to_array(array('id','name','description', 'image', 'release_date'));
        if(!empty($book->lastChapter)) {
            $book_data['last_chapter_id'] = $book->lastChapter->id;
            $book_data['last_chapter_name'] = $book->lastChapter->name;
        }
        $book_data['tags'] = array();
        $book_data['authors'] = array();
        foreach($book->bookTags as $book_tag) {
            if($book_tag->tag->status == Tag::INACTIVE) {
                continue;
            }
            $pos = strpos($book_tag->tag->name, 'Tác giả');
            if($pos === false) {
                $book_data['tags'][] = $book_tag->tag->to_array(array('id', 'name'));
            } else {
                $tmp_tag = $book_tag->tag->to_array(array('id', 'name'));
                $tmp_tag['name'] = str_replace('Tác giả: ', '', $tmp_tag['name']);
                $book_data['authors'][] = $tmp_tag;
            }
        }
        $chapters = array();
        foreach ($book->chapters as $chapter) {
            $tmp_data = $chapter->to_array(array('id','name','stt'));
            $tmp_data['release_date'] = date('d-m-Y H:i', strtotime($chapter->created_at));
            $tmp_data['images'] = array();
            foreach($chapter->images as $image) {
                if($image->status == Image::INACTIVE) {
                    continue;
                }
                $tmp_data['images'][] = $image->get_image();
            }
            $tmp_data['read'] = false;
            if(!empty($user->id) && Read::find()->where(array('user_id'=>$user->id, 'chapter_id'=>$chapter->id))->count() > 0) {
                $tmp_data['read'] = true;
            }
            $chapters[] = $tmp_data;
        }
        return array(
            'success' => true,
            'data' => $book_data,
            'chapters' => $chapters
        );
    }
    public function actionMarkread() {
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $chapter_ids = Yii::$app->request->get('chapter_ids','');
        $chapter_ids_arr = explode(',', $chapter_ids);
        foreach ($chapter_ids_arr as $chapter_id) {
            if(!empty($chapter_id) && Read::find()->where(array('user_id'=>$user->id, 'chapter_id'=>$chapter_id))->count() == 0) {
                $read = new Read();
                $read->user_id = $user->id;
                $read->chapter_id = $chapter_id;
                $read->save();
            }
        }
        return array(
            'success' => true
        );
    }
    public function actionBookmark() {
        ini_set('memory_limit', '-1');
        $user = $this->check_user();
        if(!empty($user['error'])) {
            return array(
                'success' => false,
                'message' => $user['message']
            );
        }
        $books_ids = array();
        $chapter_ids = array();
        foreach ($user->bookmarks as $bookmark) {
            if($bookmark->status == 0) {
                continue;
            }
            $books_ids[] = $bookmark->book_id;
            if(empty($chapter_ids[$bookmark->book_id])) {
                $chapter_ids[$bookmark->book_id] = array();
            }
            $chapter_ids[$bookmark->book_id][] = $bookmark->chapter_id;
        }
        $fields = array('id','name', 'image', 'release_date', 'slug');
        $books = Book::find()->select($fields)->where(array('status'=>Book::ACTIVE, 'id' => $books_ids));
        $total = $books->count();

        $setting_model = new Setting();
        $limit = $setting_model->get_setting('mobile_limit');
        if($limit != '') {
            $limit = (int) $limit;
        } else {
            $limit = Yii::$app->params['limit'];
            $setting_model->get_setting('mobile_limit', $limit);
        }

        $total_page = ceil($total / $limit);
        $page = max((int) getParam('page', 1),1);
        $page = min($page, $total_page);
        $offset = ($page - 1) * $limit;

        $books->limit($limit)->offset($offset)->orderBy(['release_date' => SORT_DESC, 'id' => SORT_DESC]);
        $books = $books->all();

        $data = array();
        $chapter_fields = array('id','name','created_at');
        foreach ($books as $book) {
            if(empty($chapter_ids[$book->id])) {
                continue;
            }
            $tmp = $book->to_array(array('id','name', 'image', 'release_date'));
            if(!empty($book->lastChapter)) {
                $tmp['last_chapter_id'] = $book->lastChapter->id;
                $tmp['last_chapter_name'] = $book->lastChapter->name;
            }
            $tmp['tags'] = array();
            $tmp['authors'] = array();
            foreach($book->bookTags as $book_tag) {
                if($book_tag->tag->status == Tag::INACTIVE) {
                    continue;
                }
                $pos = strpos($book_tag->tag->name, 'Tác giả');
                if($pos === false) {
                    $tmp['tags'][] = $book_tag->tag->to_array(array('id', 'name'));
                } else {
                    $tmp_tag = $book_tag->tag->to_array(array('id', 'name'));
                    $tmp_tag['name'] = str_replace('Tác giả: ', '', $tmp_tag['name']);
                    $tmp['authors'][] = $tmp_tag;
                }
            }
            $chapters = Chapter::find()->select($chapter_fields)->where(array('status'=>Chapter::ACTIVE, 'id' => $chapter_ids[$book->id]))->orderBy(['stt' => SORT_DESC, 'id' => SORT_DESC])->all();
            $tmp['chapters'] = array();
            foreach ($chapters as $chapter) {
                $tmp_data = $chapter->to_array(array('id','name'));
                $tmp_data['read'] = true;
                $tmp_data['release_date'] = date('d-m-Y H:i', strtotime($chapter->created_at));
                $tmp['chapters'][] = $tmp_data;
            }
            $data[] = $tmp;
        }
        if(empty($data)) {
            return array(
                'success' => false,
                'message' => 'Không có truyện',
                'total' => 0,
                'count_pages' => 0
            );
        }
        return array(
            'success' => true,
            'data' => $data,
            'total' => $total,
            'count_pages' => $total_page
        );
    }
    public function actionLoginfacebook() {
        $user_model = new User();
        $errors = $user_model->login_facebook(Yii::$app->request->post());
        if(!empty($errors->id)) {
            unset($errors->password);
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
        return array(
            'success' => false,
            'message' => empty($errors['message'])?'Đăng nhập thất bại':$errors['message']
        );
    }
}
