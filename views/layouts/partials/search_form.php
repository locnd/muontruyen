<input id="open-filter-btn" class="btn btn-success btn-lg p-5-15" type="button" value="<?php echo 'Search';?>" style="margin: 10px;"
    onclick="$(this).remove();$('.dl-filter-form').show();">

<form class="m-t-20 dl-filter-form" style="display:none">
<?php
if (!isset($filters)) {
    $filters = array();
}
foreach ($filters as $key => $value) { ?>
    <div class="form-group col-md-6 m-b-10">
        <label class="col-md-4 control-label label-search-1"><?php echo $key; ?></label>
        <div class="col-md-8">
            <?php if ($key == 'from_date' || $key == 'to_date') { 
                $option_from_to_date = array(
                    'name' => $key,
                    'type' => 'text',
                    'class' => 'form-control w-100-per input-datepicker',
                    'value' => $value,
                    'placeholder' => $key
                );
                echo_input($option_from_to_date); ?>
                <span class="input-group-addon dl-input-calendar"><i class="fa fa-calendar"></i></span>
            <?php } elseif ($key == 'is_admin') {
                $option_user_type = array(
                    'name' => $key,
                    'type' => 'select',
                    'class' => 'form-control'
                );
                $items = array(
                    ''=>'Please select',
                    '0'=>'User',
                    '1'=>'Admin'
                );
                echo_input($option_user_type, $items, $value==='' ? '' : $value);
            } elseif ($key == 'status') {
                $option_status = array(
                    'name' => $key,
                    'type' => 'select',
                    'class' => 'form-control'
                );
                $status_arr = array(
                    ''=>'Please select',
                    '0'=>'Inactive',
                    '1'=>'Active'
                );
                echo_input($option_status, $status_arr, $value==='' ? '' : $value);
            } elseif ($key == 'book_id') {
                $option_book = array(
                    'name' => $key,
                    'type' => 'select',
                    'class' => 'form-control dl_combobox'
                );
                $tmp_books = app\models\Book::find()->all();
                $books_arr = array(
                    ''=>'Please select'
                );
                foreach ($tmp_books as $tmp_book) {
                    $books_arr[$tmp_book->id] = $tmp_book->name;
                }
                echo_input($option_book, $books_arr, $value==='' ? '' : $value);
            } elseif ($key == 'user_id') {
                $option_user = array(
                    'name' => $key,
                    'type' => 'select',
                    'class' => 'form-control dl_combobox'
                );
                $tmp_users = app\models\User::find()->all();
                $users_arr = array(
                    ''=>'Please select'
                );
                foreach ($tmp_users as $tmp_user) {
                    $users_arr[$tmp_user->id] = $tmp_user->name;
                }
                echo_input($option_user, $users_arr, $value==='' ? '' : $value);
            } else {
                $option_else = array(
                    'name' => $key,
                    'type' => 'text',
                    'class' => 'form-control w-100-per',
                    'value' => $value,
                    'placeholder' => $key
                );
                echo_input($option_else);
            } ?>
        </div>
    </div>
<?php } ?>
<div class="clear0"></div>
<div class="form-group col-md-6 m-b-20 m-t-10">
    <label class="col-md-4 control-label label-search-1"></label>
    <div class="col-md-8">
        <input class="btn btn-success btn-lg p-5-15" type="submit" value="<?php echo 'Search';?>">
    </div>
</div>
</form>
<script>
    $(document).ready(function () {
        <?php
        $tmp_filter = $filters;
        foreach ($tmp_filter as $k => $v) {
            if ($v === '' || ($k == 'to_date' && $v === date('d-m-Y'))) {
                unset($tmp_filter[$k]);
            }
        }
        if(!empty($tmp_filter)) { ?>
        $('#open-filter-btn').click();
        <?php } ?>
    });
</script>