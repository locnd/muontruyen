
var interval = null;

function show_home(page, sort) {
    $('#list-books').html('');
    $('#paging').html('');
    if(!is_logined()) {
        $('#just_readed').hide();
    }
    if(page == 1 && !is_admin()) {
        var time_cache = localStorage.getItem("time_cache_home_"+sort);
        if (time_cache !== null && time_cache !== '' && $.now() < parseInt(time_cache) + 600000) {
            check_unread();
            get_cache_home(sort);
            return true;
        }
    }

    var params = {
        page: page,
        sort: sort,
        device_id: localStorage.getItem("device_id"),
        device_type: localStorage.getItem("device_type"),
        app_version: APP_VERSION
    };
    send_api('GET', '/home', params, function(res) {
        $('#select_sort').val(sort);
        $('#select_sort').show();
        if (res.success) {
            if(page == 1 && !is_admin()) {
                localStorage.setItem("time_cache_home_"+sort, $.now());
                localStorage.setItem("count_pages", res.count_pages);
                interval = setInterval(function() {
                    if(typeof(db2) != 'undefined') {
                        clearInterval(interval);
                        save_cache_home_books(res.data, sort);
                    }
                },1);
            }
            show_home_content(res, page, sort);
            cache_images();
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function home_again() {
    var sort = parseInt($('#select_sort').val());
    window.location.href="index.html?sort="+sort;
}

function get_cache_home(sort) {
    $('#paging').hide();
    var count_pages = localStorage.getItem("count_pages");
    if(count_pages === null || count_pages === '') {
        count_pages = 1;
    }
    count_pages = parseInt(count_pages);
    if(count_pages > 1) {
        var page = 1;
        var pages = [];
        for (var i=1;i<=count_pages;i++) {
            if (count_pages > 3) {
                if((page == 1 && i==3) || (page == count_pages && i==count_pages-2)) {
                    pages.push(i);
                    continue;
                }
                if (i < page - 1 || i > page + 1) {
                    continue;
                }
            }
            pages.push(i);
        }
        display_paging('index.html?sort='+sort+'&page=',pages, page, count_pages);
    }
    interval = setInterval(function() {
        if(typeof(db2) != 'undefined') {
            clearInterval(interval);
            get_all_home_books(sort, function(data){
                $('#select_sort').val(sort);
                $('#select_sort').show();
                display_a_book(data);
                cache_images();
                $('#paging').show();
            });
        }
    },1);
}
function show_home_content(res,page,sort) {
    for(var i=0;i<res.data.length;i++) {
        display_a_book(res.data[i]);
    }
    if(res.count_pages > 1) {
        var pages = [];
        for (var i=1;i<=res.count_pages;i++) {
            if (res.count_pages > 3) {
                if((page == 1 && i==3) || (page == res.count_pages && i==res.count_pages-2)) {
                    pages.push(i);
                    continue;
                }
                if (i < page - 1 || i > page + 1) {
                    continue;
                }
            }
            pages.push(i);
        }
        display_paging('index.html?sort='+sort+'&page=',pages, page, res.count_pages);
    }
}
function display_paging(url,pages, current_page, total_page) {
    var html = '<ul class="a-pagging">';
    if(current_page > 1) {
        html += '<li><a href="'+url+'1">Đầu</a></li>';
    }
    for(var i=0;i<pages.length;i++) {
        if(pages[i] == current_page) {
            html += '<li class="active">';
        } else {
            html += '<li>';
        }
        html += '<a href="'+url+''+pages[i]+'">'+pages[i]+'</a>';
        html += '</li>';
    }
    if(current_page < total_page) {
        html += '<li><a href="'+url+''+total_page+'">Cuối</a></li>';
    }
    html += '</ul>';
    $('#paging').html(html);
}

function display_a_book(book) {
    var html = '<div class="section-container a-book">';
    html += '<div class="a-cover-new">';
    html += '<a href="book.html?id='+book.id+'"><img style="margin-bottom:5px" width="100%" src="'+book.image+'" alt="" /></a>';
    html += '</div>';
    html += '<div class="a-description-new">';
    html += '<table>';
    html += '<tr>';
    html += '<td colspan="2" style="width:100%">';
    html += '<div class="a-title" style="padding:0"><a href="book.html?id='+book.id+'">'+book.name+'</a></div>';
    html += '</td>';
    html += '</tr>';
    if(book.last_chapter_id > 0) {
        html += '<tr>';
        html += '<td class="label-td">Chương mới</td>';
        if(typeof(book.last_chapter_read) != 'undefined' && book.last_chapter_read) {
            html += '<td class="value-td"><a style="color:darkgoldenrod"href="chapter.html?id=' + book.last_chapter_id + '">' + book.last_chapter_name + '</a></td>';
        } else {
            html += '<td class="value-td"><a href="chapter.html?id=' + book.last_chapter_id + '">' + book.last_chapter_name + '</a></td>';
        }
        html += '</tr>';
    }
    if(typeof(book.tags) != 'undefined' && book.tags.length > 0) {
        html += '<tr>';
        html += '<td class="label-td">Thể loại</td>';
        html += '<td class="value-td">';
        for(var i=0;i<book.tags.length;i++) {
            html += '<a href="tag.html?id='+book.tags[i].id+'">'+book.tags[i].name+'</a> - ';
        }
        html = html.slice(0,-3);
        html += '</td>';
        html += '</tr>';
    }
    if(typeof(book.authors) != 'undefined' && book.authors.length > 0) {
        html += '<tr>';
        html += '<td class="label-td">Tác giả</td>';
        html += '<td class="value-td">';
        for(var i=0;i<book.authors.length;i++) {
            html += '<a href="tag.html?id='+book.authors[i].id+'">'+book.authors[i].name+'</a> - ';
        }
        html = html.slice(0,-3);
        html += '</td>';
        html += '</tr>';
    }
    html += '<tr>';
    html += '<td class="label-td">Cập nhật</td>';

    html += '<td class="value-td">'+show_date(book.release_date)+'</td>';
    html += '</tr>';
    html += '</table>';
    html += '</div>';
    html += '<div class="clear5"></div>';

    if(typeof(book.chapters) == 'object' && book.chapters.length > 0) {
        html += '<div class="clear10" style="border-top: 1px solid lightblue;"></div>';
        for(var i=0;i<book.chapters.length;i++) {
            var chapter = book.chapters[i];
            html += '<div class="a-chapter">';
            html += '<div style="width: calc(100% - 150px);float:left">';
            if(chapter.read) {
                html += '<a style="color:darkgoldenrod" href="chapter.html?id=' + chapter.id + '">' +chapter.name + '</a>';
            } else {
                html += '<a href="chapter.html?id='+ chapter.id + '">' +chapter.name + '</a>';
            }
            html += '</div>';
            html += '<div style="width: 135px;float:right">';
            html += chapter.release_date;
            html += '</div>';
            html += '<div class="clear5"></div>';
            html += '</div>';
        }
        html += '<div class="clear5"></div>';
    }
    html += '</div>';
    $('#list-books').append(html);
}

function display_a_tag(tag) {
    var html='<div class="a-tag">';
    html += '<a href="tag.html?id='+tag.id+'">';
    var tag_name = tag.name;
    html += '<img src="assets/img/tag.png">'+tag_name + ' ('+tag.count+')';
    html += '</a>';
    html += '</div>';
    $('#list-books .a-book').append(html);
}

function show_book(id) {
    var params = {
        id: id
    };
    send_api('GET', '/book', params, function(res) {
        if (res.success) {
            $('#book_id').val(res.data.id);
            if(is_admin()) {
                for(var i=0;i<res.tags.length;i++) {
                    if(res.tags[i].type == 0) {
                        show_select_tag(res.tags[i], res.tags[i].is_checked);
                    }
                }
            }
            display_book_info(res.data, res.tags);
            display_list_chapters(res.chapters);
            display_groups(res.data.id, res.groups);
            cache_images();
        } else {
            dl_alert('danger', res.message, true);
            window.location.href = "index.html";
        }
    });
}

function open_reports() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    show_modal('report-modal');
}

function send_report() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    var content = $.trim($('#report_content').val());
    if(content == '') {
        dl_alert('danger', 'Hãy nhập nội dung lỗi', false);
        return false;
    }
    var book_id = $('#book_id').val();
    var chapter_id = 0;
    if($('#chapter_id').length > 0) {
        chapter_id = $('#chapter_id').val();
    }
    var params = {
        book_id: book_id,
        chapter_id: chapter_id,
        content: content
    };
    send_api('POST', '/report', params, function (res) {
        $('#loading-btn').hide();
        if (res.success) {
            close_modal('report-modal');
            dl_alert('success', 'Đã báo lỗi thành công', false);
            $('#report_content').val('');
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function display_book_info(book, tags) {
    var html = '';
    html += '<div class="book-title">'+book.name+'</div>';
    html += '<div class="clear10"></div>';
    html += '<div class="book-cover">';
    html += '<img src="'+book.image+'">';
    html += '</div>';
    html += '<div class="clear10"></div>';
    if($.trim(book.description) == '') {
        book.description = 'Chưa có thông tin';
    }
    html += '<div class="book-description">'+book.description+'</div>';
    html += '<div class="clear10"></div>';
    if(book.is_following) {
        html += '<div id="follow-btn" onclick="follow()" class="btn-unbookmark">';
    } else {
        html += '<div id="follow-btn" onclick="follow()" class="btn-bookmark">';
    }
    html += '</div>';
    html += '<div class="clear10"></div>';

    var author_html = '';
    var tag_html = '';
    for (var i = 0; i < book.tags.length; i++) {
        tag_html += '<a href="tag.html?id=' + book.tags[i].id + '">' + book.tags[i].name + '</a> - ';
    }
    for (var i = 0; i < book.authors.length; i++) {
        author_html += '<a href="tag.html?id=' + book.authors[i].id + '">' + book.authors[i].name + '</a> - ';
    }
    if(tag_html == '') {
        html += '<div style="margin-bottom:10px">* Truyện chưa phân loại</div>';
    } else {
        html += '<div style="float:left;margin-right:10px">Thể loại:</div>';
        html += tag_html.slice(0, -3);
    }
    if(author_html != '') {
        html += '<div class="clear5"></div>';
        html += '<div style="float:left;margin-right:10px">Tác giả:</div>';
        html += author_html.slice(0, -3);
    }
    html += '<div class="clear5" style="height:1px"></div>';
    html += '<div style="text-align:right"><i class="fa fa-eye"></i> '+book.count_views+'</div>';
    html += '<div class="clear5"></div>';
    html += '<div style="float:left;margin:0" id="save-btn" onclick="save_to_offline()" class="btn-save-to-offline"><i class="fa fa-download"></i>&nbsp; Lưu đọc Offline</div>';
    html += '<div id="report">';
    html += '<a onclick="open_reports()" class="report-btn fl-r"><i class="fa fa-exclamation-circle"></i> Báo lỗi</a>';
    html += '</div>';
    html += '<div class="clear5" style="height:1px"></div>';

    $('#book-info').html(html);
    $('#book-info').show();
}

function display_list_chapters(chapters) {
    var html = '';
    if(chapters.length == 0) {
        html += '<div class="book-chapter-list">Không có chương nào</div>';
    } else {
        html += '<div class="book-chapter-list">Danh sách chương ('+chapters.length+' chương)<a href="javascript:;" onclick="move_to_bottom()" class="right-btn">Đến chương đầu</a></div>';
        html += '<div class="clear10"></div>';
        for(var i=0;i<chapters.length;i++) {
            var chapter = chapters[i];
            var moving_html = '';
            if(is_admin()) {
                moving_html += '<span class="admin-btn mov-btn" onclick="move_chapter('+chapter.id+',0)"><i class="fa fa-angle-double-up"></i></span>';
                moving_html += '<span class="admin-btn mov-btn" onclick="move_chapter('+chapter.id+',1)"><i class="fa fa-angle-double-down"></i></span>';
                moving_html += '<span class="admin-btn mov-btn" onclick="delete_chapter('+chapter.id+')"><i class="fa fa-trash"></i></span>';
            }
            html += '<div class="a-chapter">';
            html += '<div style="width: calc(100% - 150px);float:left">';
            if(chapter.read) {
                html += moving_html + ' <a style="color:darkgoldenrod" href="chapter.html?id=' + chapter.id + '">' +chapter.name + '</a>';
            } else {
                html += moving_html + ' <a href="chapter.html?id='+ chapter.id + '">' +chapter.name + '</a>';
            }
            html += '</div>';
            html += '<div style="width: 135px;float:right">';
            html += chapter.release_date;
            html += '</div>';
            html += '<div class="clear10"></div>';
            html += '</div>';
        }
    }
    $('#chapters-list').html(html);
    $('#chapters-list').show();
}

function move_chapter(chapter_id, is_down) {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    $('#loading-btn').show();
    var params = {
        chapter_id: chapter_id,
        is_down: is_down
    };
    send_api('POST', '/move-chapter', params, function (res) {
        $('#loading-btn').hide();
        if (res.success) {
            display_list_chapters(res.data);
            $('.mov-btn').show();
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function delete_image(image_id) {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    $('#loading-btn').show();
    var params = {
        image_id: image_id
    };
    send_api('POST', '/delete-image', params, function (res) {
        $('#loading-btn').hide();
        if (res.success) {
            var html = '';
            for(var i=0;i<res.data.length;i++) {
                var moving_html = '';
                if(is_admin()) {
                    moving_html += '<span class="admin-btn mov-btn" onclick="move_image('+res.data[i].id+',0)"><i class="fa fa-angle-double-up"></i></span>';
                    moving_html += '<span class="admin-btn mov-btn" onclick="move_image('+res.data[i].id+',1)"><i class="fa fa-angle-double-down"></i></span>';
                    moving_html += '<span class="admin-btn mov-btn" onclick="delete_image('+res.data[i].id+')"><i class="fa fa-trash"></i></span>';
                }
                html+=moving_html + '<img style="width: 100%;margin:5px 0;" src="'+res.data[i].image+'">';
            }
            $('#images_list').html(html);
            $('.mov-btn').show();
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function move_image(image_id, is_down) {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    $('#loading-btn').show();
    var params = {
        image_id: image_id,
        is_down: is_down
    };
    send_api('POST', '/move-image', params, function (res) {
        $('#loading-btn').hide();
        if (res.success) {
            var html = '';
            for(var i=0;i<res.data.length;i++) {
                var moving_html = '';
                if(is_admin()) {
                    moving_html += '<span class="admin-btn mov-btn" onclick="move_image('+res.data[i].id+',0)"><i class="fa fa-angle-double-up"></i></span>';
                    moving_html += '<span class="admin-btn mov-btn" onclick="move_image('+res.data[i].id+',1)"><i class="fa fa-angle-double-down"></i></span>';
                    moving_html += '<span class="admin-btn mov-btn" onclick="delete_image('+res.data[i].id+')"><i class="fa fa-trash"></i></span>';
                }
                html+=moving_html + '<img style="width: 100%;margin:5px 0;" src="'+res.data[i].image+'">';
            }
            $('#images_list').html(html);
            $('.mov-btn').show();
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function follow() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    var book_id = $('#book_id').val();
    var is_following = false;
    if($('#follow-btn').length > 0) {
        if($('#follow-btn').hasClass('btn-unbookmark')) {
            is_following = true;
        }
    }
    if($('#top_follow')) {
        if($('#top_follow a').hasClass('btn-unfollow')) {
            is_following = true;
        }
    }
    if(!is_following) {
        $('.dl-overlay').fadeIn( 1, function() {
            $('#list_group').fadeIn(500);
        });
        return true;
    }
    if(confirm('Bạn muốn bỏ theo dõi truyện này ?')) {
        send_follow(book_id, true);
    }
}

function create_tag() {
    var tag_name = $('#tag_name').val();
    if(tag_name == '') {
        dl_alert('danger', 'Điền tên thể loại', false);
        return true;
    }
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    $('#loading-btn').show();
    var params = {
        tag_name: tag_name
    };
    send_api('POST', '/create-tag', params, function (res) {
        $('#loading-btn').hide();
        if (res.success) {
            show_select_tag(res.data, false);
            $('#tag_name').val('');
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function show_select_tag(tag, checked) {
    var html = '<li>';
    if(checked) {
        html += '<label for="tag-' + tag.id + '"><input checked id="tag-' + tag.id + '" type="checkbox" value="' + tag.id + '" name="tags[]"> ' + tag.name + '</label>';
    } else {
        html += '<label for="tag-' + tag.id + '"><input id="tag-' + tag.id + '" type="checkbox" value="' + tag.id + '" name="tags[]"> ' + tag.name + '</label>';
    }
    html += '</li>';
    $('ul#tags-list').append(html);
    if($('ul#tags-list li').length > 10) {
        $('ul#tags-list').attr('style', 'height: 163px;overflow-y: scroll;');
    }
}

function send_follow(book_id, is_following) {
    if(!is_logined()) {
        window.location.href = "login.html";
    }
    var group_id = parseInt($('input[name=group_id]:checked').val());
    var group_name = $.trim($('#input_group_name').val());
    if(!is_following) {
        if(group_id == 0 && group_name == '') {
            dl_alert('danger', 'Hãy điền tên nhóm', false);
            return true;
        }
        if(group_id == 0) {
            var groups = $('.dl-group-name');
            for (var i = 0; i < groups.length - 1; i++) {
                var tmp_name = $.trim($(groups[i]).html()).split(' (');
                if (group_name == tmp_name[0]) {
                    dl_alert('danger', 'Tên nhóm đã tồn tại', false);
                    return true;
                }
            }
        }
    }
    var params = {
        book_id: book_id,
        group_id: group_id,
        group_name: group_name
    };
    $('.btn-send-follow').hide();
    $('.btn-loading-send-follow').show();
    send_api('POST', '/make-follow', params, function(res) {
        $('.btn-send-follow').show();
        $('.btn-loading-send-follow').hide();
        if (res.success) {
            if(res.data) {
                dl_alert('success', 'Đã theo dõi', false);
                $('#list_group').fadeOut( 500, function() {
                    $('.dl-overlay').fadeOut(1);
                    display_groups(book_id, res.groups);
                });
                $('#follow-btn').removeClass('btn-bookmark').addClass('btn-unbookmark');
                $('#top_follow a').removeClass('btn-follow').addClass('btn-unfollow');
            } else {
                display_groups(book_id, res.groups);
                dl_alert('success', 'Đã bỏ theo dõi', false);
                $('#follow-btn').removeClass('btn-unbookmark').addClass('btn-bookmark');
                $('#top_follow a').removeClass('btn-unfollow').addClass('btn-follow');
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function display_groups(book_id, groups) {
    var html = '<h3>Danh sách nhóm</h3>';
    for(var i=0;i<groups.length;i++) {
        var group = groups[i];
        html += '<div>';
        html += '<label for="group'+group.id+'">';
        if(groups.length >= 5 && i==0) {
            html += '<input checked id="group' + group.id + '" type="radio" name="group_id" value="' + group.id + '"> &nbsp;';
        } else {
            html += '<input id="group' + group.id + '" type="radio" name="group_id" value="' + group.id + '"> &nbsp;';
        }
        html += '<span class="dl-group-name">'+group.name+'</span>';
        html += '</label>';
        html += '</div>';
    }
    if(groups.length < 5) {
        html += '<div>';
        html += '<label for="group0">';
        html += '<input checked id="group0" type="radio" name="group_id" value="0"> &nbsp;';
        html += '<span><input style="padding: 0 10px; max-width:100%" onfocus="$(\'#group0\').prop(\'checked\',true);" id="input_group_name" type="text"></span>';
        html += '</label>';
        html += '</div>';
    }
    html += '<div class="btn-send-follow" onclick="send_follow('+book_id+',false)">Theo dõi</div>';
    html += '<div class="btn-loading-send-follow" style="display:none"><img style="width:20px" src="data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA==" /></div>';
    if(groups.length < 5) {
        html += '<div>';
        html += '<label>Chỉ có thể tạo tối đa 5 nhóm</label>';
        html += '</div>';
    }
    $('#list_group').html(html);
}

function show_chapter(id) {
    var params = {
        id: id
    };
    send_api('GET', '/chapter', params, function(res) {
        if (res.success) {
            $('#book_id').val(res.book.id);
            $('#chapter_id').val(res.data.id);
            if(is_admin()) {
                $('#input-textarea').val(res.data.name);
            }
            var chapter = res.data;
            if(typeof(chapter.url) != 'undefined') {
                $('#source-btn').attr('onclick','view_source(\''+chapter.url+'\')');
            }
            var book = res.book;
            var images = res.images;
            var chapters = res.chapters;
            display_groups(res.book.id, res.groups);
            if(book.is_following) {
                $('#top_follow a').addClass('btn-unfollow');
            } else {
                $('#top_follow a').addClass('btn-follow');
            }
            for(var i=0;i<chapters.length;i++) {
                if(i==0 && chapter.id == chapters[i].id) {
                    chapter.point = 'last';
                    $('#top_btn_next').hide();
                }
                if(i==chapters.length-1 && chapter.id == chapters[i].id) {
                    chapter.point = 'first';
                    $('#top_btn_prev').hide();
                }
                if(chapter.id == chapters[i].id) {
                    if(i > 0) {
                        chapter.next = chapters[i-1].id;
                    }
                    if(i < chapters.length-1) {
                        chapter.prev = chapters[i+1].id;
                    }
                }
            }
            if(chapters.length == 1) {
                chapter.point = 'only';
                $('#top_btn_prev').hide();
                $('#top_btn_next').hide();
            }

            var html = '<div class="clear10"></div>';
            html+='<a class="book-title" href="book.html?id='+book.id+'">'+book.name+'</a>';
            html+='<div class="clear10"></div>';
            html+='<div id="report">';
            if(res.is_bookmark) {
                html+='<a onclick="bookmark()" id="bookmark-btn" class="btn-unbookmark-chap"><i class="fa fa-star"></i> Bỏ đánh dấu</a>';
            } else {
                html+='<a onclick="bookmark()" id="bookmark-btn" class="btn-bookmark-chap"><i class="fa fa-star"></i> Đánh dấu</a>';
            }
            html+='<a onclick="open_reports()" class="report-btn fl-r"><i class="fa fa-exclamation-circle"></i> Báo lỗi</a>';
            html+='</div>';
            html+='<div class="clear10" style="height:15px"></div>';
            var paging_html = '';
            paging_html+='<select class="select-chap" onchange="change_chapter(this)">';
            for(var i=0;i<chapters.length;i++) {
                var a_chapter = chapters[i];
                if(a_chapter.id == chapter.id) {
                    paging_html+='<option selected value="'+a_chapter.id+'">'+a_chapter.name+'</option>';
                } else {
                    paging_html+='<option value="'+a_chapter.id+'">'+a_chapter.name+'</option>';
                }
            }
            paging_html+='</select>';
            $('#top_select_chapter').html(paging_html);
            paging_html+='<div class="clear5"></div>';
            paging_html+='<div class="clear10"></div>';
            if(chapter.point != 'first' && chapter.point != 'only') {
                paging_html+='<a class="btn-prev" href="chapter.html?id='+chapter.prev+'"><i class="fa fa-angle-double-left"></i> Trước</a>';
            }
            if(chapter.point != 'last' && chapter.point != 'only') {
                paging_html+='<a class="btn-next" href="chapter.html?id='+chapter.next+'">Sau <i class="fa fa-angle-double-right"></i></a>';
            }
            html+=paging_html;
            html+='<div class="clear10"></div>';
            html+='<div id="images_list">';
            for(var i=0;i<images.length;i++) {
                var moving_html = '';
                if(is_admin()) {
                    moving_html += '<span class="admin-btn mov-btn" onclick="move_image('+images[i].id+',0)"><i class="fa fa-angle-double-up"></i></span>';
                    moving_html += '<span class="admin-btn mov-btn" onclick="move_image('+images[i].id+',1)"><i class="fa fa-angle-double-down"></i></span>';
                    moving_html += '<span class="admin-btn mov-btn" onclick="delete_image('+images[i].id+')"><i class="fa fa-trash"></i></span>';
                }
                html+=moving_html + '<img style="width: 100%;margin:5px 0;" src="'+images[i].image+'">';
            }
            html+='</div>';
            html+='<div class="clear10"></div>';
            html+=paging_html;
            html+='<div class="clear10"></div>';
            html+='<div class="clear10"></div>';
            html+='<a class="btn-back" href="book.html?id='+book.id+'">Quay về</a>';
            html+='<div class="clear10"></div>';
            $('#chapter-page').html(html);
            $('#chapter-page').show();
            $('#send-report-btn').attr('onclick','send_report()');
            check_header();
            cache_images();
        } else {
            dl_alert('danger', res.message, true);
            window.location.href = "book.html?id="+id;
        }
    });
}

function change_chapter(ele) {
    var chapter_id = $(ele).val();
    window.location.href = 'chapter.html?id='+chapter_id;
}

function show_follow(tab, page, is_first) {
    var params = {
        tab: tab,
        page: page
    };
    send_api('GET', '/follow', params, function(res) {
        if (res.success) {
            var books = res.data;
            var groups = res.groups;
            if(is_first) {
                var html = '';
                for (var i = 0; i < groups.length; i++) {
                    html += '<div id="group' + groups[i].id + '" onclick="show_follow(' + groups[i].id + ',1, false)" class="section-container a-book">';
                    html += '<div>' + groups[i].name + '</div>';
                    html += '</div>';
                }
                $('#list_groups').append(html);
            }
            var html = '';
            if(books.length == 0) {
                html += '<div class="section-container a-book">';
                html += 'Không có truyện theo dõi';
                html += '</div>';
            } else {
                for (var i = 0; i < books.length; i++) {
                    var book = books[i];
                    html += '<div class="section-container a-book">';
                    html += '<div class="a-cover-new">';
                    html += '<a href="book.html?id='+book.id+'"><img width="100%" src="'+book.image+'" alt="" /></a>';
                    html += '</div>';
                    html += '<div class="a-description-new">';
                    html += '<table>';
                    html += '<tr>';
                    html += '<td colspan="2">';
                    html += '<div class="a-title" style="padding:0"><a href="book.html?id='+book.id+'">'+book.name+'</a></div>';
                    html += '</td>';
                    html += '</tr>';
                    if(book.last_chapter_id > 0) {
                        html += '<tr>';
                        html += '<td class="label-td">Chương mới</td>';
                        if(typeof(book.last_chapter_read) != 'undefined' && book.last_chapter_read) {
                            html += '<td class="value-td"><a style="color:darkgoldenrod"href="chapter.html?id=' + book.last_chapter_id + '">' + book.last_chapter_name + '</a></td>';
                        } else {
                            html += '<td class="value-td"><a href="chapter.html?id=' + book.last_chapter_id + '">' + book.last_chapter_name + '</a></td>';
                        }
                        html += '</tr>';
                    }
                    html += '<tr>';
                    html += '<td class="label-td">Cập nhật</td>';
                    html += '<td class="value-td">'+show_date(book.release_date)+'</td>';
                    html += '</tr>';
                    html += '</table>';
                    html += '</div>';
                    html += '<div class="clear0"></div>';
                    html += '</div>';
                }
            }
            $('#list_follows').html(html);
            if(groups.length == 0) {
                $('#list_groups').hide();
                $('.dl-content').css('width','100%');
                return true;
            }
            if(tab == 0) {
                if (books.length > 0) {
                    $('#group0').show();
                } else {
                    $('#group0').hide();
                    if(page > 1) {
                        show_follow(0, 1, false);
                        return true;
                    }
                    if (groups.length > 0) {
                        show_follow(groups[0].id, 1, false);
                        return true;
                    }
                }
            }
            $('.a-book.active').removeClass('active');
            $('#group'+tab).addClass('active');
            $('html, body').animate({scrollTop: 0}, 1);
            display_follow_paging(tab, page, res.count_pages);
            cache_images();
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function forgotpassword() {
    $('.form-error').html('');
    $('#loading-btn').show();
    $('#login-btn').hide();
    $('.form-control').removeClass('input-error');
    var params = {
        email: $.trim($('#email').val()),
    };
    send_api('POST', '/forgotpassword', params, function(res) {
        $('#loading-btn').hide();
        $('#login-btn').show();
        if (res.success) {
            $('#login-btn').hide();
            $('#login-to-btn').attr('style','padding-left:0');
            $('#input-area').html('');
            $('#input-area').append('<p>Mật khẩu mới của bạn là <b>'+res.data.new_password+'</b>.</p>');
            $('#input-area').append('<p>Vui lòng đăng nhập bằng tên đăng nhập đã đăng ký và mật khẩu mới trong vòng 15\'.</p>');
            $('#input-area').append('<p>Cảm ơn !</p>');
        } else {
            $.each(res.data, function( index, value ) {
                if(typeof(value) != 'undefined' && value != '' && value != null && value != 'null') {
                    $('#'+index+'_error').html(value);
                    $('#'+index).addClass('input-error');
                }
            });
            dl_alert('danger', res.message, false);
        }
    });
}

function login() {
    $('.form-error').html('');
    $('#loading-btn').show();
    $('#login-btn').hide();
    $('.form-control').removeClass('input-error');
    var params = {
        username: $.trim($('#username').val()),
        password: $('#password').val(),
        device_id: localStorage.getItem("device_id", ''),
        device_type: localStorage.getItem("device_type", ''),
        app_version: APP_VERSION
    };
    send_api('POST', '/login', params, function(res) {
        $('#loading-btn').hide();
        $('#login-btn').show();
        if (res.success) {
            localStorage.setItem("token", res.data.token);
            if(parseInt(res.data.is_admin) == 1) {
                localStorage.setItem("is_admin", res.data.is_admin);
            }
            dl_alert('success', 'Đăng nhập thành công', true);
            window.location.href = 'index.html';
        } else {
            $.each(res.data, function( index, value ) {
                if(typeof(value) != 'undefined' && value != '' && value != null && value != 'null') {
                    $('#'+index+'_error').html(value);
                    $('#'+index).addClass('input-error');
                }
            });
            dl_alert('danger', res.message, false);
        }
    });
}
function register() {
    $('.form-error').html('');
    $('#loading-btn').show();
    $('#login-btn').hide();
    $('.form-control').removeClass('input-error');
    var params = {
        username: $.trim($('#username').val()),
        name: $.trim($('#name').val()),
        email: $.trim($('#email').val()),
        password: $('#password').val(),
        password2: $('#password2').val(),
        device_id: localStorage.getItem("device_id", ''),
        device_type: localStorage.getItem("device_type", ''),
        app_version: APP_VERSION
    };
    send_api('POST', '/register', params, function(res) {
        $('#loading-btn').hide();
        $('#login-btn').show();
        if (res.success) {
            localStorage.setItem("token", res.data.token);
            dl_alert('success', 'Đăng ký thành công', true);
            window.location.href = 'index.html';
        } else {
            $.each(res.data, function( index, value ) {
                if(typeof(value) != 'undefined' && value != '' && value != null && value != 'null') {
                    $('#'+index+'_error').html(value);
                    $('#'+index).addClass('input-error');
                }
            });
            dl_alert('danger', res.message, false);
        }
    });
}

function logout() {
    localStorage.setItem("token", '');
    localStorage.setItem("is_admin", '');
    window.location.href = 'index.html';
}
function reload_chapter(chapter_id) {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if(confirm('Bạn có chắc là muốn lấy lại chương truyện này không ?')) {
        var params = {
            book_id: 0,
            chapter_id: chapter_id
        };
        send_api('POST', '/reload', params, function (res) {
            if (res.success) {
                window.location.reload();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}
function delete_chapter(chapter_id) {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    var params = {
        chapter_id: chapter_id
    };
    if(confirm('Bạn có chắc là muốn xóa chương này không ?')) {
        send_api('POST', '/deletechapter', params, function (res) {
            if (res.success) {
                window.location.reload();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}
function delete_chap() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    if(confirm('Bạn có chắc là muốn xóa chương này không ?')) {
        $('#loading-btn').show();
        var params = {
            chapter_id: $('#chapter_id').val()
        };
        send_api('POST', '/deletechapter', params, function (res) {
            $('#loading-btn').hide();
            if (res.success) {
                window.location.href="book.html?id="+$('#book_id').val();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}

function disable() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }

    var message = 'Bạn có chắc là muốn xóa truyện này không ?';
    var book_id = $('#book_id').val();
    var chapter_id = 0;
    if($('#chapter_id').length > 0) {
        book_id = 0;
        chapter_id = $('#chapter_id').val();
        message = 'Bạn có chắc là muốn ẩn chương truyện này không ?';
    }

    if(confirm(message)) {
        $('#loading-btn').show();
        var params = {
            book_id: book_id,
            chapter_id: chapter_id
        };
        send_api('POST', '/disable', params, function (res) {
            $('#loading-btn').hide();
            if (res.success) {
                if($('#chapter_id').length > 0) {
                    window.location.href="book.html?id="+$('#book_id').val();
                } else {
                    window.location.href = "index.html";
                }
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}
function save_tags() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    var book_id = $('#book_id').val();
    if(book_id == '') {
        dl_alert('danger', 'Không có truyện', false);
        return true;
    }
    var tags = $('input[name="tags[]"]:checked');
    var tag_ids = '';
    for(var i=0;i<tags.length;i++) {
        tag_ids += $.trim($(tags[i]).attr('value'))+',';
    }
    var params = {
        book_id: book_id,
        tag_ids: tag_ids
    };
    $('#loading-btn').show();
    send_api('POST', '/save-tags', params, function (res) {
        $('#loading-btn').hide();
        if (res.success) {
            window.location.reload();
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function reset() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    if(confirm('Bạn có chắc là muốn lấy lại truyện này không ?')) {
        $('#loading-btn').show();
        var book_id = $('#book_id').val();
        var params = {
            book_id: book_id
        };
        send_api('POST', '/reset', params, function (res) {
            $('#loading-btn').hide();
            if (res.success) {
                window.location.reload();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}
function reload() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    if(confirm('Bạn có chắc là muốn lấy lại truyện này không ?')) {
        $('#loading-btn').show();
        var book_id = $('#book_id').val();
        var chapter_id = 0;
        if($('#chapter_id').length > 0) {
            book_id = 0;
            chapter_id = $('#chapter_id').val();
        }
        var params = {
            book_id: book_id,
            chapter_id: chapter_id
        };
        send_api('POST', '/reload', params, function (res) {
            $('#loading-btn').hide();
            if (res.success) {
                window.location.reload();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}

function show_profile() {
    send_api('GET', '/profile', {}, function (res) {
        if (res.success) {
            var html ='';
            html += '<div class="a-profile"><h3>Thông tin thành viên</h3></div>';
            html += '<div class="clear5"></div>';
            html += '<hr>';
            html += '<div class="clear5"></div>';
            html += '<div class="a-profile">';
            html += '<label>Tên</label>';
            html += '<span>'+res.data.name+'</span>';
            html += '</div>';
            html += '<div class="a-profile">';
            html += '<label>Tên đăng nhập</label>';
            html += '<span>'+res.data.username+'</span>';
            html += '</div>';
            html += '<div class="a-profile">';
            html += '<label>Email</label>';
            html += '<span>'+res.data.email+'</span>';
            html += '</div>';
            html += '<div class="a-profile">';
            html += '<span><input id="open_form_btn" class="dl-btn-default" type="button" value="Đổi mật khẩu" onclick="show_change_password()"></span>';
            html += '</div>';

            html += '<div id="change-password-form" style="display:none">';
            html += '<div class="a-profile">';
            html += '<label>Mật khẩu hiện tại</label>';
            html += '<div><input id="current_password" type="password"><br><span class="form-dl-error" id="current_password_error"></span></div>';
            html += '</div>';
            html += '<div class="a-profile">';
            html += '<label>Mật khẩu mới</label>';
            html += '<div><input id="password" type="password"><br><span class="form-dl-error" id="password_error"></span></div>';
            html += '</div>';
            html += '<div class="a-profile">';
            html += '<label>Xác nhận mật khẩu</label>';
            html += '<div><input id="password2" type="password"><br><span class="form-dl-error" id="password2_error"></span></div>';
            html += '</div>';
            html += '<div class="a-profile">';
            html += '<label class="no_show_when_small"></label>';
            html += '<span><input class="dl-btn-default" onclick="change_password()" value="Lưu" type="button"></span>';
            html += '</div>';
            html += '</div>';
            $('#profile').html(html);
            $('#profile').show();
            $.each(res.data.options, function( index, value ) {
                var admin_html = '';
                admin_html += '<div class="a-profile">';
                if(index != '-' && index != '--') {
                    admin_html += '<label>' + index + '</label>';
                }
                admin_html += '<span>'+show_number(value)+'</span>';
                admin_html += '</div>';
                $('#profile').append(admin_html);
            });
            if(res.data.is_admin) {
                var html ='<div class="section-container a-book" id="admin-cp">';
                html += '<div class="a-profile"><h3>Dành cho quản trị</h3></div>';
                html += '<div class="clear5"></div>';
                html += '<a style="margin-left:20px" target="_blank" href="http://muontruyen.tk">Administrator Backend</a>';
                html += '<div class="clear5"></div>';
                html += '<hr>';
                html += '<div class="clear5"></div>';
                html += '</div>';
                $('#profile').parent().append(html);
                $.each(res.options, function( index, value ) {
                    var admin_html = '';
                    admin_html += '<div class="a-profile">';
                    if(index != '-' && index != '--') {
                        admin_html += '<label>' + index + '</label>';
                    }
                    if(index != '-' && index != '--') {
                        admin_html += '<span>'+show_number(value)+'</span>';
                    } else {
                        admin_html += '<span>'+value+'</span>';
                    }
                    admin_html += '</div>';
                    $('#admin-cp').append(admin_html);
                });
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function show_change_password() {
    $('#change-password-form').show();
    $('#open_form_btn').hide();
}

function change_password() {
    $('.form-dl-error').html('');
    $('input[type="password"]').removeClass('input-error');
    var params = {
        current_password: $.trim($('#current_password').val()),
        password: $('#password').val(),
        password2: $('#password2').val()
    };
    send_api('POST', '/changepassword', params, function(res) {
        $('#loading-btn').hide();
        $('#login-btn').show();
        if (res.success) {
            dl_alert('success', 'Thay đổi mật khẩu thành công', true);
            window.location.reload();
        } else {
            $.each(res.data, function( index, value ) {
                if(typeof(value) != 'undefined' && value != '' && value != null && value != 'null') {
                    $('#'+index+'_error').html(value);
                    $('#'+index).addClass('input-error');
                }
            });
            dl_alert('danger', res.message, false);
        }
    });
}

function show_tags(type) {
    send_api('GET', '/tags', {type:type}, function(res) {
        if (res.success) {
            if(type == 0) {
                $('h3.page-title').html('Danh sách <b>thể loại</b> ('+res.total+' kết quả)');
            } else {
                $('h3.page-title').html('Danh sách <b>tác giả</b> ('+res.total+' kết quả)');
            }
            $('h3.page-title').show();
            show_tags_list(res);
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function show_tags_list(res) {
    if(res.total > 0) {
        for (var i = 0; i < res.data.length; i++) {
            display_a_tag(res.data[i]);
        }
    }
    $('#list-books .a-book').append('<div style="clear:both;height:10px;"></div>');
    $('#list-books .a-book').show();
}

function show_number(num) {
    num = ''+num;
    return num.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1.");
}
function edit_name() {
    $('#input-textarea').css('height', '36px');
    $('.input-admin').show();
}
function edit_title() {
    $('#action').val('name');
    $('#input-textarea').val($.trim($('.book-title').html()));
    $('#input-textarea').css('height', '36px');
    $('.input-admin').show();
}
function edit_description() {
    $('#action').val('description');
    $('#input-textarea').val($.trim($('.book-description').html()).replace('&nbsp;',''));
    $('#input-textarea').css('height', '100px');
    $('.input-admin').show();
}
function save_chapter_name() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    var name = $.trim($('#input-textarea').val());
    if(name == '') {
        dl_alert('danger', 'Hãy điền thông tin', false);
        return true;
    }
    if(confirm('Bạn có chắc là muốn sửa tên chương này không ?')) {
        $('#loading-btn').show();
        var params = {
            chapter_id: $('#chapter_id').val(),
            name: name
        };
        send_api('POST', '/edit-chapter', params, function (res) {
            $('#loading-btn').hide();
            if (res.success) {
                window.location.reload();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}
function save_data() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if($('#loading-btn').is(":visible")) {
        return true;
    }
    var content = $.trim($('#input-textarea').val());
    if(content == '') {
        dl_alert('danger', 'Hãy điền thông tin', false);
        return true;
    }
    if(confirm('Bạn có chắc là muốn sửa thông tin truyện này không ?')) {
        $('#loading-btn').show();
        var params = {
            book_id: $('#book_id').val(),
            action: $('#action').val(),
            content: content
        };
        send_api('POST', '/edit', params, function (res) {
            $('#loading-btn').hide();
            if (res.success) {
                window.location.reload();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}
function convertToSlug(str)
{
    str = str.replace(/^\s+|\s+$/g, ''); // trim
    str = str.toLowerCase();

    // remove accents, swap ñ for n, etc
    var from = "áăạäặâàấãåčçćďéěëèêẽĕȇíìîïňñóöòôõøðřŕšťúůüùûýÿžþÞđßÆa·/_,:;";
    var to   = "aaaaaaaaaacccdeeeeeeeeiiiinnooooooorrstuuuuuyyzbbdbaa------";
    for (var i=0, l=from.length ; i<l ; i++) {
        str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }

    str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
        .replace(/\s+/g, '-') // collapse whitespace and replace by -
        .replace(/-+/g, '-'); // collapse dashes

    return str.toLowerCase().replace(/[^\w ]+/g,'').replace(/ +/g,'-');
}
function show_search(keyword, page, is_full) {
    var params = {
        keyword: keyword,
        page: page,
        is_full: is_full
    };
    if(is_full == 1) {
        $('#check_full_book').attr('checked','checked');
    }
    $('#check_full_book').attr('onchange','search_again("'+keyword+'")');
    $('#full_book').show();
    send_api('GET', '/search', params, function(res) {
        if (res.success) {
            show_search_result(res, keyword, page);
            cache_images();
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function show_search_result(res, keyword, page) {
    $('h3.page-title').html('Tìm kiếm "<b>'+keyword+'</b>" ('+res.total+' kết quả)');
    $('h3.page-title').show();
    if(res.total > 0) {
        for(var i=0;i<res.data.length;i++) {
            display_a_book(res.data[i]);
        }
    }
    var check_full = 0;
    if($('#check_full_book').is(":checked")) {
        check_full = 1;
    }
    if(res.count_pages > 1) {
        var pages = [];
        for (var i=1;i<=res.count_pages;i++) {
            if (res.count_pages > 3) {
                if((page == 1 && i==3) || (page == res.count_pages && i==res.count_pages-2)) {
                    pages.push(i);
                    continue;
                }
                if (i < page - 1 || i > page + 1) {
                    continue;
                }
            }
            pages.push(i);
        }
        display_paging('search.html?keyword='+keyword+'&is_full='+check_full+'&page=',pages, page, res.count_pages);
    }
}
function search_again(keyword) {
    var check_full = 0;
    if($('#check_full_book').is(":checked")) {
        check_full = 1;
    }
    window.location.href = 'search.html?keyword='+keyword+'&is_full='+check_full;
}
function show_tag(tag_id, page, is_full) {
    var params = {
        tag_id: tag_id,
        page: page,
        is_full: is_full
    };
    if(is_full == 1) {
        $('#check_full_book').attr('checked','checked');
    }
    $('#check_full_book').attr('onchange','tag_again('+tag_id+')');
    if(tag_id != 50) {
        $('#full_book').show();
    }
    send_api('GET', '/tag', params, function(res) {
        if (res.success) {
            if(res.tag.type == 0) {
                $('h3.page-title').html('Thể loại "<b>'+res.tag.name+'</b>" ('+res.total+' kết quả)');
            }
            if(res.tag.type == 1) {
                $('h3.page-title').html('Tác giả "<b>'+res.tag.name+'</b>" ('+res.total+' kết quả)');
            }
            $('h3.page-title').show();
            show_tag_result(res, page, 'tag');
            cache_images();
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function tag_again(tag_id) {
    var check_full = 0;
    if($('#check_full_book').is(":checked")) {
        check_full = 1;
    }
    window.location.href = 'tag.html?id='+tag_id+'&is_full='+check_full;
}

function show_tag_result(res, page, type) {
    if(res.total > 0) {
        for (var i = 0; i < res.data.length; i++) {
            display_a_book(res.data[i]);
        }
    }
    if(res.count_pages > 1) {
        var pages = [];
        for (var i=1;i<=res.count_pages;i++) {
            if (res.count_pages > 3) {
                if((page == 1 && i==3) || (page == res.count_pages && i==res.count_pages-2)) {
                    pages.push(i);
                    continue;
                }
                if (i < page - 1 || i > page + 1) {
                    continue;
                }
            }
            pages.push(i);
        }
        display_paging(type+'.html?id='+res.tag.id+'&page=',pages, page, res.count_pages);
    }
}
function save_to_offline() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    $('#save-btn').removeAttr('onclick');
    dl_alert('success', 'Vui lòng đợi lưu truyện Offline..', false);
    var params = {
        id: $('#book_id').val()
    };
    $('#save-btn').html('<span class="saving-bar"></span><i class="fa fa-spinner fa-spin"></i>&nbsp; Đang lưu Offline...');
    loading_bar(1, 200);
    send_api('GET', '/savebook', params, function(res) {
        if (res.success) {
            save_cache_book(res);
        } else {
            $('#save-btn').html('<i class="fa fa-download"></i>&nbsp; Lưu đọc Offline');
            $('#save-btn').attr('onclick','save_to_offline()');
            dl_alert('danger', res.message, false);
        }
    });
}
function convertImgToBase64URL(url, callback){
    var id = 0;
    var url_arr = url.split('_dl_');
    if(url_arr.length == 2) {
        id = url_arr[0];
        url = url_arr[1];
    }
    var canvas = document.createElement('canvas'),
        ctx = canvas.getContext('2d'), dataURL;
    var img = new Image();
    img.crossOrigin = 'Anonymous';
    img.onload = function(){
        canvas.height = img.height;
        canvas.width = img.width;
        ctx.drawImage(img, 0, 0);
        dataURL = canvas.toDataURL('image/jpg');
        callback(id, dataURL);
        canvas = null;
    };
    img.src = url;
    img.onerror = function(){
        callback(id, 'assets/img/default.jpg');
    };
}
var count_image = 0;
var running_image = 0;
function save_cache_book(res) {
    count_image = 0;
    running_image = 0;
    var tmp_book_save = {};
    tmp_book_save.id = res.data.id;
    tmp_book_save.name = res.data.name;
    tmp_book_save.last_chapter_id = res.data.last_chapter_id;
    tmp_book_save.last_chapter_name = res.data.last_chapter_name;
    tmp_book_save.count_views = res.data.count_views;
    tmp_book_save.description = res.data.description;
    tmp_book_save.release_date = res.data.release_date;
    tmp_book_save.slug = res.data.slug;
    tmp_book_save.chapters = [];
    tmp_book_save.image = '';
    count_image++;
    for (var i = 0; i < res.chapters.length; i++) {
        var chapter = res.chapters[i];
        count_image = count_image + chapter.images.length;
    }
    tmp_book_save.tags = [];
    for (var i = 0; i < res.data.tags.length; i++) {
        tmp_book_save.tags.push(res.data.tags[i].name);
    }
    tmp_book_save.authors = [];
    for (var i = 0; i < res.data.authors.length; i++) {
        tmp_book_save.authors.push(res.data.authors[i].name);
    }
    convertImgToBase64URL(res.data.image, function(id, encode64){
        tmp_book_save.image = encode64;
        running_image++;
        loading_bar(running_image, count_image);
        save_offline_book(tmp_book_save, function(){
            save_cache_chapter(res, 0);
        });
    });
}
function save_cache_chapter(res, ind) {
    if(ind >= res.chapters.length) {
        $('#save-btn').html('<i class="fa fa-check"></i>&nbsp; Đã lưu Offline');
        dl_alert('success', 'Đã lưu truyện Offline', false);
        count_image = 0;
        running_image = 0;
        return true;
    }
    var chapter = res.chapters[ind];
    var tmp_chapter = {};
    tmp_chapter.id = chapter.id;
    tmp_chapter.book_id = res.data.id;
    tmp_chapter.name = chapter.name;
    tmp_chapter.release_date = chapter.release_date;
    tmp_chapter.read = chapter.read;
    tmp_chapter.images = [];
    var chapter_running_image = 0;
    for(var i=0;i<chapter.images.length;i++) {
        var url = i+'_dl_'+chapter.images[i];
        convertImgToBase64URL(url, function(id, encode64){
            running_image++;
            chapter_running_image++;
            loading_bar(running_image, count_image);
            tmp_chapter.images[parseInt(id)] = encode64;
            if(chapter_running_image == chapter.images.length) {
                save_offline_chapter(tmp_chapter, function() {
                    save_cache_chapter(res, ind+1);
                });
            }
        });
    }
}

function loading_bar(running_image, count_image) {
    var tyle = running_image/count_image;
    var width = tyle * 166;
    $('span.saving-bar').css('width', width+'px');
}
function show_offline() {
    interval = setInterval(function() {
        if(typeof(db1) != 'undefined') {
            clearInterval(interval);
            get_all_offline_books(function(data) {
                display_offline_book(data);
                $('#image-refresh').hide();
            });
        }
    },1);
}
function delete_offline(book_id) {
    delete_offline_book(book_id, function(){
        dl_alert('success', 'Đã xoá truyện xem offline', true);
        window.location.href="offline.html";
    });
}
function display_offline_book(book) {
    if(typeof(book) == 'undefined' || book == '') {
        return true;
    }
    var html = '<div class="section-container a-book">';
    html += '<div class="a-cover-new">';
    html += '<a href="offline_book.html?id='+book.id+'"><img style="margin-bottom: 5px" width="100%" src="'+book.image+'" alt="" /></a>';
    html += '</div>';
    html += '<div class="a-description-new">';
    html += '<table>';
    html += '<tr>';
    html += '<td colspan="2">';
    html += '<div class="a-title" style="padding:0"><a href="offline_book.html?id='+book.id+'">'+book.name+'</a></div>';
    html += '</td>';
    html += '</tr>';
    if(book.last_chapter_id > 0) {
        html += '<tr>';
        html += '<td class="label-td">Chương mới</td>';
        html += '<td class="value-td"><a href="offline_chapter.html?id='+book.id+'&c_id=' + book.last_chapter_id + '">' + book.last_chapter_name + '</a></td>';
        html += '</tr>';
    }
    if(typeof(book.tags) != 'undefined' && book.tags.length > 0) {
        html += '<tr>';
        html += '<td class="label-td">Thể loại</td>';
        html += '<td class="value-td">';
        for(var i=0;i<book.tags.length;i++) {
            html += '<a href="javascript:;">'+book.tags[i]+'</a> - ';
        }
        html = html.slice(0,-3);
        html += '</td>';
        html += '</tr>';
    }
    if(typeof(book.authors) != 'undefined' && book.authors.length > 0) {
        html += '<tr>';
        html += '<td class="label-td">Tác giả</td>';
        html += '<td class="value-td">';
        for(var i=0;i<book.authors.length;i++) {
            html += '<a href="javascript:;">'+book.authors[i]+'</a> - ';
        }
        html = html.slice(0,-3);
        html += '</td>';
        html += '</tr>';
    }
    html += '<tr>';
    html += '<td class="label-td">Cập nhật</td>';
    html += '<td class="value-td">'+show_date(book.release_date)+'</td>';
    html += '</tr>';
    html += '</table>';
    html += '</div>';
    html += '<div class="clear5"></div>';
    html += '</div>';
    $('#list-books').append(html);
}
function show_offline_book(id) {
    interval = setInterval(function() {
        if(typeof(db1) != 'undefined') {
            clearInterval(interval);
            get_offline_book(id, function(book) {
                var html = '';
                html += '<div class="book-title">'+book.name+'</div>';
                html += '<div class="clear10"></div>';
                html += '<div class="book-cover">';
                html += '<img src="'+book.image+'">';
                html += '</div>';
                html += '<a href="book.html?id='+book.id+'" class="btn-save-to-offline"><i class="fa fa-globe"></i>&nbsp; Xem Online</a>';
                html += '<a href="javacript:;" onclick="delete_offline('+book.id+')" class="btn-save-to-offline"><i class="fa fa-trash"></i>&nbsp; Xoá Offline</a>';
                html += '<div class="clear10"></div>';
                if($.trim(book.description) == '') {
                    book.description = 'Chưa có thông tin';
                }
                html += '<div class="book-description">'+book.description+'</div>';
                html += '</div>';
                html += '<div class="clear10"></div>';

                var tag_html = '';
                for (var i = 0; i < book.tags.length; i++) {
                    tag_html += '<a href="javascript:;">' + book.tags[i] + '</a> - ';
                }
                if(tag_html == '') {
                    html += '<div>* Truyện chưa phân loại</div>';
                } else {
                    html += '<div style="float:left;margin-right:10px">Thể loại: </div>';
                    html += tag_html.slice(0, -3);
                }
                html += '<div class="clear10" style="height:0"></div>';
                var author_html = '';
                for (var i = 0; i < book.authors.length; i++) {
                    author_html += '<a href="javascript:;">' + book.authors[i] + '</a> - ';
                }
                if(author_html == '') {
                    html += '';
                } else {
                    if(tag_html != '') {
                        html += '<div class="clear5"></div>';
                    }
                    html += '<div style="float:left;margin-right:10px">Tác giả: </div>';
                    html += author_html.slice(0, -3);
                }
                html += '<div class="clear10" style="height:0"></div>';
                $('#book-info').html(html);
                $('#book-info').show();
                show_offline_chapter_list(book.id);
            });
        }
    },1);
}
function show_offline_chapter_list(book_id) {
    interval = setInterval(function() {
        if(typeof(db3) != 'undefined') {
            clearInterval(interval);
            get_list_offline_chapters(book_id, function(data) {
                display_offline_chapter_list(data);
                $('#image-refresh').hide();
            });
        }
    },1);
}
function display_offline_chapter_list(chapters) {
    var html = '';
    if(chapters.length == 0) {
        html += '<div class="book-chapter-list">Không có chương nào</div>';
    } else {
        html += '<div class="book-chapter-list">Danh sách chương ('+chapters.length+' chương)<a href="javascript:;" onclick="move_to_bottom()" class="right-btn">Đến chương đầu</a></div>';
        html += '<div class="clear10"></div>';
        for(var i=0;i<chapter.length;i++) {
            var chapter = chapters[i];
            html += '<div class="a-chapter">';
            html += '<div style="width: calc(100% - 150px);float:left">';
            if(chapter.read) {
                html += ' <a style="color:darkgoldenrod" href="offline_chapter.html?id='+chapter.book_id+'&c_id=' + chapter.id + '">' +chapter.name + '</a>';
            } else {
                html += ' <a href="offline_chapter.html?id='+chapter.book_id+'&c_id='+ chapter.id + '">' +chapter.name + '</a>';
            }
            html += '</div>';
            html += '<div style="width: 135px;float:right">';
            html += chapter.release_date;
            html += '</div>';
            html += '<div class="clear10"></div>';
            html += '</div>';
        }
    }
    $('#chapters-list').html(html);
    $('#chapters-list').show();
    $('#image-refresh').hide();
}
function show_offline_chapter(book_id, chapter_id) {
    interval = setInterval(function() {
        if(typeof(db1) != 'undefined' || typeof(db3) != 'undefined') {
            clearInterval(interval);
            get_offline_book(book_id, function(book) {
                get_list_offline_chapters(book_id, function(chapters) {
                    var chapter = '';
                    var images = [];
                    for(var i=0;i<chapters.length;i++) {
                        if(chapter_id == chapters[i].id) {
                            chapter = chapters[i];
                            images = chapter.images;
                            if(!chapter.read) {
                                var mark_reads = localStorage.getItem("mark_reads");
                                if(mark_reads !== null && mark_reads !== '') {
                                    if (mark_reads.indexOf(',' + chapter.id + ',') == -1) {
                                        mark_reads += ',' + chapter.id + ',';
                                    }
                                } else {
                                    mark_reads = ',' + chapter.id + ',';
                                }
                                localStorage.setItem("mark_reads", mark_reads);
                                chapter.read = true;
                                save_offline_chapter(chapter);
                            }
                            break;
                        }
                    }
                    if (images.length == 0) {
                        dl_alert('danger', 'Truyện không khả dụng', true);
                        window.location.href = "offline_book.html?id="+book.id;
                    }
                    for(var i=0;i<chapters.length;i++) {
                        if(i==0 && chapter.id == chapters[i].id) {
                            chapter.point = 'last';
                        }
                        if(i==chapters.length-1 && chapter.id == chapters[i].id) {
                            chapter.point = 'first';
                        }
                        if(chapter.id == chapters[i].id) {
                            if(i > 0) {
                                chapter.next = chapters[i-1].id;
                            }
                            if(i < chapters.length-1) {
                                chapter.prev = chapters[i+1].id;
                            }
                        }
                    }
                    if(chapters.length == 1) {
                        chapter.point = 'only';
                    }

                    var html = '<div class="clear10"></div>';
                    html+='<a class="book-title" href="offline_book.html?id='+book_id+'">'+book.name+'</a>';
                    html+='<div class="clear10"></div>';
                    html+='<div id="report">';
                    html+='</div>';
                    var paging_html = '';
                    paging_html+='<select class="select-chap" onchange="change_offline_chapter(this, '+book.id+')">';
                    for(var i=0;i<chapters.length;i++) {
                        var a_chapter = chapters[i];
                        if(a_chapter.id == chapter.id) {
                            paging_html+='<option selected value="'+a_chapter.id+'">'+a_chapter.name+'</option>';
                        } else {
                            paging_html+='<option value="'+a_chapter.id+'">'+a_chapter.name+'</option>';
                        }
                    }
                    paging_html+='</select>';
                    paging_html+='<div class="clear5"></div>';
                    paging_html+='<div class="clear10"></div>';
                    if(chapter.point != 'first' && chapter.point != 'only') {
                        paging_html+='<a class="btn-prev" href="offline_chapter.html?id='+book.id+'&c_id='+chapter.prev+'"><i class="fa fa-angle-double-left"></i> Trước</a>';
                    }
                    if(chapter.point != 'last' && chapter.point != 'only') {
                        paging_html+='<a class="btn-next" href="offline_chapter.html?id='+book.id+'&c_id='+chapter.next+'">Sau <i class="fa fa-angle-double-right"></i></a>';
                    }
                    html+=paging_html;
                    html+='<div class="clear10"></div>';
                    html+='<div id="images_list">';
                    for(var i=0;i<images.length;i++) {
                        html+='<img style="width: 100%;margin:5px 0;" src="'+images[i]+'">';
                    }
                    html+='</div>';
                    html+='<div class="clear10"></div>';
                    html+=paging_html;
                    html+='<div class="clear10"></div>';
                    html+='<div class="clear10"></div>';
                    html+='<a class="btn-back" href="offline_book.html?id='+book.id+'">Quay về</a>';
                    html+='<div class="clear10"></div>';
                    $('#chapter-page').html(html);
                    $('#chapter-page').show();
                    $('#image-refresh').hide();
                });
            });
        }
    },1);
}

function change_offline_chapter(ele, book_id) {
    var chapter_id = $(ele).val();
    window.location.href = 'offline_chapter.html?id='+book_id+'&c_id='+chapter_id;
}

function display_follow_paging(tab, current_page, total_page) {
    var html = '<ul class="a-pagging">';
    if(current_page > 1) {
        html += '<li style="float:left;margin:0"><a onclick="show_follow('+tab+','+(current_page-1)+',false)">Trước</a></li>';
    }
    if(current_page < total_page) {
        html += '<li style="float:right;margin:0"><a onclick="show_follow('+tab+','+(current_page+1)+',false)">Sau</a></li>';
    }
    html += '</ul>';
    $('#paging').html(html);
}

function bookmark() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    var params = {
        book_id: $('#book_id').val(),
        chapter_id: $('#chapter_id').val()
    };
    send_api('POST', '/make-bookmark', params, function(res) {
        if (res.success) {
            if(res.data) {
                dl_alert('success', 'Đã đánh dấu', false);
                $('#bookmark-btn').html('<i class="fa fa-star"></i> Bỏ đánh dấu');
                $('#bookmark-btn').removeClass('btn-bookmark-chap').addClass('btn-unbookmark-chap');
            } else {
                dl_alert('success', 'Đã bỏ đánh dấu', false);
                $('#bookmark-btn').html('<i class="fa fa-star"></i> Đánh dấu');
                $('#bookmark-btn').removeClass('btn-unbookmark-chap').addClass('btn-bookmark-chap');
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function show_bookmark(page) {
    var params = {
        page: page
    };
    $('h3.page-title').html('Truyện được đánh dấu');
    $('h3.page-title').show();
    send_api('GET', '/bookmark', params, function(res) {
        if (res.success) {
            show_bookmark_result(res, page);
            cache_images();
        } else {
            if(res.message == 'Không có truyện') {
                $('#list-books').html('<div class="section-container a-book" style="padding: 10px;font-size:17px;">Không có truyện</div>');
            } else {
                dl_alert('danger', res.message, false);
            }
        }
    });
}
function show_bookmark_result(res, page) {
    for(var i=0;i<res.data.length;i++) {
        display_a_book(res.data[i]);
    }
    if(res.count_pages > 1) {
        var pages = [];
        for (var i=1;i<=res.count_pages;i++) {
            if (res.count_pages > 3) {
                if((page == 1 && i==3) || (page == res.count_pages && i==res.count_pages-2)) {
                    pages.push(i);
                    continue;
                }
                if (i < page - 1 || i > page + 1) {
                    continue;
                }
            }
            pages.push(i);
        }
        display_paging('bookmark.html?page=',pages, page, res.count_pages);
    }
}
function filter_tag() {
    var keyword = $.trim($('#filter_tag').val()).toLowerCase();
    var tags = $('.a-tag');
    for(var i=0;i<tags.length;i++) {
        var tag_name = $(tags[i]).text().toLowerCase();
        if(tag_name.indexOf(keyword) > -1) {
            $(tags[i]).show();
        } else {
            $(tags[i]).hide();
        }
    }
}
function show_date(date) {
    return date.replace(get_current_date()+' ','').replace(get_yesterday_date()+' ','Hôm qua ');
}
function get_current_date() {
    var d = new Date();

    var month = d.getMonth()+1;
    var day = d.getDate();

    return (day<10?'0':'')+day +'-'+(month<10?'0':'')+month+'-'+d.getFullYear();
}
function get_yesterday_date() {
    var d = new Date();
    d.setDate(d.getDate() - 1);

    var month = d.getMonth()+1;
    var day = d.getDate();

    return (day<10?'0':'')+day +'-'+(month<10?'0':'')+month+'-'+d.getFullYear();
}
function to_book() {
    var book_id = $('#book_id').val();
    window.location.href = 'book.html?id='+book_id;
}
function prev() {
    var a = $('a.btn-prev')[0];
    window.location.href = $(a).attr('href');
}
function next() {
    var a = $('a.btn-next')[0];
    window.location.href = $(a).attr('href');
}
function login_facebook() {
    if(!$('#loading-btn').is(":visible")) {
        $('#loading-btn').show();
        $('#login-btn').hide();
        $('.form-control').removeClass('input-error');
        $.getScript('https://connect.facebook.net/en_US/sdk.js', function () {
            FB.init({
                appId            : '729161650453723',
                autoLogAppEvents : true,
                xfbml            : true,
                version          : 'v3.0'
            });
            FB.getLoginStatus(function (response) {
                if (response.status === 'connected') {
                    get_facebook_profile();
                } else {
                    $('#loading-btn').hide();
                    $('#login-btn').show();
                    if (response.status !== 'not_authorized') {
                        dl_alert('danger', 'Không thể đăng nhập bằng tài khoản Facebook', false);
                    }
                }
            });
        });
    }
}
function get_facebook_profile(){
    FB.api('/me', {locale: 'en_US', fields: 'id,name,email'},
        function (response) {
            if(typeof(response.id)=='undefined' || typeof(response.email)=='undefined') {
                $('#loading-btn').hide();
                $('#login-btn').show();
                dl_alert('danger', 'Không thể đăng nhập bằng tài khoản Facebook', false);
                return false;
            }
            var params = {
                name: response.name,
                facebook_id: response.id,
                email: response.email,
                device_id: localStorage.getItem("device_id", ''),
                device_type: localStorage.getItem("device_type", ''),
                app_version: APP_VERSION
            };
            send_api('POST', '/login-facebook', params, function(res) {
                $('#loading-btn').hide();
                $('#login-btn').show();
                if (res.success) {
                    localStorage.setItem("token", res.data.token);
                    if(parseInt(res.data.is_admin) == 1) {
                        localStorage.setItem("is_admin", res.data.is_admin);
                    }
                    dl_alert('success', 'Đăng nhập thành công', true);
                    window.location.href = 'index.html';
                } else {
                    dl_alert('danger', res.message, false);
                }
            });
        }
    );
}

function clear_cache() {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    if(!is_admin()) {
        dl_alert('danger', 'Không có quyền thực hiện', false);
        return true;
    }
    if(confirm('Bạn muốn xóa toàn bộ cache ?')) {
        send_api('GET', '/clearcache', {}, function (res) {
            if (res.success) {
                window.location.reload();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}
function view_source(url) {
    if(url == '') {
        return;
    }
    window.open(url, '_blank');
}