
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard';?></a></li>
    <li class="active"><?php echo 'List';?> <?php echo 'Error';?> <?php echo ucfirst($type); ?></li>
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
                                        <th><?php echo 'Book';?></th>
                                        <?php if($type=='chapter') { ?>
                                        <th><?php echo 'Chapter';?></th>
                                        <?php } ?>
                                        <th><?php echo 'Status';?></th>
                                        <th><?php echo 'Register date';?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item) { ?>
                                        <tr id="item-<?php echo $item['id']; ?>">
                                            <td><?php echo $item['id']; ?></td>
                                            <td>
                                                <a href="/admin/book/detail/<?php echo $type=='chapter'?$item['book_id']:$item['id']; ?>">
                                                <?php if($type=='chapter') {
                                                    echo $item['book_name'];
                                                } else {
                                                    echo $item['name'];
                                                } ?>
                                                </a>
                                            </td>
                                            <?php if($type=='chapter') {?>
                                            <td>
                                                <a href="/admin/book/chapter/<?php echo $item['id']; ?>">
                                                    <?php echo $item['name']; ?>
                                                </a>
                                            </td>
                                            <?php } ?>
                                            <td><?php echo $item['status']==1 ? 'Active' : 'Inactive'; ?></td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($item['created_at'])); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>