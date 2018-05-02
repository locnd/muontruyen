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
    <link rel="icon" href="../assets/img/favicon.ico" />

    <link href="../admin_assets/css/font.css" rel="stylesheet">
    <link href="../admin_assets/plugins/jquery-ui/themes/base/minified/jquery-ui.min.css" rel="stylesheet" />
    <link href="../admin_assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="../admin_assets/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
    <link href="../admin_assets/css/animate.css" rel="stylesheet" />
    <link href="../admin_assets/css/style.css" rel="stylesheet" />
    <link href="../admin_assets/css/style-responsive.css" rel="stylesheet" />
    <link href="../admin_assets/css/theme.css" rel="stylesheet" />
    <link href="../admin_assets/css/custom.css" rel="stylesheet" />

    <script src="../admin_assets/plugins/pace/pace.js"></script>
</head>
<body class="pace-top">
<div id="page-loader" class="fade in"><span class="spinner"></span></div>

<div class="login-cover">
    <div class="login-cover-image"><img src="../admin_assets/img/login-bg/bg-1.jpg" data-id="login-cover-image" alt="" /></div>
    <div class="login-cover-bg"></div>
</div>

<div id="page-container" class="fade">
    <?php echo $content; ?>
</div>

<script src="../admin_assets/plugins/jquery/jquery-1.9.1.min.js"></script>
<script src="../admin_assets/plugins/jquery/jquery-migrate-1.1.0.min.js"></script>
<script src="../admin_assets/plugins/jquery-ui/ui/minified/jquery-ui.min.js"></script>
<script src="../admin_assets/plugins/bootstrap/js/bootstrap.min.js"></script>
<!--[if lt IE 9]>
<script src="../admin_assets/crossbrowserjs/html5shiv.js"></script>
<script src="../admin_assets/crossbrowserjs/respond.min.js"></script>
<script src="../admin_assets/crossbrowserjs/excanvas.min.js"></script>
<![endif]-->
<script src="../admin_assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../admin_assets/plugins/jquery-cookie/jquery.cookie.js"></script>

<script src="../admin_assets/js/login-v2.demo.js"></script>
<script src="../admin_assets/js/apps.js"></script>

<script>
    $(document).ready(function () {
        App.init();
        LoginV2.init();
    });
</script>
</body>
</html>