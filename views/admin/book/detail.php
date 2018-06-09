
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard'; ?></a></li>
    <li><a href="/admin/book"><?php echo 'List Books'; ?></a></li>
    <li class="active">Book Detail</li>
</ol>

<input id="crsf_token" type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
<input type="hidden" id="book_id" value="<?php echo $book->id;?>">
<div class="profile-container">
    <div class="profile-section">
        <div class="profile-info">
            <div class="table-responsive">
                <table class="table table-profile tr-height-55 td-field-140">
                    <thead>
                        <tr>
                            <th style="text-align: right">
                                <a onclick="open_edit('name')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                            </th>
                            <th class="po-re">
                                <h4 id="cur_name"><?php echo $book->name;?></h4>
                                <input type="text" class="form-control" id="new_name" value="<?php echo $book->name;?>" style="display: none">
                                <button onclick="save('name')" id="btn_name" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="highlight">
                            <td class="field"><?php echo 'ID'; ?></td>
                            <td><?php echo $book->id;?></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Image'; ?><br>
                                <a onclick="open_edit('image')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                            </td>
                            <td>
                                <div id="cur_image" class="profile-image" style="width: 200px;">
                                    <img id="show_profile_image" class="show_profile_image editable" src="<?php echo $book->get_image(); ?>">
                                </div>
                                <input type="file" class="form-control" id="new_image" style="display: none">
                                <button onclick="save('image')" id="btn_image" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Slug'; ?></td>
                            <td><?php echo $book->slug;?></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Url'; ?></td>
                            <td><a href="<?php echo $book->url; ?>" target="_blank"><?php echo $book->url;?></a></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field">
                                <?php echo 'Description'; ?><br>
                                <a onclick="open_edit('description')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                            </td>
                            <td>
                                <span id="cur_description"><?php echo $book->description;?></span>
                                <textarea class="form-control" id="new_description" style="height:200px;display: none"><?php echo $book->description;?></textarea>
                                <button onclick="save('description')" id="btn_description" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Count Follows'; ?></td>
                            <td><?php echo $book->count_follows;?></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Count Views'; ?></td>
                            <td><?php echo $book->count_views;?></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Status'; ?><br>
                                <a onclick="open_edit('status')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                            </td>
                            <td>
                                <span id="cur_status"><?php echo $book->status == 1 ? 'Active' : 'Inactive'; ?></span>
                                <select class="form-control" id="new_status" style="display: none">
                                    <option value="0" <?php echo ($book->status == 0)?'selected':''; ?>>Inactive</option>
                                    <option value="1" <?php echo ($book->status == 1)?'selected':''; ?>>Active</option>
                                </select>
                                <button onclick="save('status')" id="btn_status" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Reload'; ?><br>
                                <a onclick="open_edit('will_reload')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                            </td>
                            <td>
                                <span id="cur_will_reload"><?php echo $book->will_reload == 1 ? 'True' : 'False'; ?></span>
                                <select class="form-control" id="new_will_reload" style="display: none">
                                    <option value="0" <?php echo ($book->will_reload == 0)?'selected':''; ?>>False</option>
                                    <option value="1" <?php echo ($book->will_reload == 1)?'selected':''; ?>>True</option>
                                </select>
                                <button onclick="save('will_reload')" id="btn_will_reload" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                            </td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Release date'; ?></td>
                            <td><?php echo show_date($book->release_date);?></td>
                        </tr>
                        <tr class="highlight">
                            <td class="field"><?php echo 'Created date'; ?></td>
                            <td><?php echo show_date($book->created_at);?></td>
                        </tr>
                    <?php if($reports = app\models\Report::find()->where(array(
                        'book_id' => $book->id,
                        'chapter_id' => 0,
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
        <div class="row">
            <h4 style="float: left;margin-left: 10px;"><?php echo $total; ?> chương</h4>
            <a onclick="ignore_book()" style="padding: 3px 7px;float: right;margin-right: 10px;" class="btn btn-danger"><i class="fa fa-trash"></i></a>
            <a onclick="sort_chapters()" style="padding: 3px 7px;float: right;margin-right: 10px;" href="javascript:;" title="<?php echo 'Sort'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-sort"></i></a>
            <a onclick="reset_chapters_name()" style="padding: 3px 7px;float: right;margin-right: 10px;" href="javascript:;" title="<?php echo 'Reset'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-refresh"></i></a>
            <input placeholder="remove string" type="text" class="form-control" id="tmp_name" style="width:200px;float:right;margin-right:10px;">
            <div style="clear:both;height:0px"></div>
            <div class="col-sm-12">
                <table class="table table-striped table-bordered data-table admin-table">
                    <thead>
                    <tr>
                        <th><?php echo 'ID';?></th>
                        <th><?php echo 'Name';?></th>
                        <th><?php echo 'Url';?></th>
                        <th><?php echo 'Stt';?></th>
                        <th><?php echo 'Reload';?></th>
                        <th><?php echo 'Images';?></th>
                        <th><?php echo 'Status';?></th>
                        <th><?php echo 'Created date';?></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($chapters as $chapter) { ?>
                        <tr id="item-<?php echo $chapter->id; ?>">
                            <td><?php echo $chapter->id; ?></td>
                            <td class="item-name"><a href="/admin/book/chapter/<?php echo $chapter->id; ?>"><?php echo $chapter->name; ?></a></td>
                            <td><a href="<?php echo $chapter->url; ?>" target="_blank"><?php echo $chapter->url; ?></td>
                            <td><?php echo $chapter->stt; ?></td>
                            <td><?php echo $chapter->will_reload == 1 ? 'True' :'False'; ?></td>
                            <td><?php echo count($chapter->images); ?></td>
                            <td><?php echo $chapter->status == 1 ? 'Active' : 'Inactive'; ?></td>
                            <td><?php echo date('d-m-Y H:i:s',strtotime($chapter->created_at)); ?></td>
                            <td><?php
                                $will_reload = $chapter->will_reload == 0 ? 'Make Reload' :'Cancel Reload';
                                ?>
                                <a onclick="will_reload(<?php echo $chapter->id; ?>)" style="padding: 3px 7px;<?php echo $will_reload=='Cancel Reload'?'background:lightgrey;border-color:lightgrey':'';?>" href="javascript:;" title="<?php echo 'Reload'; ?>" class="btn btn-primary"><?php echo $will_reload; ?></a>
                                <a onclick="forge_delete(<?php echo $chapter->id; ?>)" class="btn btn-danger pd-3-8"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="row">
                <?php echo \Yii::$app->view->render('/layouts/partials/pagging', array(
                    'url' => '/admin/book/detail/'.$book->id,
                    'total' => $total,
                    'page' => $page,
                    'filters' => array(),
                    'limit' => $limit
                )); ?>
            </div>
        </div>
    </div>
</div>
<script>
    function open_edit(attr) {
        $('#cur_'+attr).toggle();
        $('#new_'+attr).toggle();
        $('#btn_'+attr).toggle();
    }
    function save(attr) {
        var url = '/ajax/editbook';
        if(attr == 'image') {
            var data = new FormData();
            data.append('book_id', $('#book_id').val());
            data.append('key', attr);
            data.append('value', $('#new_image')[0].files[0]);
            data.append('_csrf', $('#crsf_token').val());
            $.ajax({
                url: url,
                type: "POST",
                data: data,
                enctype: 'multipart/form-data',
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(result) {
                if(result.success) {
                    window.location.reload();
                } else {
                    alert(result.message);
                }
            });
            return true;
        }
        var params = {};
        params['book_id'] = $('#book_id').val();
        params['key'] = attr;
        params['value'] = $('#new_'+attr).val();
        params['_csrf'] = $('#crsf_token').val();
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
    function sort_chapters() {
        if(confirm('Bạn có chắc là muốn sắp xếp lại chương truyện không?')) {
            var params = {};
            params['book_id'] = $('#book_id').val();
            params['_csrf'] = $('#crsf_token').val();
            var url = '/ajax/sortchapters';
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
    function reset_chapters_name() {
        if(confirm('Bạn có chắc là muốn reset lại tên các chương truyện không?')) {
            var params = {};
            params['book_id'] = $('#book_id').val();
            params['_csrf'] = $('#crsf_token').val();
            params['tmp_name'] = $('#tmp_name').val();
            var url = '/ajax/resetchaptername';
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
    function mark_fixed() {
        if(confirm('Bạn đã hoàn thành sửa truyện?')) {
            var params = {};
            params['book_id'] = $('#book_id').val();
            params['chapter_id'] = 0;
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
    function forge_delete(chapter_id) {
        if(confirm('Bạn có chắc là muốn xoá chương truyện không?')) {
            var params = {};
            params['item_id'] = chapter_id;
            params['item_type'] = 'chapter';
            params['_csrf'] = $('#crsf_token').val();
            var url = '/ajax/deleteitem';
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
    function will_reload(chapter_id) {
        if(confirm('Bạn có chắc là muốn load lại chương truyện không?')) {
            var params = {};
            params['chapter_id'] = chapter_id;
            params['_csrf'] = $('#crsf_token').val();
            var url = '/ajax/reloadchapter';
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
    function ignore_book() {
        if(confirm('Bạn có chắc là xoá truyện không?')) {
            var params = {};
            params['book_id'] = $('#book_id').val();
            params['_csrf'] = $('#crsf_token').val();
            var url = '/ajax/ignorebook';
            $.ajax({
                url: url,
                type: 'POST',
                data: params,
                dataType: 'json',
                success: function(result){
                    if(result.success) {
                        window.location.href = "/admin/book";
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