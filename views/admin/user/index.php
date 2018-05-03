
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard';?></a></li>
    <li class="active"><?php echo 'List';?> <?php echo 'Users';?></li>
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
                                        <th><?php echo 'Name';?></th>
                                        <th><?php echo 'Username';?></th>
                                        <th><?php echo 'Avatar';?></th>
                                        <th><?php echo 'Permission';?></th>
                                        <th><?php echo 'Status';?></th>
                                        <th><?php echo 'Register date';?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user) { ?>
                                        <tr id="item-<?php echo $user->id; ?>">
                                            <td><?php echo $user->id; ?></td>
                                            <td class="item-name"><?php echo $user->name; ?></td>
                                            <td><?php echo $user->username; ?></td>
                                            <td><img class="show_profile_image mini-image" src="<?php echo '/uploads/users/default.png'; ?>"></td>
                                            <td class="item-type"><?php echo $user->is_admin == 1 ? 'Admin' : 'User'; ?></td>
                                            <td><?php echo $user->status == 1 ? 'Active' : 'Inactive'; ?></td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($user->created_at)); ?></td>
                                            <td>
                                                <a href="/admin/user/detail/<?php echo $user->id; ?>" class="btn btn-success btn-icon btn-circle btn-lg m-r-5"><i class="fa fa-search"></i></a>
                                                <a href="/admin/user/edit/<?php echo $user->id; ?>" class="btn btn-warning btn-icon btn-circle btn-lg m-r-5"><i class="fa fa-pencil"></i></a>
                                                <?php if(empty($user->deleted_at)) { ?>
                                                <a onclick="delete_item('user', <?php echo $user->id; ?>)" class="btn btn-danger btn-icon btn-circle btn-lg"><i class="fa fa-trash"></i></a>
                                                <?php } else { ?>
                                                <a onclick="restore_item('user', <?php echo $user->id; ?>)" class="btn btn-danger btn-icon btn-circle btn-lg"><i class="fa fa-refresh"></i></a>
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
                            'url' => '/admin/user',
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