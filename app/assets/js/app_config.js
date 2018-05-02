document.addEventListener("deviceready", onDeviceReady, false);
function onDeviceReady() {
    window.open = cordova.InAppBrowser.open;
}

// var API_URL = 'http://truyentranh.me/api/v1';
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
                $('body').css('margin-top', (80+moving/3) + 'px');
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
    if (is_admin !== null && is_admin !== '' && is_admin) {
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
