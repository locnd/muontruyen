
function show_home(page) {
    if(page == 1 && !is_admin()) {
        var time_cache = localStorage.getItem("time_cache_home");
        if (time_cache !== null && time_cache !== '' && $.now() < parseInt(time_cache) + 900000) {
            get_cache_home();
            return true;
        }
    }

    var params = {
        page: page,
        device_id: localStorage.getItem("device_id"),
        device_type: localStorage.getItem("device_type"),
        app_version: APP_VERSION
    };
    send_api('GET', '/home', params, function(res) {
        if (res.success) {
            if(page == 1 && !is_admin()) {
                localStorage.setItem("time_cache_home", $.now());
                localStorage.setItem("count_pages", res.count_pages);
                save_cache_home_books(res.data);
            }
            show_home_content(res, page);
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
var interval = null;
function get_cache_home() {
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
        display_paging('index.html?page=',pages, page, count_pages);
    }
    interval = setInterval(function() {
        if(typeof(db2) != 'undefined') {
            clearInterval(interval);
            get_all_home_books(function(data){
                display_a_book(data);
                $('#paging').show();
            });
        }
    },1);
}
function show_home_content(res,page) {
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
        display_paging('index.html?page=',pages, page, res.count_pages);
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
    html += '<div class="a-title"><a href="book.html?id='+book.id+'">'+book.name+'</a></div>';
    html += '<div class="a-cover">';
    html += '<a href="book.html?id='+book.id+'"><img width="100%" src="'+book.image+'" alt="" /></a>';
    html += '</div>';
    html += '<div class="a-description">';
    html += '<span>'+get_mini_description(book.description, 60)+'</span>';
    html += '</div>';
    html += '<div class="clear5"></div>';
    html += '<div class="a-date">Cập nhật: '+book.release_date+'</div>';
    html += '</div>';
    $('#list-books').append(html);
}

function display_a_tag(tag) {
    var html='<div class="a-tag">';
    html += '<a href="tag.html?id='+tag.id+'">';
    html += '<img src="assets/img/tag.png">'+tag.name + ' ('+tag.count+')';
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
                $('#reload-btn').attr('onclick','reload('+res.data.id+',0)');
                $('#edit-title-btn').attr('onclick','edit_title()');
                $('#edit-description-btn').attr('onclick','edit_description()');
                $('#disable-btn').attr('onclick','disable('+res.data.id+',0)');
                $('#moving-manage-btn').attr('onclick','$(".mov-btn").toggle()');
                $('#tag-btn').attr('onclick','show_modal("tag-modal");');

                for(var i=0;i<res.tags.length;i++) {
                    show_select_tag(res.tags[i], res.tags[i].is_checked);
                }
            }
            $('#send-report-btn').attr('onclick','send_report('+res.data.id+',0)');

            var is_following = res.options.is_following;
            display_book_info(res.data, is_following, res.tags);
            display_list_chapters(res.chapters);
            display_groups(res.data.id, res.groups);
            if(res.options.make_read) {
                show_unread(res.options.unread);
            }
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

function send_report(book_id, chapter_id) {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    var content = $.trim($('#report_content').val());
    if(content == '') {
        dl_alert('danger', 'Hãy nhập nội dung lỗi', false);
        return false;
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

function display_book_info(book, is_following, tags) {
    var html = '';
    html += '<div class="book-title">'+book.name+'</div>';
    html += '<div class="clear10"></div>';
    html += '<div id="report">';
    html += '<a onclick="open_reports()" class="dl-btn-default fl-r">Báo lỗi</a>';
    html += '<div class="clear0"></div>';
    html += '</div>';
    html += '<div class="book-cover">';
    html += '<img src="'+book.image+'">';
    html += '</div>';
    html += '<div id="save-btn" onclick="save_to_offline()" class="btn-save-to-offline"><i class="fa fa-download"></i> Lưu đọc Offline</div>';
    html += '<div class="clear10"></div>';
    html += '<div class="book-description">'+book.description+'</div>';
    html += '<div class="clear10"></div>';
    if(is_following) {
        html += '<div id="follow-btn" onclick="follow('+is_following+', '+book.id+')" class="btn-unbookmark">';
    } else {
        html += '<div id="follow-btn" onclick="follow('+is_following+', '+book.id+')" class="btn-bookmark">';
    }
    html += '</div>';
    html += '<div class="clear10"></div>';

    var tag_html = '';
    for (var i = 0; i < tags.length; i++) {
        if (tags[i].is_checked) {
            tag_html += '<a href="tag.html?id=' + tags[i].id + '" class="available_tag">' + tags[i].name + '</a>';
        }
    }
    if(tag_html == '') {
        html += '<div style="margin-bottom:10px">* Truyện chưa ngắn thẻ tag</div>';
    } else {
        html += '<div style="float:left;"><img style="width: 25px" src="assets/img/tag.png"></div>';
        html += tag_html;
    }
    html += '<div class="clear10" style="height:0"></div>';
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

function follow(is_following, book_id) {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
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
        dl_alert('danger', 'Điền tên thẻ tag', false);
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
        if(group_id > 0) {
            var groups = $('.dl-group-name');
            for (var i = 0; i < groups.length - 1; i++) {
                if (group_name == $.trim($(groups[i]).html())) {
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
    send_api('POST', '/make-follow', params, function(res) {
        if (res.success) {
            is_following = res.data;
            if(is_following) {
                dl_alert('success', 'Đã theo dõi', false);
                $('#list_group').fadeOut( 500, function() {
                    $('.dl-overlay').fadeOut(1);
                    if(group_id == 0) {
                        display_groups(book_id, res.groups);
                    }
                });
                $('#follow-btn').removeClass('btn-bookmark').addClass('btn-unbookmark');
            } else {
                dl_alert('success', 'Đã bỏ theo dõi', false);
                $('#follow-btn').removeClass('btn-unbookmark').addClass('btn-bookmark');
            }
            $('#follow-btn').attr('onclick', 'follow('+is_following+','+book_id+')');
        } else {
            dl_alert('danger', res.message, true);
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
        html += '<span><input style="padding: 0 10px" onfocus="$(\'#group0\').prop(\'checked\',true);" id="input_group_name" type="text"></span>';
        html += '</label>';
        html += '</div>';
    }
    html += '<div class="btn-send-follow" onclick="send_follow('+book_id+',false)">Theo dõi</div>';
    html += '<div>';
    html += '<label>Chỉ có thể tạo tối đa 5 nhóm</label>';
    html += '</div>';
    $('#list_group').html(html);
}

function show_chapter(id) {
    var params = {
        id: id
    };
    send_api('GET', '/chapter', params, function(res) {
        if (res.success) {
            if(is_admin()) {
                $('#reload-btn').attr('onclick','reload(0,'+res.data.id+')');
                $('#disable-btn').attr('onclick','disable(0,'+res.data.id+')');
                $('#edit-name-btn').attr('onclick','edit_name('+res.data.id+')');
                $('#moving-manage-btn').attr('onclick','$(".mov-btn").toggle()');
                $('#input-textarea').val(res.data.name);
            }
            var chapter = res.data;
            var book = res.book;
            var images = res.images;
            var chapters = res.chapters;
            for(var i=0;i<chapters.length;i++) {
                if(i==0 && chapter.stt == chapters[i].stt) {
                    chapter.point = 'last';
                }
                if(i==chapters.length-1 && chapter.stt == chapters[i].stt) {
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
            html+='<a class="book-title" href="book.html?id='+book.id+'">'+book.name+'</a>';
            html+='<div class="clear10"></div>';
            html+='<div id="report">';
            html+='<a onclick="open_reports()" class="dl-btn-default fl-r">Báo lỗi</a>';
            html+='<div class="clear10"></div>';
            html+='</div>';
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
            $('#send-report-btn').attr('onclick','send_report('+book.id+','+res.data.id+')');
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

function show_follow(tab, is_first) {
    var params = {
        tab: tab
    };
    send_api('GET', '/follow', params, function(res) {
        if (res.success) {
            var books = res.data;
            var groups = res.groups;
            if(is_first) {
                var html = '';
                for (var i = 0; i < groups.length; i++) {
                    html += '<div id="group' + groups[i].id + '" onclick="show_follow(' + groups[i].id + ', false)" class="section-container a-book">';
                    html += '<div>' + groups[i].name + ' (' + groups[i].count + ')</div>';
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
                    html += '<div class="a-title"><a href="book.html?id='+book.id+'">'+book.name+'</a></div>';
                    html += '<div class="a-cover" style="width: 90px">';
                    html += '<a href="book.html?id='+book.id+'"><img width="100%" src="'+book.image+'" alt="" /></a>';
                    html += '</div>';
                    html += '<div class="a-description" style="width:calc(100% - 92px)">';
                    html += '<span>'+get_mini_description(book.description, 30)+'</span>';
                    html += '</div>';
                    html += '<div class="clear5"></div>';
                    html += '<div class="a-date">Cập nhật: '+book.release_date+'</div>';
                    html += '<div class="clear5"></div>';
                    html += '</div>';
                }
            }
            $('#list_follows').html(html);
            if(tab == 0) {
                show_unread(books.length);
                if (books.length > 0) {
                    $('#group0').show();
                } else {
                    $('#group0').hide();
                    if (groups.length > 0) {
                        show_follow(groups[0].id, false);
                        return true;
                    }
                }
            }
            $('.a-book.active').removeClass('active');
            $('#group'+tab).addClass('active');
            if(groups.length == 0) {
                $('#list_groups').hide();
                $('#list_follows').css('width','100%');
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function get_mini_description(description, count) {
    var description_arr = description.split(' ');
    if(description_arr.length > count) {
        description = '';
        for(var j=0;j<count;j++) {
            description += description_arr[j]+' ';
        }
        description = $.trim(description) + '...';
    }
    return description;
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

function disable(book_id, chapter_id) {
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
    if(confirm('Bạn có chắc là muốn ẩn truyện này không ?')) {
        $('#loading-btn').show();
        var params = {
            book_id: book_id,
            chapter_id: chapter_id
        };
        send_api('POST', '/disable', params, function (res) {
            $('#loading-btn').hide();
            if (res.success) {
                window.location.href="index.html";
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
function reload(book_id, chapter_id) {
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
            html += '<span><input style="border:1px solid lightgrey" id="open_form_btn" class="dl-btn-default" type="button" value="Đổi mật khẩu" onclick="show_change_password()"></span>';
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
            html += '<label></label>';
            html += '<span><input class="dl-btn-default" onclick="change_password()" value="Lưu" type="button"></span>';
            html += '</div>';
            html += '</div>';
            $('#profile').html(html);
            $.each(res.data.options, function( index, value ) {
                var admin_html = '';
                admin_html += '<div class="a-profile">';
                admin_html += '<label>'+index+'</label>';
                admin_html += '<span>'+show_number(value)+'</span>';
                admin_html += '</div>';
                $('#profile').append(admin_html);
            });
            if(res.data.is_admin) {
                var html ='<div class="section-container a-book" id="admin-cp">';
                html += '<div class="a-profile"><h3>Dành cho quản trị</h3></div>';
                html += '<div class="clear5"></div>';
                html += '<a style="margin-left:20px" href="http://muontruyen.tk">Administrator Backend</a>';
                html += '<div class="clear5"></div>';
                html += '<hr>';
                html += '<div class="clear5"></div>';
                html += '</div>';
                $('#profile').parent().append(html);
                $.each(res.options, function( index, value ) {
                    var admin_html = '';
                    admin_html += '<div class="a-profile">';
                    admin_html += '<label>'+index+'</label>';
                    admin_html += '<span>'+show_number(value)+'</span>';
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

function show_tags() {
    send_api('GET', '/tags', {}, function(res) {
        if (res.success) {
            show_tags_list(res);
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function show_tags_list(res) {
    $('h3.page-title').html('Danh sách <b>thẻ tag</b> ('+res.total+' kết quả)');
    $('h3.page-title').show();
    if(res.total > 0) {
        for (var i = 0; i < res.data.length; i++) {
            display_a_tag(res.data[i]);
        }
    }
    $('#list-books .a-book').show();
}

function show_number(num) {
    num = ''+num;
    return num.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1.");
}
function edit_name(chapter_id) {
    $('#chapter_id').val(chapter_id);
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
function show_search(keyword, page) {
    var params = {
        keyword: keyword,
        page: page
    };
    $('h3.page-title').html('Tìm kiếm "<b>'+keyword+'</b>"');
    $('h3.page-title').show();
    send_api('GET', '/search', params, function(res) {
        if (res.success) {
            show_search_result(res, keyword, page);
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
        display_paging('search.html?keyword='+keyword+'&page=',pages, page, res.count_pages);
    }
}
function show_tag(tag_id, page) {
    var params = {
        tag_id: tag_id,
        page: page
    };
    send_api('GET', '/tag', params, function(res) {
        if (res.success) {
            show_tag_result(res, page);
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function show_tag_result(res, page) {
    $('h3.page-title').html('Thẻ tag "<b>'+res.tag.name+'</b>" ('+res.total+' kết quả)');
    $('h3.page-title').show();
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
        display_paging('tag.html?id='+res.tag.id+'&page=',pages, page, res.count_pages);
    }
}
function save_to_offline(noti) {
    if(!is_logined()) {
        dl_alert('danger', 'Vui lòng đăng nhập', false);
        return true;
    }
    $('#save-btn').removeAttr('onclick');
    dl_alert('success', 'Vui lòng đợi lưu truyện Offline..', false);
    var params = {
        id: $('#book_id').val()
    };
    $('#save-btn').html('<span class="saving-bar"></span><i class="fa fa-spinner fa-spin"></i> Đang lưu Offline...');
    $('#save-btn').css('width','185px');
    send_api('GET', '/savebook', params, function(res) {
        if (res.success) {
            save_cache_book(res);
        } else {
            $('#save-btn').html('<i class="fa fa-download"></i> Lưu đọc Offline');
            $('#save-btn').css('width','155px');
            $('#save-btn').attr('onclick','save_to_offline()');
            dl_alert('danger', res.message, false);
        }
    });
}
function convertImgToBase64URL(url, callback){
    var url_arr = url.split('_dl_');
    var id = 0;
    if(url_arr.length == 2) {
        id = url_arr[0];
        url = url_arr[1];
    }
    var canvas = document.createElement('canvas'),
        ctx = canvas.getContext('2d'), dataURL;
    var img = new Image();
    img.crossOrigin = '';
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
var tmp_book_save = {};
var count_image = 0;
var running_image = 0;
function save_cache_book(res) {
    count_image = 0;
    running_image = 0;
    tmp_book_save = {};
    tmp_book_save.id = res.data.id;
    tmp_book_save.name = res.data.name;
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
        var tmp_chapter = {};
        tmp_chapter.id = chapter.id;
        tmp_chapter.name = chapter.name;
        tmp_chapter.release_date = chapter.release_date;
        tmp_chapter.stt = chapter.stt;
        tmp_chapter.read = chapter.read;
        tmp_chapter.images = [];
        count_image = count_image + chapter.images.length;
        tmp_book_save.chapters.push(tmp_chapter);
    }
    tmp_book_save.tags = [];
    for (var i = 0; i < res.tags.length; i++) {
        tmp_book_save.tags.push(res.tags[i].name);
    }
    convert_images_to_base64(res);
}
function convert_images_to_base64(res) {
    convertImgToBase64URL(res.data.image, function(id, encode64){
        tmp_book_save.image = encode64;
        running_image++;
        loading_bar(running_image, count_image);
        if(running_image == count_image) {
            save_offline_book_images();
        }
    });
    for(var i=0;i<res.chapters.length;i++) {
        var chapter = res.chapters[i];
        for(var j=0;j<chapter.images.length;j++) {
            var url = i+'_'+j+'_dl_'+chapter.images[j];
            convertImgToBase64URL(url, function(id, encode64){
                running_image++;
                loading_bar(running_image, count_image);
                var tmp_id = id.split('_');
                var data = tmp_id[1] + '_dl_' + encode64;
                tmp_book_save.chapters[parseInt(tmp_id[0])].images.push(data);
                if(running_image == count_image) {
                    save_offline_book_images();
                }
            });
        }
    }
}

function save_offline_book_images() {
    var tmp_book_saved = tmp_book_save;
    for(var i=0;i<tmp_book_save.chapters.length;i++) {
        var chapter = tmp_book_save.chapters[i];
        var tmp_images = [];
        for(var stt=0;stt<chapter.images.length;stt++) {
            for (var k = 0; k < chapter.images.length; k++) {
                var tmp_image = chapter.images[k].split('_dl_');
                if (parseInt(tmp_image[0]) == stt) {
                    tmp_images.push(tmp_image[1]);
                    break;
                }
            }
        }
        tmp_book_saved.chapters[i].images = tmp_images;
    }
    save_offline_book(tmp_book_saved, true);
    count_image = 0;
    running_image = 0;
    tmp_book_save = {};
}

function loading_bar(running_image, count_image) {
    var tyle = running_image/count_image;
    var width = tyle * 185;
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
    html += '<div class="a-title"><a href="offline_book.html?id=' + book.id + '">' + book.name + ' - ' + book.last_chapter_name +'</a></div>';
    html += '<div class="a-cover">';
    html += '<a href="offline_book.html?id=' + book.id + '"><img width="100%" src="' + book.image + '" alt="" /></a>';
    html += '</div>';
    html += '<div class="a-description">';
    html += '<span>' + get_mini_description(book.description, 60) + '</span>';
    html += '</div>';
    html += '<div class="clear5"></div>';
    html += '<div class="a-date">Cập nhật: ' + book.release_date + '</div>';
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
                html += '<a href="book.html?id='+book.id+'" class="btn-save-to-offline" style="width: 130px"><i class="fa fa-globe"></i> Xem Online</a>';
                html += '<div class="clear0"></div>';
                html += '<a href="javacript:;" onclick="delete_offline('+book.id+')" class="btn-save-to-offline" style="width: 165px"><i class="fa fa-trash"></i> Xoá xem Offline</a>';
                html += '<div class="clear10"></div>';
                html += '<div class="book-description">'+book.description+'</div>';
                html += '</div>';
                html += '<div class="clear10"></div>';

                var tag_html = '';
                for (var i = 0; i < book.tags.length; i++) {
                    tag_html += '<a href="javascript:;" class="available_tag">' + book.tags[i] + '</a>';
                }
                if(tag_html == '') {
                    html += '<div style="margin-bottom:10px">* Truyện chưa ngắn thẻ tag</div>';
                } else {
                    html += '<div style="float:left;"><img style="width: 25px" src="assets/img/tag.png"></div>';
                    html += tag_html;
                }
                html += '<div class="clear10" style="height:0"></div>';
                $('#book-info').html(html);
                $('#book-info').show();

                var html = '';
                var chapters = book.chapters;
                if(chapters.length == 0) {
                    html += '<div class="book-chapter-list">Không có chương nào</div>';
                } else {
                    html += '<div class="book-chapter-list">Danh sách chương ('+chapters.length+' chương)<a href="javascript:;" onclick="move_to_bottom()" class="right-btn">Đến chương đầu</a></div>';
                    html += '<div class="clear10"></div>';
                    for(var i=0;i<chapters.length;i++) {
                        var chapter = chapters[i];
                        html += '<div class="a-chapter">';
                        html += '<div style="width: calc(100% - 150px);float:left">';
                        if(chapter.read) {
                            html += ' <a style="color:darkgoldenrod" href="offline_chapter.html?id='+book.id+'&c_id=' + chapter.id + '">' +chapter.name + '</a>';
                        } else {
                            html += ' <a href="offline_chapter.html?id='+book.id+'&c_id='+ chapter.id + '">' +chapter.name + '</a>';
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
            });
        }
    },1);
}
function show_offline_chapter(book_id, chapter_id) {
    interval = setInterval(function() {
        if(typeof(db1) != 'undefined') {
            clearInterval(interval);
            get_offline_book(book_id, function(book) {
                var chapter = '';
                for(var i=0;i<book.chapters.length;i++) {
                    if(chapter_id == book.chapters[i].id) {
                        chapter = book.chapters[i];
                        images = chapter.images;
                        if(!book.chapters[i].read) {
                            var mark_reads = localStorage.getItem("mark_reads");
                            if(mark_reads !== null && mark_reads !== '') {
                                if (mark_reads.indexOf(',' + book.chapters[i] + ',') == -1) {
                                    mark_reads += ',' + book.chapters[i] + ',';
                                }
                            } else {
                                mark_reads = ',' + book.chapters[i] + ',';
                            }
                            localStorage.setItem("mark_reads", mark_reads);
                            book.chapters[i].read = true;
                            save_offline_book(book, false);
                        }
                        break;
                    }
                }
                if (chapter == '') {
                    dl_alert('danger', 'Truyện không khả dụng', true);
                    window.location.href = "offline_book.html?id="+book.id;
                }
                var images = chapter.images;
                var chapters = book.chapters;
                for(var i=0;i<chapters.length;i++) {
                    if(i==0 && chapter.stt == chapters[i].stt) {
                        chapter.point = 'last';
                    }
                    if(i==chapters.length-1 && chapter.stt == chapters[i].stt) {
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
        }
    },1);
}

function change_offline_chapter(ele, book_id) {
    var chapter_id = $(ele).val();
    window.location.href = 'offline_chapter.html?id='+book_id+'&c_id='+chapter_id;
}