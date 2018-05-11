<?php
$total_page = ceil($total / $limit);
$from = ($page - 1) * $limit + 1;
$to = min($total, $page * $limit);
if(!isset($filters)) {
    $filters = array();
}
if(!isset($sort)) {
    $sort = array();
} ?>
<div class="col-sm-12">
    <div class="dataTables_info">
        <?php echo 'Hiiển thị từ '.show_number(max($from, 0)).' đến '.show_number($to).' trong tổng số '.show_number($total).' kết quả'; ?>
    </div>
</div>
<?php if ($total > $limit) { ?>
    <div class="col-sm-12 txt-alg-center">
        <div class="dataTables_paginate paging_simple_numbers">
            <ul class="pagination">
                <?php if ($page == 1) { ?>
                <li class="paginate_button previous disabled">
                <?php } else { ?>
                <li class="paginate_button previous">
                <?php } ?>
                    <a href="<?php echo make_page_url($url, $filters, $sort, max(1, $page - 1)); ?>"><?php echo 'Previous'; ?></a>
                </li>
                <?php for ($i = 1; $i <= $total_page; $i++) {
                if($i<$page-4 || $i>$page+4) { continue; }
                if ($page == $i) { ?>
                <li class="paginate_button active">
                <?php } else { ?>
                <li class="paginate_button">
                <?php } ?>
                    <a href="<?php echo make_page_url($url, $filters, $sort, $i); ?>"><?php echo show_number($i); ?></a>
                </li>
                <?php }
                if ($page == $total_page) { ?>
                <li class="paginate_button next disabled">
                <?php } else { ?>
                <li class="paginate_button next">
                <?php } ?>
                    <a href="<?php echo make_page_url($url, $filters, $sort, min($total_page, $page + 1)); ?>"><?php echo 'Next'; ?></a>
                </li>
            </ul>
        </div>
    </div>
<?php }
