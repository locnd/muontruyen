
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard';?></a></li>
    <li class="active"><?php echo 'List';?> <?php echo 'Books';?></li>
</ol>
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
                            <table class="table table-striped table-bordered data-table admin-table">
                                <thead>
                                    <tr>
                                        <th><?php echo 'ID';?></th>
                                        <th><?php echo 'Title';?></th>
                                        <th><?php echo 'Image';?></th>
                                        <th><?php echo 'Url';?></th>
                                        <th><?php echo 'Views';?></th>
                                        <th><?php echo 'Status';?></th>
                                        <th><?php echo 'Created date';?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book) { ?>
                                        <tr id="item-<?php echo $book->id; ?>">
                                            <td><?php echo $book->id; ?></td>
                                            <td class="item-name"><?php echo $book->title; ?></td>
                                            <td><img class="show_profile_image mini-image" src="<?php echo $book->get_image(); ?>"></td>
                                            <td><?php echo $book->url; ?></td>
                                            <td><?php echo $book->count_views; ?></td>
                                            <td><?php echo $book->status == 1 ? 'Active' : 'Inactive'; ?></td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($book->created_at)); ?></td>
                                            <td>
                                                <a href="/admin/book/detail/<?php echo $book->id; ?>" class="btn btn-success btn-icon btn-circle btn-lg m-r-5"><i class="fa fa-search"></i></a>
                                                <?php if(empty($book->deleted_at)) { ?>
                                                <a onclick="delete_item('book', <?php echo $book->id; ?>)" class="btn btn-danger btn-icon btn-circle btn-lg"><i class="fa fa-trash"></i></a>
                                                <?php } else { ?>
                                                <a onclick="restore_item('book', <?php echo $book->id; ?>)" class="btn btn-danger btn-icon btn-circle btn-lg"><i class="fa fa-refresh"></i></a>
                                                <?php } ?>
                                            </td>
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