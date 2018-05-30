
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard';?></a></li>
    <li class="active"><?php echo 'List';?> <?php echo 'Books';?></li>
</ol>
<input id="crsf_token" type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-inverse">
            <div class="panel-body">
                <div>
                    <div class="row">
                        <?php echo \Yii::$app->view->render('/layouts/partials/search_form', array('filters'=>$filters)); ?>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <input id="book_url" placeholder="book_url" class="form-control fl-l w-100-40" type="text">
                            <a class="dl-default-btn fl-r" onclick="add_book()" href="javascript:;"><i class="fa fa-plus"></i></a>
                            <table class="table table-striped table-bordered data-table admin-table">
                                <thead>
                                    <tr>
                                        <th><?php echo 'ID';?></th>
                                        <th><?php echo 'Name';?></th>
                                        <th><?php echo 'Image';?></th>
                                        <th><?php echo 'Url';?></th>
                                        <th><?php echo 'Chapters';?></th>
                                        <th><?php echo 'Status';?></th>
                                        <th><?php echo 'Release date';?></th>
                                        <th><?php echo 'Created date';?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book) { ?>
                                        <tr id="item-<?php echo $book->id; ?>">
                                            <td><?php echo $book->id; ?></td>
                                            <td class="item-name"><a href="/admin/book/detail/<?php echo $book->id; ?>"><?php echo $book->name; ?></a></td>
                                            <td><img class="show_profile_image mini-image" src="<?php echo $book->get_image(); ?>"></td>
                                            <td><a href="<?php echo $book->url; ?>" target="_blank"><?php echo $book->url; ?></a></td>
                                            <td><?php echo count($book->chapters); ?></td>
                                            <td><?php echo $book->status == 1 ? 'Active' : 'Inactive'; ?></td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($book->created_at)); ?></td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($book->release_date)); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <?php echo \Yii::$app->view->render('/layouts/partials/pagging', array(
                            'url' => '/admin/book',
                            'total' => $total,
                            'page' => $page,
                            'filters' => $filters,
                            'limit' => $limit
                        )); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function add_book() {
        var book_url = $.trim($('#book_url').val());
        if(book_url == '') {
            alert('Hãy điền url của truyện');
            return true;
        }
        var params = {};
        params['book_url'] = book_url;
        params['_csrf'] = $('#crsf_token').val();
        var url = '/ajax/addbook';
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