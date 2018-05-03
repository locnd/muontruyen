<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Mượn Truyện</title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
    <meta content="Mượn truyện về đọc chút nhé" name="description" />
    <meta content="Lộc Nguyễn" name="author" />
    <meta content="mượn truyện, truyện tranh, truyện hay" name="keywords" />
    <!-- ================== BEGIN BASE CSS STYLE ================== -->
    <link href="/assets/css/font.css" rel="stylesheet" />
    <link href="/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="/assets/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" />
    <link href="/assets/css/animate.min.css" rel="stylesheet" />
    <link href="/assets/css/style.min.css" rel="stylesheet" />
    <link href="/assets/css/style-responsive.min.css" rel="stylesheet" />
    <link href="/assets/css/theme.css" rel="stylesheet" />
    <link href="/assets/css/custom.css" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon.ico">
    <!-- ================== END BASE CSS STYLE ================== -->
    <!-- ================== BEGIN BASE JS ================== -->
    <script src="/assets/plugins/pace/pace.min.js"></script>
    <script src="/assets/plugins/jquery/jquery-1.9.1.min.js"></script>
    <!-- ================== END BASE JS ================== -->
</head>
<body>
<div class="dl-overlay"></div>
<div id="alert-flash" class="alert"></div>
<div id="header" class="header navbar navbar-default navbar-fixed-top">
    <?php echo \Yii::$app->view->render('/layouts/partials/front_header'); ?>
</div>

<div id="content" class="content">
    <div class="container">
        <div class="row row-space-30">
            <div class="col-md-12">
                <?= $content ?>
            </div>
        </div>
    </div>
</div>

<div id="footer-copyright" class="footer-copyright">
    <?php echo \Yii::$app->view->render('/layouts/partials/front_footer'); ?>
</div>

<!-- ================== BEGIN BASE JS ================== -->
<script src="/assets/plugins/jquery/jquery-migrate-1.1.0.min.js"></script>
<script src="/assets/plugins/bootstrap/js/bootstrap.min.js"></script>
<!--[if lt IE 9]>
<script src="/assets/crossbrowserjs/html5shiv.js"></script>
<script src="/assets/crossbrowserjs/respond.min.js"></script>
<script src="/assets/crossbrowserjs/excanvas.min.js"></script>
<![endif]-->
<script src="/assets/plugins/jquery-cookie/jquery.cookie.js"></script>
<script src="/assets/js/theme.min.js"></script>
<!-- ================== END BASE JS ================== -->
<script>
    $(document).ready(function() {
        App.init();
    });
</script>
</body>
</html>
