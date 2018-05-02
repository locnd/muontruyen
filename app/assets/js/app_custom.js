
function show_home(page) {
    var params = {
        page: page
    };
    send_api('GET', '/home', params, function(res) {
        if (res.success) {
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
                display_paging(pages, page, res.count_pages);
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function display_paging(pages, current_page, total_page) {
    var html = '<ul class="a-pagging">';
    if(current_page > 1) {
        html += '<li><a href="index.html">Đầu</a></li>';
    }
    for(var i=0;i<pages.length;i++) {
        if(pages[i] == current_page) {
            html += '<li class="active">';
        } else {
            html += '<li>';
        }
        html += '<a href="index.html?page='+pages[i]+'">'+pages[i]+'</a>';
        html += '</li>';
    }
    if(current_page < total_page) {
        html += '<li><a href="index.html?page='+total_page+'">Cuối</a></li>';
    }
    html += '</ul>';
    $('#paging').html(html);
}

function display_a_book(book) {
    var html = '<div class="section-container a-book">';
    html += '<div class="a-title"><a href="book.html?id='+book.id+'">'+book.title+'</a></div>';
    html += '<div class="a-cover">';
    html += '<a href="book.html?id='+book.id+'"><img width="100%" src="'+book.image+'" alt="" /></a>';
    html += '</div>';
    html += '<div class="a-description">';
    if(book.description.length > 300) {
        book.description = book.description.substr(0, 300) + '...';
    }
    html += '<span>'+book.description+'</span>';
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
            if(is_admin()) {
                $('#book_id').val(res.data.id);
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

function display_book_info(book, is_following, tags) {
    var html = '<div class="clear10"></div>';
    html += '<div class="book-title">'+book.title+'</div>';
    html += '<div class="clear10"></div>';
    html += '<div class="book-cover">';
    html += '<img src="'+book.image+'">';
    html += '</div>';
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
        html += '<div style="float:left;">Tags:</div>';
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
        html += '<div class="book-chapter-list">Danh sách chương ('+chapters.length+' chương)</div>';
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
            html+='<a class="book-title" href="book.html?id='+book.id+'">'+book.title+'</a>';
            html+='<div class="clear10"></div>';
            html+='<div class="clear10"></div>';
            var paging_html = '';
            paging_html+='<select id="chapter_selecter" class="select-chap" onchange="change_chapter('+book.id+')">';
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
        } else {
            dl_alert('danger', res.message, true);
            window.location.href = "book.html?id="+id;
        }
    });
}

function change_chapter(id) {
    var chapter = $("#chapter_selecter").val();
    window.location.href = 'chapter.html?id='+chapter;
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
                    html += '<div class="a-title"><a href="book.html?id='+book.id+'">'+book.title+'</a></div>';
                    html += '<div class="a-cover" style="width: 90px">';
                    html += '<a href="book.html?id='+book.id+'"><img width="100%" src="'+book.image+'" alt="" /></a>';
                    html += '</div>';
                    html += '<div class="a-description" style="width:calc(100% - 92px)">';
                    if(book.description.length > 300) {
                        book.description = book.description.substr(0, 300) + '...';
                    }
                    html += '<span>'+book.description+'</span>';
                    html += '<div class="clear5"></div>';
                    html += '<div class="a-date">Cập nhật: '+book.release_date+'</div>';
                    html += '</div>';
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
        } else {
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
        password: $('#password').val()
    };
    send_api('POST', '/login', params, function(res) {
        $('#loading-btn').hide();
        $('#login-btn').show();
        if (res.success) {
            localStorage.setItem("token", res.data.token);
            localStorage.setItem("is_admin", res.data.is_admin);
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
        password2: $('#password2').val()
    };
    send_api('POST', '/register', params, function(res) {
        $('#loading-btn').hide();
        $('#login-btn').show();
        if (res.success) {
            localStorage.setItem("token", res.data.token);
            localStorage.setItem("is_admin", res.data.is_admin);
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
            html += '<label>Số truyện theo dõi</label>';
            html += '<span>'+show_number(res.data.follows)+'</span>';
            html += '</div>';
            html += '<div class="a-profile">';
            html += '<label>Số chương đã đọc</label>';
            html += '<span>'+show_number(res.data.reads)+'</span>';
            html += '</div>';
            $('#profile').html(html);
            if(res.data.is_admin) {
                var html ='<div class="section-container a-book">';
                html += '<div class="a-profile"><h3>Dành cho quản trị</h3></div>';
                html += '<div class="clear5"></div>';
                html += '<hr>';
                html += '<div class="clear5"></div>';
                html += '<div class="a-profile">';
                html += '<label>Tổng số thành viên</label>';
                html += '<span>'+show_number(res.options.users)+'</span>';
                html += '</div>';
                html += '<div class="a-profile">';
                html += '<label>Tổng số truyện</label>';
                html += '<span>'+show_number(res.options.books)+'</span>';
                html += '</div>';
                html += '<div class="a-profile">';
                html += '<label>Tổng số truyện bị ẩn</label>';
                html += '<span>'+show_number(res.options.in_active_books)+'</span>';
                html += '</div>';
                html += '<div class="a-profile">';
                html += '<label>Tổng số chương</label>';
                html += '<span>'+show_number(res.options.chapters)+'</span>';
                html += '</div>';
                html += '<div class="a-profile">';
                html += '<label>Tổng số chương bị ẩn</label>';
                html += '<span>'+show_number(res.options.in_active_chapters)+'</span>';
                html += '</div>';
                html += '<div class="a-profile">';
                html += '<label>Tổng số hình ảnh</label>';
                html += '<span>'+show_number(res.options.images)+'</span>';
                html += '</div>';
                html += '<div class="a-profile">';
                html += '<label>Lấy thêm truyện</label>';
                html += '<span><img id="loading-btn" src="data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCgAAACwAAAAAEAAQAAADMwi63P4wyklrE2MIOggZnAdOmGYJRbExwroUmcG2LmDEwnHQLVsYOd2mBzkYDAdKa+dIAAAh+QQJCgAAACwAAAAAEAAQAAADNAi63P5OjCEgG4QMu7DmikRxQlFUYDEZIGBMRVsaqHwctXXf7WEYB4Ag1xjihkMZsiUkKhIAIfkECQoAAAAsAAAAABAAEAAAAzYIujIjK8pByJDMlFYvBoVjHA70GU7xSUJhmKtwHPAKzLO9HMaoKwJZ7Rf8AYPDDzKpZBqfvwQAIfkECQoAAAAsAAAAABAAEAAAAzMIumIlK8oyhpHsnFZfhYumCYUhDAQxRIdhHBGqRoKw0R8DYlJd8z0fMDgsGo/IpHI5TAAAIfkECQoAAAAsAAAAABAAEAAAAzIIunInK0rnZBTwGPNMgQwmdsNgXGJUlIWEuR5oWUIpz8pAEAMe6TwfwyYsGo/IpFKSAAAh+QQJCgAAACwAAAAAEAAQAAADMwi6IMKQORfjdOe82p4wGccc4CEuQradylesojEMBgsUc2G7sDX3lQGBMLAJibufbSlKAAAh+QQJCgAAACwAAAAAEAAQAAADMgi63P7wCRHZnFVdmgHu2nFwlWCI3WGc3TSWhUFGxTAUkGCbtgENBMJAEJsxgMLWzpEAACH5BAkKAAAALAAAAAAQABAAAAMyCLrc/jDKSatlQtScKdceCAjDII7HcQ4EMTCpyrCuUBjCYRgHVtqlAiB1YhiCnlsRkAAAOwAAAAAAAAAAAA==" /><input style="float:none" class="admin-btn" id="scraper-btn" onclick="scraper()" type="button" value="SCRAPER"></span>';
                html += '</div>';
                html += '</div>';
                $('#profile').parent().append(html);
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function show_tags() {
    send_api('GET', '/tags', {}, function(res) {
        if (res.success) {
            $('h3.page-title').html('Danh sách <b>thẻ tag</b> ('+res.total+' kết quả)');
            $('h3.page-title').show();
            if(res.total > 0) {
                for (var i = 0; i < res.data.length; i++) {
                    display_a_tag(res.data[i]);
                }
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}

function show_number(num) {
    num = ''+num;
    return num.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1.");
}
function scraper() {
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
    if(confirm('Bạn có chắc là muốn lấy thêm truyện không ?')) {
        $('#loading-btn').show();
        $('#scraper-btn').hide();
        send_api('POST', '/scraper', {}, function (res) {
            $('#loading-btn').hide();
            $('#scraper-btn').show();
            if (res.success) {
                window.location.reload();
            } else {
                dl_alert('danger', res.message, false);
            }
        });
    }
}
function edit_name(chapter_id) {
    $('#chapter_id').val(chapter_id);
    $('#input-textarea').css('height', '36px');
    $('.input-admin').show();
}
function edit_title() {
    $('#action').val('title');
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
function show_search(keyword) {
    var params = {
        keyword: keyword
    };
    $('h3.page-title').html('Tìm kiếm "<b>'+keyword+'</b>"');
    $('h3.page-title').show();
    send_api('GET', '/search', params, function(res) {
        if (res.success) {
            $('h3.page-title').html('Tìm kiếm "<b>'+keyword+'</b>" ('+res.total+' kết quả)');
            $('h3.page-title').show();
            if(res.total > 0) {
                for(var i=0;i<res.data.length;i++) {
                    display_a_book(res.data[i]);
                }
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}
function show_tag(tag_id) {
    var params = {
        tag_id: tag_id
    };
    send_api('GET', '/tag', params, function(res) {
        if (res.success) {
            $('h3.page-title').html('Thẻ tag "<b>'+res.tag.name+'</b>" ('+res.total+' kết quả)');
            $('h3.page-title').show();
            if(res.total > 0) {
                for (var i = 0; i < res.data.length; i++) {
                    display_a_book(res.data[i]);
                }
            }
        } else {
            dl_alert('danger', res.message, false);
        }
    });
}