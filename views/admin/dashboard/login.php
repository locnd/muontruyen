<div class="login login-v2" data-pageload-addclass="animated fadeIn">
    <div class="login-header">
        <div class="brand">
            <span class="logo"></span> Đăng nhập
            <small>Dành cho quản trị viên</small>
        </div>
        <div class="icon">
            <i class="fa fa-sign-in"></i>
        </div>
    </div>

    <div class="login-content">
        <form method="POST" class="margin-bottom-0">
            <input id="crsf_token" type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
            <div class="form-group m-b-20">
                <?php $option1 = array(
                    'type' => 'text',
                    'name' => 'username',
                    'value' => empty($model['username'])?'':$model['username'],
                    'class' => 'form-control input-lg',
                    'placeholder' => 'Tên đăng nhập',
                    'required' => true,
                    'minlength' => 4,
                    'maxlength' => 32
                );
                echo_input($option1); ?>
                <?php form_error($model, 'username'); ?>
            </div>
            <div class="form-group m-b-20">
                <?php $option2 = array(
                    'type' => 'password',
                    'name' => 'password',
                    'class' => 'form-control input-lg',
                    'placeholder' => 'Mật khẩu',
                    'required' => true,
                    'minlength' => 6,
                    'maxlength' => 64
                );
                echo_input($option2); ?>
                <?php form_error($model, 'password'); ?>
            </div>
            <div class="checkbox m-b-20">
                <label>
                    <?php $option3 = array(
                        'type' => 'checkbox',
                        'name' => 'remember_me',
                        'value' => 1,
                        'checked' => empty($model['remember_me'])?false:true
                    );
                    echo_input($option3); ?> Ghi nhớ tài khoản
                </label>
            </div>
            <div class="login-buttons">
                <button type="submit" class="btn btn-success btn-block btn-lg">Đăng nhập</button>
            </div>
        </form>
    </div>
</div>

<ul class="login-bg-list clearfix">
    <li class="active"><a href="#" data-click="change-bg"><img src="../admin_assets/img/login-bg/bg-1.jpg" alt="" /></a></li>
    <li><a href="#" data-click="change-bg"><img src="../admin_assets/img/login-bg/bg-2.jpg" alt="" /></a></li>
    <li><a href="#" data-click="change-bg"><img src="../admin_assets/img/login-bg/bg-3.jpg" alt="" /></a></li>
    <li><a href="#" data-click="change-bg"><img src="../admin_assets/img/login-bg/bg-4.jpg" alt="" /></a></li>
    <li><a href="#" data-click="change-bg"><img src="../admin_assets/img/login-bg/bg-5.jpg" alt="" /></a></li>
    <li><a href="#" data-click="change-bg"><img src="../admin_assets/img/login-bg/bg-6.jpg" alt="" /></a></li>
</ul>