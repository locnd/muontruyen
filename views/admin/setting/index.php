
<ol class="breadcrumb dl-breadcrumb">
    <li><a href="/admin"><i class="fa fa-home"></i> <?php echo 'Dashboard';?></a></li>
    <li class="active"><?php echo 'List';?> <?php echo 'Setting';?></li>
</ol>
<input id="crsf_token" type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-inverse">
            <div class="panel-body">
                <div>
                    <div class="row">
                        <h3 style="padding-left: 10px;">Settings</h3>
                        <div class="col-sm-12">
                            <table class="table table-striped table-bordered data-table admin-table">
                                <thead>
                                    <tr>
                                        <th><?php echo 'ID';?></th>
                                        <th><?php echo 'Name';?></th>
                                        <th><?php echo 'Value';?></th>
                                        <th><?php echo 'Updated date';?></th>
                                        <th><?php echo 'Created date';?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($settings as $setting) { ?>
                                        <tr id="item-<?php echo $setting->id; ?>">
                                            <td><?php echo $setting->id; ?></td>
                                            <td class="item-name"><?php echo $setting->name; ?></td>
                                            <td>
                                                <span id="cur_<?php echo $setting->id; ?>"><?php echo $setting->value; ?></span>
                                                <input type="text" class="form-control" id="new_<?php echo $setting->id; ?>" value="<?php echo $setting->value;?>" style="display: none">
                                                <button onclick="save(<?php echo $setting->id; ?>)" id="btn_<?php echo $setting->id; ?>" style="margin-top: 10px;display:none" class="btn btn-primary">Save</button>
                                            </td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($setting->updated_at)); ?></td>
                                            <td><?php echo date('d-m-Y H:i:s',strtotime($setting->created_at)); ?></td>
                                            <td>
                                                <a onclick="open_edit(<?php echo $setting->id; ?>)" style="padding: 3px 7px;" href="javascript:;" title="<?php echo 'Edit'; ?>" class="btn btn-primary"><i class="glyphicon glyphicon-pencil"></i></a>
                                            </td>
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
<script>
    function open_edit(id) {
        $('#cur_'+id).toggle();
        $('#new_'+id).toggle();
        $('#btn_'+id).toggle();
    }
    function save(id) {
        var params = {};
        params['setting_id'] = id;
        params['value'] = $('#new_'+id).val();
        params['_csrf'] = $('#crsf_token').val();
        var url = '/ajax/editsetting';
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