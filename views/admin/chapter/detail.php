<?php $book = $chapter->book; ?>
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard'; ?></a></li>
    <li><a href="/admin/book"><?php echo 'List Books'; ?></a></li>
    <li><a href="/admin/book/detail/<?php echo $book->id; ?>"><?php echo 'Book Detail'; ?></a></li>
    <li class="active">Chapter Detail</li>
</ol>

<input id="crsf_token" type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
<input type="hidden" id="book_id" value="<?php echo $book->id;?>">
<input type="hidden" id="chapter_id" value="<?php echo $chapter->id;?>">
<div class="profile-container">
    <div class="profile-section">
        <div class="profile-info">
            <div class="table-responsive">
                <table class="table table-profile tr-height-55 td-field-140">
                    <thead>
                        <tr>
                            <th style="text-align: right">
                            </th>
                            <th class="po-re">
                                <h4 id="cur_title"><?php echo $book->name;?>
                                    <a style="float:right;margin-top:-10px" onclick="delete_chapter()" class="btn btn-danger btn-icon btn-circle btn-lg"><i class="fa fa-trash"></i></a>
                                </h4>
                                <input type="text" class="form-control" id="new_title" value="<?php echo $book->name;?>" style="display: none">
                                <button onclick="save('name')" id="btn_title" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="highlight">
                            <td class="field">
                                <a onclick="open_edit('name')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                            </td>
                            <td>
                                <span id="cur_name"><?php echo $chapter->name;?></span>
                                <input type="text" class="form-control" id="new_name" value="<?php echo $chapter->name;?>" style="display: none">
                                <button onclick="save('name')" id="btn_name" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </td>
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
                            <td class="field"><?php echo 'Status'; ?><br>
                                <a onclick="open_edit('status')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                            </td>
                            <td>
                                <span id="cur_status"><?php echo $chapter->status == 1 ? 'Active' : 'Inactive'; ?></span>
                                <select class="form-control" id="new_status" style="display: none">
                                    <option value="0" <?php echo ($chapter->status == 0)?'selected':''; ?>>Inactive</option>
                                    <option value="1" <?php echo ($chapter->status == 1)?'selected':''; ?>>Active</option>
                                </select>
                                <button onclick="save('status')" id="btn_status" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Reload'; ?><br>
                                <a onclick="open_edit('will_reload')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                            </td>
                            <td>
                                <span id="cur_will_reload"><?php echo $chapter->will_reload == 1 ? 'True' : 'False'; ?></span>
                                <select class="form-control" id="new_will_reload" style="display: none">
                                    <option value="0" <?php echo ($chapter->will_reload == 0)?'selected':''; ?>>False</option>
                                    <option value="1" <?php echo ($chapter->will_reload == 1)?'selected':''; ?>>True</option>
                                </select>
                                <button onclick="save('will_reload')" id="btn_will_reload" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Created date'; ?></td>
                            <td><?php echo show_date($chapter->created_at);?></td>
                        </tr>
                        <?php if($reports = app\models\Report::find()->where(array(
                                'book_id' => $book->id,
                                'chapter_id' => $chapter->id,
                                'status' => app\models\Report::STATUS_NEW,
                            ))->count() > 0) { ?>
                            <tr class="highlight">
                                <td class="field"></td>
                                <td><a href="javascript:;" onclick="mark_fixed()">Báo đã sửa lỗi</a></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="profile-info">
            <h4 style="float: left;"><?php echo count($images); ?> ảnh</h4>
            <div class="clear0"></div>
            <form id="add-image-form" style="display:none">
                <textarea name="list_images" style="width: 100%;min-height: 100px;margin-bottom: 10px"></textarea>
            </form>
            <div class="clear5"></div>
            <a id="save-btn" style="margin-right: 10px;width: 50px;display:none" class="dl-default-btn fl-l" onclick="save_images()" href="javascript:;">Save</a>
            <a id="add-btn" class="dl-default-btn fl-l" onclick="add_image()" href="javascript:;"><i class="fa fa-refresh"></i></a>
            <div class="clear5"></div>
            <?php foreach ($images as $image) { ?>
                <img style="width:100%;max-width: 500px" src="<?php echo $image->get_image(); ?>">
                <div class="clear5"></div>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    function add_image() {
        $('#add-image-form').show();
        $('#save-btn').show();
        $('#add-btn').hide();
    }
    function mark_fixed() {
        if(confirm('Bạn đã hoàn thành sửa truyện?')) {
            var params = {};
            params['book_id'] = $('#book_id').val();
            params['chapter_id'] = $('#chapter_id').val();
            params['_csrf'] = $('#crsf_token').val();
            var url = '/ajax/fixed';
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
        params['chapter_id'] = $('#chapter_id').val();
        params['key'] = attr;
        params['value'] = $('#new_'+attr).val();
        params['_csrf'] = $('#crsf_token').val();
        var url = '/ajax/editchapter';
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
    function delete_chapter() {
        if(confirm('Bạn có thực sự muốn xoá chương này không?')) {
            var params = {};
            params['chapter_id'] = $('#chapter_id').val();
            params['_csrf'] = $('#crsf_token').val();
            var url = '/ajax/deletechapter';
            $.ajax({
                url: url,
                type: 'POST',
                data: params,
                dataType: 'json',
                success: function(result){
                    if(result.success) {
                        var book_id = $('#book_id').val();
                        window.location.href = '/admin/book/detail/'+book_id;
                    } else {
                        alert(result.message);
                    }
                },
                error: function( xhr ) {
                    window.location.reload();
                }
            });
        }
    }
</script>