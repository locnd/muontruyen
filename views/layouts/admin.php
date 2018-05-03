<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title><?php echo Yii::$app->params['meta_title']; ?></title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <meta content="<?php echo Yii::$app->params['meta_description']; ?>" name="description" />
    <meta content="<?php echo Yii::$app->params['meta_author']; ?>" name="author" />
    <meta content="<?php echo Yii::$app->params['meta_keywords']; ?>" name="keywords" />
    <link rel="icon" href="/assets/img/favicon.ico" />

    <link href="/admin_assets/css/font.css" rel="stylesheet">
    <link href="/admin_assets/plugins/jquery-ui/themes/base/jquery-ui.css" rel="stylesheet" />
    <link href="/admin_assets/plugins/bootstrap/css/bootstrap.css" rel="stylesheet" />
    <link href="/admin_assets/plugins/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="/admin_assets/plugins/bootstrap-combobox/css/bootstrap-combobox.css" rel="stylesheet" />
    <link href="/admin_assets/css/animate.css" rel="stylesheet" />
    <link href="/admin_assets/css/style.css" rel="stylesheet" />
    <link href="/admin_assets/css/style-responsive.css" rel="stylesheet" />
    <link href="/admin_assets/css/theme.css" rel="stylesheet" />
    <link href="/admin_assets/css/custom.css" rel="stylesheet" />

    <script src="/admin_assets/plugins/jquery/jquery-1.9.1.js"></script>
</head>
<body>
<div id="page-loader" class="fade in"><span class="spinner"></span></div>
<div id="page-container" class="fade page-sidebar-fixed page-header-fixed">
    <?php echo \Yii::$app->view->render('/layouts/partials/admin_header'); ?>
    <?php echo \Yii::$app->view->render('/layouts/partials/admin_sidebar'); ?>
    <div class="sidebar-bg"></div>
    <div id="content" class="content">
        <?php echo $content; ?>
    </div>
    <a href="javascript:;" class="btn btn-icon btn-circle btn-success btn-scroll-to-top fade" data-click="scroll-top"><i class="fa fa-angle-up"></i></a>
</div>
<div id="preview_image_modal" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="imageModal" aria-hidden="false">
    <div class="modal-content" data-dismiss="modal">
        <img class="image-fullsize">
    </div>
</div>

<script src="/admin_assets/plugins/jquery/jquery-migrate-1.1.0.js"></script>
<script src="/admin_assets/plugins/jquery-ui/ui/jquery-ui.js"></script>
<script src="/admin_assets/plugins/bootstrap/js/bootstrap.js"></script>
<!--[if lt IE 9]>
<script src="/admin_assets/crossbrowserjs/html5shiv.js"></script>
<script src="/admin_assets/crossbrowserjs/respond.min.js"></script>
<script src="/admin_assets/crossbrowserjs/excanvas.min.js"></script>
<![endif]-->
<script src="/admin_assets/plugins/slimscroll/jquery.slimscroll.js"></script>
<script src="/admin_assets/plugins/jquery-cookie/jquery.cookie.js"></script>
<script src="/admin_assets/plugins/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
<script src="/admin_assets/plugins/DataTables/media/js/jquery.dataTables.js"></script>
<script src="/admin_assets/plugins/DataTables/media/js/dataTables.bootstrap.js"></script>
<script src="/admin_assets/plugins/DataTables/extensions/Responsive/js/dataTables.responsive.js"></script>
<script src="/admin_assets/plugins/bootstrap-combobox/js/bootstrap-combobox.js"></script>
<script src="/admin_assets/js/apps.js"></script>
<script src="/admin_assets/js/custom.js"></script>
</body>
</html>