
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard';?></a></li>
    <li class="active"><?php echo 'List';?> <?php echo 'Reports';?></li>
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
                                        <th><?php echo 'User';?></th>
                                        <th><?php echo 'Book';?></th>
                                        <th><?php echo 'Chapter';?></th>
                                        <th><?php echo 'Content';?></th>
                                        <th><?php echo 'Status';?></th>
                                        <th><?php echo 'Register date';?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $report) { ?>
                                        <tr id="item-<?php echo $report->id; ?>">
                                            <td><?php echo $report->id; ?></td>
                                            <td><?php echo $report->user->name; ?></td>
                                            <td><a href="/admin/book/detail/<?php echo $report->book->id ?>" target="_blank"><?php echo $report->book->name; ?></a></td>
                                            <td><?php if(!empty($report->chapter)) { ?><a href="/admin/book/chapter/<?php echo $report->chapter->id ?>" target="_blank"><?php echo $report->chapter->name; ?></a><?php } ?></td>
                                            <td><?php echo nl2br($report->content); ?></td>
                                            <td><?php echo $report->status == \app\models\Report::STATUS_FIXED ? 'Fixed' : 'New'; ?></td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($report->created_at)); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <?php echo \Yii::$app->view->render('/layouts/partials/pagging', array(
                            'url' => '/admin/report',
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