<div id="sidebar" class="sidebar">
    <div data-scrollbar="true" data-height="100%">
        <ul class="nav">
            <li class="nav-profile">
                <div class="image">
                    <a href="javascript:;"><img src="<?php echo \Yii::$app->session->get('avatar', '/uploads/users/default.png'); ?>" alt="" /></a>
                </div>
                <div class="info">
                    <?php echo \Yii::$app->session->get('name', ''); ?>
                    <small class="f-s-13"><?php echo 'Administrator'; ?></small>
                </div>
            </li>
        </ul>
        <ul class="nav">
            <li class="nav-header f-s-13"><?php echo 'Navigator'; ?></li>
            <?php $page_id = isset($this->params['page_id'])?$this->params['page_id']:''; ?>
            <li <?php echo ($page_id == 'dashboard' || $page_id == '')?'class="active"':'';?>><a href="/admin"><i class="fa fa-home"></i> <span><?php echo 'Dashboard'; ?></span></a></li>
            <li class="has-sub<?php echo (in_array($page_id, array('user_list','user_create', 'user_edit', 'user_detail')))?' active':'';?>">
                <a href="javascript:;">
                    <b class="caret pull-right"></b>
                    <i class="fa fa-group"></i>
                    <span><?php echo 'Users'; ?></span>
                </a>
                <ul class="sub-menu">
                    <li <?php echo (in_array($page_id, array('user_list', 'user_edit', 'user_detail')))?'class="active"':'';?>><a href="/admin/user"><?php echo 'List'; ?></a></li>
                    <li <?php echo ($page_id == 'user_create')?'class="active"':'';?>><a href="/admin/user/create"><?php echo 'Add'; ?></a></li>
                </ul>
            </li>

            <li><a href="javascript:;" class="sidebar-minify-btn" data-click="sidebar-minify"><i class="fa fa-angle-double-left"></i></a></li>
        </ul>
    </div>
</div>