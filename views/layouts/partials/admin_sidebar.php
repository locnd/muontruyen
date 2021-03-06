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
            <li <?php echo (in_array($page_id, array('user_list','user_detail','user_create', 'user_edit')))?'class="active"':'';?>><a href="/admin/user"><i class="fa fa-group"></i> <span><?php echo 'Users'; ?></span></a></li>
            <li <?php echo (in_array($page_id, array('book_list','book_detail')))?'class="active"':'';?>><a href="/admin/book"><i class="fa fa-book"></i> <span><?php echo 'Books'; ?></span></a></li>
            <li <?php echo (in_array($page_id, array('chapter_list','chapter_detail')))?'class="active"':'';?>><a href="/admin/chapter"><i class="fa fa-file"></i> <span><?php echo 'Chapters'; ?></span></a></li>
            <li <?php echo $page_id=='setting'?'class="active"':'';?>><a href="/admin/setting"><i class="fa fa-cog"></i> <span><?php echo 'Settings'; ?></span></a></li>
            <li <?php echo $page_id=='report'?'class="active"':'';?>><a href="/admin/report"><i class="fa fa-bug"></i> <span><?php echo 'Reports'; ?></span></a></li>
            <li <?php echo $page_id=='device'?'class="active"':'';?>><a href="/admin/device"><i class="fa fa-tablet"></i> <span><?php echo 'Devices'; ?></span></a></li>
            <li><a href="javascript:;" class="sidebar-minify-btn" data-click="sidebar-minify"><i class="fa fa-angle-double-left"></i></a></li>
        </ul>
    </div>
</div>