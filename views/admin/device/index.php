
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard';?></a></li>
    <li class="active"><?php echo 'List';?> <?php echo 'Devices';?></li>
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
                            <table class="table table-striped table-bordered data-table admin-table">
                                <thead>
                                    <tr>
                                        <th><?php echo 'ID';?></th>
                                        <th><?php echo 'User';?></th>
                                        <th><?php echo 'App Version';?></th>
                                        <th><?php echo 'Device Type';?></th>
                                        <th><?php echo 'Device ID';?></th>
                                        <th><?php echo 'Created date';?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devices as $device) { ?>
                                        <tr id="item-<?php echo $device->id; ?>">
                                            <td><?php echo $device->id; ?></td>
                                            <td class="item-name">
                                                <?php if(!empty($device->user)) { ?>
                                                <a href="/admin/user/detail/<?php echo $device->user->id; ?>"><?php echo $device->user->name; ?></a>
                                                <?php } ?>
                                            </td>
                                            <td><?php echo $device->app_version; ?></td>
                                            <td><?php echo $device->type; ?></td>
                                            <td><?php echo $device->device_id; ?></td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($device->created_at)); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <?php echo \Yii::$app->view->render('/layouts/partials/pagging', array(
                            'url' => '/admin/device',
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