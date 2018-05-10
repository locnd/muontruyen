<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard';?></a></li>
    <li class="active"><?php echo 'List';?> <?php echo 'Books';?></li>
</ol>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-inverse">
            <div class="panel-body border-area">
                <h3>Thông tin chung</h3>
                <div class="clear5"></div>
                <label class="col-md-3">Số thành viên</label>
                <div class="col-md-9"><?php echo $count_users; ?></div>
                <div class="clear5"></div>
                <label class="col-md-3">Số truyện</label>
                <div class="col-md-9"><?php echo $count_books; ?></div>
                <div class="clear5"></div>
                <label class="col-md-3">Số chương</label>
                <div class="col-md-9"><?php echo $count_chapters; ?></div>
                <div class="clear5"></div>
                <label class="col-md-3">Số hình ảnh</label>
                <div class="col-md-9"><?php echo $count_images; ?></div>
            </div>
            <div class="panel-body border-area">
                <h3>Gửi tin nhắn</h3>
                <form method="POST">
                    <div class="clear20"></div>
                    <input id="crsf_token" type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
                    <div class="form-group">
                        <label class="col-md-3 control-label txt-right l-h-35"><?php echo 'User';?> <span class="item_required">*</span></label>
                        <div class="col-md-9">
                            <?php
                            $option_user = array(
                                'name' => 'user_id',
                                'type' => 'select',
                                'class' => 'form-control dl_combobox'
                            );
                            $users_arr = array(
                                ''=>'Please select'
                            );
                            foreach ($users as $tmp_user) {
                                $users_arr[$tmp_user->id] = $tmp_user->name;
                            }
                            echo_input($option_user, $users_arr); ?>
                        </div>
                    </div>
                    <div class="clear5"></div>
                    <div class="form-group">
                        <label class="col-md-3 control-label txt-right l-h-35"><?php echo 'Message';?> <span class="item_required">*</span></label>
                        <div class="col-md-9">
                            <?php
                            $option_message = array(
                                'name' => 'message',
                                'type' => 'textarea',
                                'class' => 'form-control',
                                'style' => 'height:120px',
                                'required' => true
                            );
                            echo_input($option_message); ?>
                        </div>
                    </div>
                    <div class="clear10"></div>
                    <div class="form-group">
                        <label class="col-md-3 control-label txt-right l-h-35"></label>
                        <div class="col-md-9">
                            <input type="submit" class="btn btn-primary" value="Send">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>