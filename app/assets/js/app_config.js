document.addEventListener("deviceready", onDeviceReady, false);
function onDeviceReady() {
    window.open = cordova.InAppBrowser.open;

    var push = PushNotification.init({
        "android": {"senderID": "154739668435"},
        "ios": {"alert": "true", "badge": "true", "sound": "true", "clearBadge": "true" },
        "windows": {}
    });

    push.on('registration', function(data) {
        localStorage.setItem("device_id", data.registrationId);
    });

    push.on('notification', function(data) {
        if ( data.additionalData.foreground ){
            //console.log("when the app is active");
            dl_alert('success', data.message, false);
        } else {
            //console.log("when the app is not active");
        }
        for(var i=0;i<6;i++) {
            localStorage.setItem("time_cache_home_"+i,'');
        }
        check_unread();
    });

    push.on('error', function(e) {
        dl_alert('danger', e.message, false);
    });
}
var db1;
var db2;
var db3;
document.addEventListener("DOMContentLoaded", function(){
    if("indexedDB" in window) {
        var openRequest = indexedDB.open("offline_books",1);
        openRequest.onupgradeneeded = function(e) {
            var thisDB1 = e.target.result;
            if(!thisDB1.objectStoreNames.contains("offline_books")) {
                thisDB1.createObjectStore("offline_books");
            }
        };
        openRequest.onsuccess = function(e) {
            db1 = e.target.result;
        };
        openRequest.onerror = function(e) {
        };
        var openRequest2 = indexedDB.open("home_books",1);
        openRequest2.onupgradeneeded = function(e) {
            var thisDB2 = e.target.result;
            if(!thisDB2.objectStoreNames.contains("home_books")) {
                thisDB2.createObjectStore("home_books");
            }
        };
        openRequest2.onsuccess = function(e) {
            db2 = e.target.result;
        };
        openRequest2.onerror = function(e) {
        };
        var openRequest3 = indexedDB.open("offline_chapters",1);
        openRequest3.onupgradeneeded = function(e) {
            var thisDB3 = e.target.result;
            if(!thisDB3.objectStoreNames.contains("offline_chapters")) {
                thisDB3.createObjectStore("offline_chapters");
            }
        };
        openRequest3.onsuccess = function(e) {
            db3 = e.target.result;
        };
        openRequest3.onerror = function(e) {
        };
    }
},false);

var APP_VERSION = '1.0.7';

// var API_URL = 'http://muontruyen.me/api/v1';
var API_URL = 'http://muontruyen.tk/api/v1';
var enable_cache_images = true;

function getParam(name) {
    var url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results || !results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

$(document).ready(function() {
    $('#footer-copyright').load('layouts/footer.html');

    $('.dl-overlay').click(function() {
        $('#list_group').fadeOut( 500, function() {
            $('.dl-overlay').fadeOut(1);
        });
        $('.dl-modal').fadeOut( 500, function() {
            $('.dl-overlay').fadeOut(1);
        });
    });
    $(document).bind('touchstart, mouseup', function(e) {
        var container = $("#header-navbar");
        if (!container.is(e.target) && container.has(e.target).length === 0){
            if(!$('#btn-more-menu').hasClass('collapsed')) {
                $('#btn-more-menu').trigger('click');
            }
        }
    });

    show_elements();
    check_alert();
    fullscreen(false);
    check_device_type();
    if($('#is_offline').length == 0) {
        get_book_list_for_search();
    } else {
        mark_reads();
    }

    $( window ).scroll(function() {
        if($('#chapter-header').length > 0) {
            check_header();
        }
    });

    ImgCache.options.usePersistentCache = true;
    ImgCache.init();
});
function cache_images() {
    if(enable_cache_images)
        $('img').each(function() {
            $img = $(this);
            var imgSrc = $img.attr('src');
            if(imgSrc.indexOf('http') > -1) {
                ImgCache.isCached(imgSrc, function (path, success) {
                    if (success) {
                        ImgCache.useCachedFile($(this));
                    } else {
                        ImgCache.cacheFile(imgSrc);
                    }
                });
            }
        });
}

var mouseY = 0;
$(document).ready(function() {
    $('body').bind('touchstart', function (ev) {
        if($(window).scrollTop() == 0) {
            var touch_start = ev.originalEvent.touches[0] || ev.originalEvent.changedTouches[0];
            mouseY = touch_start.pageY;
            $(document).bind('touchmove', function (e) {
                var touch_move = e.originalEvent.touches[0] || e.originalEvent.changedTouches[0];
                var moving = touch_move.pageY - mouseY;
                if (moving > 0) {
                    if (moving >= 200) {
                        $('#image-refresh').show();
                    }
                    var screen = localStorage.getItem("screen");
                    if (screen !== null && screen !== '') {
                    } else {
                        screen = 'normal';
                    }
                    if (screen == 'normal') {
                        $('body').css('margin-top', (65 + moving / 3) + 'px');
                    } else {
                        $('body').css('margin-top', (moving / 3) + 'px');
                    }
                } else {
                    $(document).unbind("touchmove");
                }
            });
        }
    });
    $('body').bind('touchend', function () {
        $('body').css('margin-top', '0');
        $(document).unbind("touchmove");
        if($('#image-refresh').length ==1 && $('#image-refresh').is(":visible")) {
            setTimeout(function(){ window.location.reload(); }, 500);
        }
    });
});

function checkLogin(is_need_login) {
    if(is_need_login == 0) { // index, search, book, chapter, tag, tags
        return true;
    }
    if(is_need_login == 1 && !is_logined()) { // profile, follow
        window.location.href = "login.html";
    }
    if(is_need_login == 2 && is_logined()) { // login, register
        window.location.href = "index.html";
    }
}
function show_elements() {
    if (is_logined()) {
        $('.logined').show();
    } else {
        $('.not_logined').show();
    }
}

function check_unread() {
    if(!is_logined()) {
        return true;
    }
    send_api('GET', '/unread', {}, function(res){
        show_unread(res.data);
    });
}

function mark_reads() {
    if(!is_logined()) {
        return true;
    }
    var mark_reads = localStorage.getItem("mark_reads");
    if(mark_reads !== null && mark_reads !== '') {
        send_api('GET', '/markread', {chapter_ids: mark_reads}, function (data) {
            localStorage.setItem("mark_reads", '');
        });
    }
}

function show_unread(unread) {
    if(parseInt(unread) > 0) {
        $('.dl-notify').html(unread);
        $('#btn-more-menu i.dl-notify').removeClass('hidden');
    } else {
        $('.dl-notify').html('');
        if(!$('#btn-more-menu i.dl-notify').hasClass('hidden')) {
            $('#btn-more-menu i.dl-notify').addClass('hidden');
        }
    }
}

function get_book_list_for_search() {
    var book_list_json = localStorage.getItem("book_list_json");
    var book_list_time = localStorage.getItem("book_list_time");
    if(book_list_time !== null && book_list_time !== '' && book_list_json !== null && book_list_json !== '') {
        if($.now() < parseInt(book_list_time) + 3600000) {
            var book_list = JSON.parse(book_list_json);
            show_book_list_for_search(book_list);
            return true;
        }
    }
    send_api('GET', '/bookforsearch', {}, function(data){
        if(data.success) {
            localStorage.setItem("book_list_json", JSON.stringify(data.data));
            localStorage.setItem("book_list_time", $.now());
            show_book_list_for_search(data.data);
        }

    });
}

function show_book_list_for_search(data) {
    var html = '<option value="">Tìm kiếm truyện</option>';
    for(var i=0;i<data.length;i++) {
        html += '<option value="'+data[i].id+'">'+data[i].name+'</option>';
    }
    $('#search_book_select').html(html);
    $('#search_book_select').combobox();
}
var first_error = true;
function send_api(method, url, params, callback) {
    var token = localStorage.getItem("token");
    if(token !== null && token !== '') {
        params.token = token;
    }
    url = url.replace('-','');
    $.ajax({
        url: API_URL + '' + url,
        type: method,
        data: params,
        dataType: 'json',
        success: function(result){
            if(typeof(result.unread) != 'undefined') {
                show_unread(result.unread);
            }
            callback(result);
        },
        error: function( xhr ) {
            $('#loading-btn').hide();
            if(first_error) {
                first_error = false;
                dl_alert('danger', 'Không thể lấy dữ liệu từ server', false);
            }
        }
    });
}

function dl_alert(type, message, save_storage) {
    if(save_storage) {
        localStorage.setItem("alert", type+'_dl_'+message);
        return true;
    }
    $('#alert-flash').html(message);
    $('#alert-flash').addClass('alert-'+type);
    $('div.alert').show();
    $('div.alert').css('z-index',999);
    localStorage.setItem("alert", '');
    setTimeout(function(){
        $('div.alert').fadeOut();
        $('#alert-flash').removeClass('alert-danger');
        $('#alert-flash').removeClass('alert-success');
        $('div.alert').css('z-index',0);
    }, 3000);
}

function is_logined() {
    var token = localStorage.getItem("token");
    if (token !== null && token !== '') {
        return true;
    }
    localStorage.setItem("token", '');
    localStorage.setItem("is_admin", '');
    return false;
}
function is_admin() {
    var is_admin = localStorage.getItem("is_admin");
    if (is_admin !== null && is_admin !== ''
        && typeof(is_admin) !== 'undefined' && is_admin !== 'undefined'
        && is_admin !== '0' && is_admin !== 0) {
        return true;
    }
    return false;
}
function show_modal(id) {
    $('.dl-overlay').fadeIn( 1, function() {
        $('#'+id).fadeIn(500);
    });
}
function close_modal(id) {
    $('#'+id).fadeOut( 500, function() {
        $('.dl-overlay').fadeOut(1);
    });
}

function check_alert() {
    var alert = localStorage.getItem("alert");
    if(alert !== null && alert !== '') {
        var alert_parse = alert.split('_dl_');
        dl_alert(alert_parse[0], alert_parse[1], false);
    }
}

function move_to_top() {
    $("html, body").animate({ scrollTop: 0 }, 500);
    return true;
}

function move_to_bottom() {
    $("html, body").animate({ scrollTop: $(document).height() }, 500);
    return true;
}

function orientation() {
    var sc = screen.orientation.type;
    if(sc.indexOf('portrait') > -1 ) {
        screen.orientation.lock('landscape');
    } else {
        screen.orientation.lock('portrait');
    }
    setTimeout(function() {
        var wid = $('#content').width();
        if(wid > 1200) {
            wid = 1010;
        }
        $('head').find('style').html('ul.ui-autocomplete.ui-front{ width:' + (wid-30) + 'px !important;}');

        var screen = localStorage.getItem("screen");
        if (screen !== null && screen !== '') {} else {
            screen = 'normal';
        }
        wid = $('#content').width();
        var mg_r = 0;
        if(wid > 1200) {
            mg_r = (wid - 1010)/2;
        }
        if(screen == 'fullscreen') {
            mg_r = 15 + mg_r;
            $('.btn-fullscreen').css('right', mg_r+'px');
        } else {
            mg_r = 150 + mg_r;
            $('.btn-fullscreen').css('right', mg_r+'px');
        }
    }, 1500);
}

function fullscreen(change) {
    var screen = localStorage.getItem("screen");
    if (screen !== null && screen !== '') {} else {
        screen = 'normal';
    }
    if(change) {
        if(screen == 'fullscreen') {
            screen = 'normal';
        } else {
            screen = 'fullscreen';
        }
    }
    var wid = $('#content').width();
    var mg_r = 0;
    if(wid > 1200) {
        mg_r = (wid - 1010)/2;
    }
    if(screen == 'fullscreen') {
        mg_r = 15 + mg_r;
        $('#header').hide();
        $('#footer-copyright').hide();
        $('.btn-fullscreen').css('right', mg_r+'px');
        $('#content').css('margin', '10px 0 0 0');
        $('#content').css('padding-bottom', '0');
        $('#content .container .col-md-12').css('padding', '0 5px');
    } else {
        mg_r = 150 + mg_r;
        $('#header').show();
        $('#footer-copyright').show();
        $('.btn-fullscreen').css('right', mg_r+'px');
        $('#content').css('margin', '65px 0 0 0');
        $('#content').css('padding-bottom', '40px');
        $('#content .container .col-md-12').css('padding', '0 15px');
    }
    localStorage.setItem("screen", screen);
}

function check_device_type() {
    var userAgent = navigator.userAgent || navigator.vendor || window.opera;
    if (/windows phone/i.test(userAgent)) {
        localStorage.setItem('device_type', "Windows Phone"); return true;
    }
    if (/android/i.test(userAgent)) {
        localStorage.setItem('device_type', "Android"); return true;
    }
    if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
        localStorage.setItem('device_type', "iOS"); return true;
    }
    localStorage.setItem('device_type', "Unknown");
}
function save_offline_book(book, callback){
    var transaction = db1.transaction(["offline_books"],"readwrite");
    var offline_books = transaction.objectStore("offline_books");
    var req = offline_books.put(book, Number(book.id));
    req.onsuccess = function(e) {
        callback();
    };
}
function save_offline_chapter(chapter, callback) {
    var transaction = db3.transaction(["offline_chapters"],"readwrite");
    var offline_chapters = transaction.objectStore("offline_chapters");
    var req = offline_chapters.put(chapter, Number(chapter.id));
    req.onsuccess = function(e) {
        callback();
    };
}
function delete_offline_book(book_id, callback){
    var transaction = db1.transaction(["offline_books"],"readwrite");
    var offline_books = transaction.objectStore("offline_books");

    var req = offline_books.delete(Number(book_id));
    req.onsuccess = function(e) {
        callback();
    };
}
function delete_offline_chapter(chapter, callback) {
    var transaction = db3.transaction(["offline_chapters"],"readwrite");
    var offline_chapters = transaction.objectStore("offline_chapters");

    var req = offline_chapters.delete(Number(chapter.id));
    req.onsuccess = function(e) {
        callback();
    };
}
function get_offline_book(book_id, callback) {
    var transaction = db1.transaction(["offline_books"],"readonly");
    var offline_books = transaction.objectStore("offline_books");
    var request = offline_books.get(Number(book_id));
    request.onsuccess = function(e) {
        callback(e.target.result);
    };
    request.onerror = function(e) {
        dl_alert('danger', 'Truyện lưu Offline không khả dụng', true);
        window.location.href = "offline.html";
    };
}
function get_list_offline_chapters(book_id, callback) {
    var transaction = db3.transaction(["offline_chapters"],"readonly");
    var offline_chapters = transaction.objectStore("offline_chapters");
    var cursor = offline_chapters.openCursor();

    var data = [];
    cursor.onsuccess = function(e) {
        var res = e.target.result;
        if(res) {
            if(res.value.book_id == book_id) {
                data.push(res.value);
            }
            res.continue();
        } else {
            for(var i=0; i<data.length-1;i++) {
                for(var j=i+1;j<data.length;j++) {
                    if(data[i].stt < data[j].stt) {
                        var tmp = data[i];
                        data[i] = data[j];
                        data[j] = tmp;
                    }
                }
            }
            callback(data);
        }
    };
}
function get_all_offline_books(callback) {
    var transaction = db1.transaction(["offline_books"],"readonly");
    var offline_books = transaction.objectStore("offline_books");
    var cursor = offline_books.openCursor();

    var has_book = false;
    cursor.onsuccess = function(e) {
        var res = e.target.result;
        if(res) {
            has_book = true;
            callback(res.value);
            res.continue();
        }
        if(!has_book) {
            $('#image-refresh').hide();
            dl_alert('danger', 'Không có truyện lưu Offline', false);
        }
    };
}
function get_all_home_books(sort, callback) {
    var transaction = db2.transaction(["home_books"],"readonly");
    var home_books = transaction.objectStore("home_books");
    var cursor = home_books.openCursor();

    var has_book = false;
    var start = sort*20+1;
    var end = (sort+1)*20;
    var id = 0;
    cursor.onsuccess = function(e) {
        var res = e.target.result;
        if(res) {
            id++;
            if(id<start || id>end) {
                res.continue();
            } else {
                has_book = true;
                callback(res.value);
                res.continue();
            }
        }
        if(id>start && !has_book) {
            dl_alert('danger', 'Không có truyện', false);
        }
    };
}
function save_cache_home_books(res,sort) {
    var transaction = db2.transaction(["home_books"], "readwrite");
    var home_books = transaction.objectStore("home_books");
    for(var i=0;i<res.length;i++) {
        var id=(sort * 20)+i+1;
        home_books.put(res[i], id);
    }
}
function check_header() {
    if($(window).scrollTop()>=$('#chapter-page').position().top + 200){
        $('#common-header').hide();
        $('#chapter-header').show();
    } else {
        $('#chapter-header').hide();
        $('#common-header').show();
    }
}
function show_admin() {
    if(is_admin()) {
        $('.for-admin').show();
    }
}
