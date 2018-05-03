
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard'; ?></a></li>
    <li><a href="/admin/book"><?php echo 'List Books'; ?></a></li>
    <li class="active">Book Detail</li>
</ol>

<input id="crsf_token" type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
<div class="profile-container">
    <div class="profile-section">
        <div class="profile-left">
            <div class="profile-image">
                <img id="show_profile_image" class="show_profile_image editable" src="<?php echo $book->get_image(); ?>">
                <i class="fa fa-user hide"></i>
            </div>
        </div>
        <div class="profile-right">
            <div class="profile-info">
                <div class="table-responsive">
                    <table class="table table-profile tr-height-55 td-field-140">
                        <thead>
                            <tr>
                                <th style="text-align: right">
                                    <a onclick="open_edit('title')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                                </th>
                                <th class="po-re" style="position: relative;">
                                    <h4 id="cur_title"><?php echo $book->title;?></h4>
                                    <input type="hidden" id="book_id" value="<?php echo $book->id;?>">
                                    <input type="text" class="form-control" id="new_title" value="<?php echo $book->title;?>" style="display: none">
                                    <button onclick="save('title')" id="btn_title" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="highlight">
                                <td class="field"><?php echo 'ID'; ?></td>
                                <td><?php echo $book->id;?></td>
                            </tr>
                            <tr class="highlight">
                                <td class="field"><?php echo 'Slug'; ?></td>
                                <td><?php echo $book->slug;?></td>
                            </tr>
                            <tr class="highlight">
                                <td class="field">
                                    <?php echo 'Description'; ?><br>
                                    <a onclick="open_edit('description')" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                                </td>
                                <td>
                                    <span id="cur_description"><?php echo $book->description;?></span>
                                    <textarea class="form-control" id="new_description" style="display: none"><?php echo $book->description;?></textarea>
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
                                <td class="field"><?php echo 'Release date'; ?></td>
                                <td><?php echo show_date($book->release_date);?></td>
                            </tr>
                            <tr class="highlight">
                                <td class="field"><?php echo 'Created date'; ?></td>
                                <td><?php echo show_date($book->created_at);?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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