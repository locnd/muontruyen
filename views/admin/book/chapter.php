<?php $book = $chapter->book; ?>
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard'; ?></a></li>
    <li><a href="/admin/book"><?php echo 'List Books'; ?></a></li>
    <li><a href="/admin/book/detail/<?php echo $book->id; ?>"><?php echo 'Book Detail'; ?></a></li>
    <li class="active">Chapter Detail</li>
</ol>

<input id="crsf_token" type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
<div class="profile-container">
    <div class="profile-section">
        <div class="profile-info">
            <div class="table-responsive">
                <table class="table table-profile tr-height-55 td-field-140">
                    <thead>
                        <tr>
                            <th style="text-align: right">
                            </th>
                            <th class="po-re" style="position: relative;">
                                <h4 id="cur_title"><?php echo $book->name;?></h4>
                                <input type="hidden" id="book_id" value="<?php echo $book->id;?>">
                                <input type="text" class="form-control" id="new_title" value="<?php echo $book->name;?>" style="display: none">
                                <button onclick="save('name')" id="btn_title" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Chapter'; ?></td>
                            <td><?php echo $chapter->name;?></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Url'; ?></td>
                            <td><a href="<?php echo $chapter->url; ?>" target="_blank"><?php echo $chapter->url;?></a></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Count Views'; ?></td>
                            <td><?php echo count($chapter->reads);?></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Created date'; ?></td>
                            <td><?php echo show_date($chapter->created_at);?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <input type="hidden" id="chapter_id" value="<?php echo $chapter->id;?>">
            <h4 style="float: left;margin-left: 10px;"><?php echo count($chapter->images); ?> ảnh</h4>
            <div class="clear0"></div>
            <form id="add-image-form" style="padding: 0 10px;">
            </form>
            <div class="clear5"></div>
            <a id="save-btn" style="margin-left: 10px;width: 50px;display:none" class="dl-default-btn fl-l" onclick="save_images()" href="javascript:;">Save</a>
            <a class="dl-default-btn fl-l" style="margin-left: 10px;" onclick="add_image()" href="javascript:;"><i class="fa fa-plus"></i></a>
            <div class="clear5"></div>
            <?php foreach ($chapter->images as $image) { ?>
                <img style="width:100%;max-width: 500px" src="<?php echo $image->get_image(); ?>">
                <div class="clear5"></div>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    function add_image() {
        var stt = <?php echo count($chapter->images); ?> + $('.a-new-image').length + 1;
        var html = '<div class="a-new-image">';
        html += '<input placeholder="URL" type="text" class="form-control img-url" name="url[]">';
        html += '<input value='+stt+' placeholder="STT" type="text" class="form-control img-stt" name="stt[]">';
        html += '</div>';
        $('#add-image-form').append(html);
        $('#save-btn').show();
    }
    function save_images() {
        var url = '/ajax/addimage';
        var data = $('#add-image-form').serialize();
        data += '&chapter_id='+$('#chapter_id').val();
        data += '&_csrf='+$('#crsf_token').val();
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(result){
                if(result.success) {
                    window.location.reload();
                } else {
                    alert(result.message);
                }
            },
            error: function( xhr ) {
                window.location.reload();
            }
        });
    }
    function open_edit(attr) {
        $('#cur_'+attr).toggle();
        $('#new_'+attr).toggle();
        $('#btn_'+attr).toggle();
    }
    function save(attr) {
        var params = {};
        params['book_id'] = $('#book_id').val();
        params['key'] = attr;
        params['value'] = $('#new_'+attr).val();
        params['_csrf'] = $('#crsf_token').val();
        var url = '/ajax/editbook';
        $.ajax({
            url: url,
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function(result){
                if(result.success) {
                    window.location.reload();
                } else {
                    alert(result.message);
                }
            },
            error: function( xhr ) {
                window.location.reload();
            }
        });
    }
</script>