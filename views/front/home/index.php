<div class="post-detail section-container">
    <?php foreach ($books as $book) { ?>
    <div class="section-container a-book">
        <div class="a-title"><a href="/book/<?php echo $book['slug']; ?>"><?php echo $book['title']; ?></a></div>
        <div class="a-cover">
            <a href="/book/<?php echo $book['slug']; ?>"><img width="100%" src="<?php echo $book['image']; ?>" alt="" /></a>
        </div>
        <div class="a-description">
            <span><?php echo $book['description']; ?></span>
        </div>
        <div class="clear5"></div>
        <div class="a-date">Cập nhật: <?php echo $book['release_date']; ?></div>
    </div>
    <?php } ?>
    <div class="clear5"></div>
    <!--div class="section-container" ng-if="dl.arr_pages.length > 1">
        <ul class="a-pagging">
            <li ng-if="dl.page > 1"><a href="/#!/">Trang đầu</a></li>
            <li ng-class="{'active': page==dl.page}" ng-repeat="page in dl.arr_pages">
                <a href="/#!/home/{{ page }}">{{ page }}</a>
            </li>
            <li ng-if="dl.page < dl.count_pages"><a href="/#!/home/{{ dl.count_pages }}">Trang cuối</a></li>
        </ul>
    </div-->
</div>