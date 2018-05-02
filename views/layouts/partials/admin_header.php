<div id="header" class="header navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <a href="/admin" class="navbar-brand"><span class="navbar-logo"></span> <?php echo \Yii::$app->params['meta_title']; ?></a>
            <button type="button" class="navbar-toggle" data-click="sidebar-toggled">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <ul class="nav navbar-nav navbar-right">
            <li class="dropdown navbar-user">
                <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="<?php echo \Yii::$app->session->get('avatar', '/uploads/users/default.png'); ?>" alt="" />
                    <span class="hidden-xs"><?php echo \Yii::$app->session->get('name', ''); ?></span> <b class="caret"></b>
                </a>
                <ul class="dropdown-menu animated fadeInLeft">
                    <li class="arrow"></li>
                    <li><a href="/admin/profile"><?php echo 'Profile'; ?></a></li>
                    <li><a href="/admin/profile/edit">Sửa hồ sơ</a></li>
                    <li class="divider"></li>
                    <li><a href="/admin/logout"><?php echo 'Logout'; ?></a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>