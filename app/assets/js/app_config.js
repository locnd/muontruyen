document.addEventListener("deviceready", onDeviceReady, false);
function onDeviceReady() {
    window.open = cordova.InAppBrowser.open;

    var push = PushNotification.init({
        "android": {
            "senderID": "154739668435"
        },
        "ios": {"alert": "true", "badge": "true", "sound": "true", "clearBadge": "true" },
        "windows": {}
    });

    push.on('registration', function(data) {
        localStorage.setItem("device_id", data.registrationId);
    });

    push.on('notification', function(data) {
        if ( data.additionalData.foreground ){
            //alert("when the app is active");
            //dl_alert('success', 'Truyện bạn đang theo dõi có cập nhật chương mới', false);
        } else {
            //alert("when the app is not active");
            localStorage.setItem("push_notification", 'true');
            //dl_alert('success', 'Truyện bạn đang theo dõi có cập nhật chương mới', false);
        }
    });

    push.on('error', function(e) {
        dl_alert('danger', e.message, false);
    });
}

// var API_URL = 'http://muontruyen.me/api/v1';
var API_URL = 'http://muontruyen.tk/api/v1';

function getParam(name) {
    var url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results || !results[2]) return '00';
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
    check_unread();
    check_alert();
    fullscreen(false);
    check_push_notification();
    get_book_list_for_search();
});

var mouseY = 0;
$(document).ready(function() {
    $('body').bind('touchstart', function (ev) {
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
                if (screen !== null && screen !== '') {} else {
                    screen = 'normal';
                }
                if(screen == 'normal') {
                    $('body').css('margin-top', (80 + moving / 3) + 'px');
                } else {
                    $('body').css('margin-top', (moving / 3) + 'px');
                }
            } else {
                $(document).unbind("touchmove");
            }
        });
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
    if(is_need_login == 0) { // home, search, book, chapter, tag
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
        if(is_admin()) {
            $('.for-admin').show();
        }
        $('.logined').show();
    } else {
        $('.not_logined').show();
    }
}

function check_unread() {
    if(!is_logined()) {
        return true;
    }
    send_api('GET', '/unread', {}, function(data){
        show_unread(data.data);
    });
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
        if($.now() < parseInt(book_list_time) + 7200000) {
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
    var html = '<option value=""></option>';
    for(var i=0;i<data.length;i++) {
        html += '<option value="'+data[i].id+'">'+data[i].name+'</option>';
    }
    $('#search_book_select').html(html);
    $('#search_book_select').combobox();
}

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
            callback(result);
        },
        error: function( xhr ) {
            $('#loading-btn').hide();
            dl_alert('danger', 'Không thể lấy dữ liệu từ server: '+API_URL + '' + url, false);
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
    setTimeout(function(){
        $('div.alert').fadeOut();
        $('#alert-flash').removeClass('alert-danger');
        $('#alert-flash').removeClass('alert-success');
        localStorage.setItem("alert", '');
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

function check_push_notification() {
    var check = localStorage.getItem("push_notification");
    if(check !== null && check !== '') {
        localStorage.setItem("push_notification", "");
        window.location.href="follow.html";
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
    if(screen == 'fullscreen') {
        $('#header').hide();
        $('#footer-copyright').hide();
        $('.btn-fullscreen').css('right', '15px');
        $('#content').css('margin', '10px 0 0 0');
        $('#content').css('padding-bottom', '0');
        $('#content .container .col-md-12').css('padding', '0 5px');
    } else {
        $('#header').show();
        $('#footer-copyright').show();
        $('.btn-fullscreen').css('right', '155px');
        $('#content').css('margin', '84px 0 0 0');
        $('#content').css('padding-bottom', '40px');
        $('#content .container .col-md-12').css('padding', '0 15px');
    }
    localStorage.setItem("screen", screen);
}
