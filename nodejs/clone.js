var request = require("request");
var mysql = require('mysql');
var DomParser = require('dom-parser');
var parser = new DomParser();
var exec = require('child_process').exec;

var con = mysql.createConnection({
    host: "localhost",
    user: "root",
    password: "l!o@c#n$g%u^y&e*n",
    database: "muontruyen"
});
var command_exit = 'php /var/www/muontruyen/yii done ';

var use_proxy = true;
var proxies = [
    "209.205.212.34:222",
    "209.205.212.35:222",
    "209.205.212.36:222",
    "209.205.212.37:222",
    "209.205.212.38:222",
    "209.205.212.34:1200",
    "209.205.212.34:1201",
    "209.205.212.34:1202",
    "209.205.212.34:1203",
    "209.205.212.34:1204",
    "209.205.212.34:1205",
    "209.205.212.34:1206",
    "209.205.212.34:1207",
    "209.205.212.34:1208",
    "209.205.212.34:1209",
    "209.205.212.34:1210",
    "209.205.212.34:1211",
    "209.205.212.34:1212",
    "209.205.212.34:1213",
    "209.205.212.34:1214",
    "209.205.212.34:1215",
    "209.205.212.34:1216",
    "209.205.212.34:1217",
    "209.205.212.34:1218",
    "209.205.212.34:1219",
    "209.205.212.34:1220",
    "209.205.212.34:1221",
    "209.205.212.34:1222",
    "209.205.212.34:1223",
    "209.205.212.34:1224",
    "209.205.212.34:1225",
    "209.205.212.34:1226",
    "209.205.212.34:1227",
    "209.205.212.34:1228",
    "209.205.212.34:1229",
    "209.205.212.34:1230",
    "209.205.212.34:1231",
    "209.205.212.34:1232",
    "209.205.212.34:1233",
    "209.205.212.34:1234",
    "209.205.212.34:1235",
    "209.205.212.34:1236",
    "209.205.212.34:1237",
    "209.205.212.34:1238",
    "209.205.212.34:1239",
    "209.205.212.34:1240",
    "209.205.212.34:1241",
    "209.205.212.34:1242",
    "209.205.212.34:1243",
    "209.205.212.34:1244",
    "209.205.212.34:1245",
    "209.205.212.34:1246",
    "209.205.212.34:1247",
    "209.205.212.34:1248",
    "209.205.212.34:1249",
    "209.205.212.34:1250"
];

var cm_book_cron_id = 0;
var cm_book_id = 0;

var total_image = -1;
var count_image = 0;
var total_author = -1;
var count_author = 0;
var total_tag = -1;
var count_tag = 0;
var total_chap = -1;
var count_skip_chap = 0;

con.connect(function(err) {
    console.log("Da ket noi database MySQL");
});

var point_count_clone_chap = -1;
var dem_delay = 0;
var interval = setInterval(function() {
    if(cm_book_id > 0) {
        if (point_count_clone_chap < count_clone_chap) {
            point_count_clone_chap = count_clone_chap;
        } else {
            console.log('cloned chap = ' + count_clone_chap + ' / ' + (total_chap-count_skip_chap));
            clearInterval(interval);
            exec(command_exit + "" + cm_book_id, function (err, stdout, stderr) {
                console.log('---- Done');
                process.exit();
            });
        }
    } else {
        if(dem_delay > 3) {
            var sql = 'UPDATE dl_book_cron SET status=0, updated_at="'+current_time()+'" WHERE id="'+cm_book_cron_id+'"';
            con.query(sql, function (err, result) {
                console.log('---- done');
                process.exit();
            });
        } else {
            dem_delay++;
        }
    }
}, 60000);

var sql = 'SELECT * FROM dl_book_cron WHERE status=1';
con.query(sql, function (err, result) {
    if (result.length < 1) {
        get_book_cron();
    } else {
        var book_cron = result[0];
        var diff = Math.abs(new Date(current_time()) - new Date(book_cron.updated_at));
        var minutes = Math.floor((diff/1000)/60);
        if(minutes > 31) {
            var sql = 'UPDATE dl_book_cron SET status=0, updated_at="' + current_time() + '" WHERE id="' + book_cron.id + '"';
            con.query(sql, function (err, result) {
                process.exit();
            });
        } else {
            console.log('So cron dang chay = ' + result.length);
            process.exit();
        }
    }
});

function get_book_cron() {
    var sql = 'SELECT * FROM dl_book_cron WHERE status=0 LIMIT 1';
    con.query(sql, function (err, result) {
        if (result.length > 0) {
            var book_cron = result[0];
            cm_book_cron_id = book_cron.id;
            var sql = 'UPDATE dl_book_cron SET status=1, updated_at="' + current_time() + '" WHERE id="' + book_cron.id + '"';
            con.query(sql, function (err, result) {
                check_book(book_cron);
            });
        } else {
            console.log('Khong co book cron');
            process.exit();
        }
    });
}

function finish_book_cron() {
    var sql = 'UPDATE dl_book_cron SET status=2, updated_at="'+current_time()+'" WHERE id="'+cm_book_cron_id+'"';
    con.query(sql, function (err, result) {
        console.log('---- done');
        process.exit();
    });
}

function check_book(cron) {
    console.log('url = '+cron.book_url);
    var sql = 'SELECT * FROM dl_books WHERE url="'+cron.book_url+'"';
    con.query(sql, function (err, result) {
        if(result.length == 0) {
            console.log('Tao moi book');
            create_book(cron.book_url);
        } else {
            var book = result[0];
            if(book.status == 0) {
                console.log('Book dang bi disable');
                finish_book_cron();
            }
            console.log('Cap nhat book');
            cm_book_id = book.id;
            total_author = 0;
            total_tag = 0;
            update_book_without_dom(book);
        }
    });
}

function parse_book(url, body) {
    var dom = parser.parseFromString(body);
    if(dom == null || typeof(dom) == 'undefined') {
        create_book(url);
    } else {
        if(is_error_page(dom)) {
            finish_book_cron();
        } else {
            var nodes = dom.getElementsByClassName('title-detail');
            if (typeof(nodes) == 'undefined' || nodes == null || nodes.length == 0) {
                create_book(url);
            } else {
                var sql = "INSERT INTO dl_books (server_id, url, name, image_source, description, created_at, updated_at, release_date)";
                sql += " VALUES (";
                sql += '"1","' + url + '"';
                var name = nodes[0].innerHTML.trim();
                sql += ',"' + addslashes(name) + '"';

                var nodes = dom.getElementsByClassName('col-xs-4 col-image');
                var image_str = nodes[0].innerHTML.trim();

                var image = get_image_url(image_str);
                sql += ',"' + image + '"';

                var nodes = dom.getElementsByClassName('detail-content');
                var description = nodes[0].getElementsByTagName('p')[0].innerHTML.trim();
                sql += ',"' + addslashes(description) + '"';

                sql += ',"' + current_time() + '"';
                sql += ',"' + current_time() + '"';
                sql += ',"' + current_time() + '"';

                sql += ")";
                con.query(sql, function (err, result) {
                    var sql = 'SELECT * FROM dl_books WHERE url="' + url + '"';
                    con.query(sql, function (err, result) {
                        if (result.length == 0) {
                            console.log('Tao moi book khong thanh cong');
                            finish_book_cron();
                        } else {
                            console.log('Tao moi book thanh cong');
                            console.log('Cap nhat book');
                            var book = result[0];
                            cm_book_id = book.id;
                            update_book(book, dom);
                            update_status_book(book, dom);
                            update_tags_book(book, dom);
                            update_authors_book(book, dom);
                        }
                    });
                });
            }
        }
    }
}

function create_book(url) {
    var proxiedRequest = get_request();
    proxiedRequest.get( url, function(error, response, body){
        if (error) {
            console.log('Khong the lay html tu book url. Thu lai..');
            create_book(url);
        } else {
            parse_book(url, body);
        }
    });
}

function parse_existed_book(book, body) {
    var dom = parser.parseFromString(body);
    if(dom == null || typeof(dom) == 'undefined') {
        update_book_without_dom(book);
    } else {
        if(is_error_page(dom)) {
            finish_book_cron();
        } else {
            update_book(book, dom);
        }
    }
}

function update_book_without_dom(book) {
    var proxiedRequest = get_request();
    proxiedRequest.get( book.url, function(error, response, body){
        if (error) {
            console.log('Khong the lay html tu book url. Thu lai...');
            update_book_without_dom(book);
        } else {
            parse_existed_book(book, body)
        }
    });
}

function update_status_book(book, dom) {
    var nodes = dom.getElementsByClassName('status row');
    var str = nodes[0].innerHTML.trim().toLowerCase();
    if(str.indexOf('hoàn thành') > -1) {
        if(total_tag == -1) {
            total_tag = 0;
        }
        total_tag += 1;
        check_tag(book, 'Hoàn thành', 'hoan-thanh', 0);
    } else {
        check_done();
    }
}
function update_tags_book(book, dom) {
    var nodes = dom.getElementsByClassName('kind row');
    var tags = nodes[0].getElementsByTagName('a');
    if(total_tag == -1) {
        total_tag = 0;
    }
    if(typeof(tags) == 'undefined' || tags.length == 0) {
        check_done();
        return false;
    }
    total_tag += tags.length;
    for(var i=0;i<tags.length;i++) {
        var tag_name = ucfirst(tags[i].innerHTML.trim().toLowerCase());
        var tag_slug = generate_slug(tag_name);
        if(tag_slug == 'chua-cap-nhat' || tag_slug == 'dang-cap-nhat') {
            count_tag++;
            check_done();
            continue;
        }
        check_tag(book, tag_name, tag_slug, 0);
    }
}
function check_tag(book, tag_name, tag_slug, type) {
    var sql = 'SELECT * FROM dl_tags WHERE type='+type+' AND slug="'+tag_slug+'"';
    con.query(sql, function (err, result) {
        if(result.length == 0) {
            create_tag(book, tag_name, tag_slug, type);
        } else {
            var tag = result[0];
            set_book_tag(book.id, tag.id, type);
        }
    });
}
function update_authors_book(book, dom) {
    var nodes = dom.getElementsByClassName('author row');
    var tags = nodes[0].getElementsByTagName('a');
    total_author = 0;
    if(typeof(tags) == 'undefined' || tags.length == 0) {
        check_done();
        return false;
    }
    total_author = tags.length;
    for(var i=0;i<tags.length;i++) {
        var tag_name = ucfirst(tags[i].innerHTML.trim().toLowerCase());
        var tag_slug = generate_slug(tag_name);
        if(tag_slug == 'chua-cap-nhat' || tag_slug == 'dang-cap-nhat') {
            count_author++;
            check_done();
            continue;
        }
        var tag_parser = tag_name.split(' ');
        tag_name = '';
        for(var j=0;j<tag_parser.length;j++) {
            tag_name += ucfirst(tag_parser[j])+' ';
        }
        check_tag(book, tag_name.trim(), tag_slug, 1);
    }
}
function create_tag(book, tag_name, tag_slug, type) {
    var sql = "INSERT INTO dl_tags (name, slug, type, status, updated_at, created_at)";
    sql += ' VALUES ("'+tag_name+'","'+tag_slug+'", '+type+', 1, "'+current_time()+'", "'+current_time()+'")';
    con.query(sql, function (err, result) {
        var sql = 'SELECT * FROM dl_tags WHERE type='+type+' AND slug="'+tag_slug+'"';
        con.query(sql, function (err, result) {
            if(result.length == 0) {
                console.log('Tao moi tag that bai '+tag_name);
                return false;
            } else {
                console.log('Tao moi tag thanh cong '+tag_name);
                var tag = result[0];
                set_book_tag(book.id, tag.id, type);
            }
        });
    });
}

function set_book_tag(book_id, tag_id, type) {
    var sql = "INSERT INTO dl_book_tag (book_id, tag_id, updated_at, created_at)";
    sql += ' VALUES ("'+book_id+'","'+tag_id+'", "'+current_time()+'", "'+current_time()+'")';
    con.query(sql, function (err, result) {
        if(type == 1) {
            count_author++;
        }else{
            count_tag++;
        }
        check_done();
    });
}
function check_done() {
    if(total_image == count_image
        && total_author == count_author
        && total_tag == count_tag
        && count_skip_chap+count_clone_chap==total_chap) {
        clear_cache();
    }
}
function get_image_url(image_str) {
    image_str = image_str.replace('src="//','src="http://').replace('original="//','original="http://');
    image_str = image_str.replace('src="https://','src="http://').replace('original="https://','original="http://');
    if(image_str.indexOf('original="http://') > -1) {
        var image_parser = image_str.split('original="');
        image_str = image_parser[1];
        image_parser = image_str.split('"');

        var img = image_parser[0];
        if(img.indexOf('=http') > -1) {
            image_parser = img.split('=http');
            img = decodeURIComponent('http'+image_parser[1]);
        }
        return img;
    }
    if(image_str.indexOf('src="http://') > -1) {
        var image_parser = image_str.split('src="');
        image_str = image_parser[1];
        image_parser = image_str.split('"');

        var img = image_parser[0];
        if(img.indexOf('=http') > -1) {
            image_parser = img.split('=http');
            img = decodeURIComponent('http'+image_parser[1]);
        }
        return img;
    }
    return '';
}
function update_book(book, dom) {
    var nodes = dom.getElementsByClassName('col-xs-5 chapter');
    total_chap = nodes.length;
    for(var i=0;i<nodes.length;i++) {
        var chap = nodes[i].innerHTML;

        var parse_str = chap.split('>');
        var chap_name = parse_str[1];
        parse_str = chap_name.split('<');
        chap_name = parse_str[0].trim().toLowerCase();
        chap_name = chap_name.replace('chuong','chương').replace('chapter','chương').replace('chap','chương');
        if(chap_name.indexOf('raw') > -1) {
            count_skip_chap++;
            continue;
        }
        chap_name = ucfirst(chap_name);
        if(chap_name.substring(chap_name.length-7) == ': video') {
            chap_name = chap_name.substring(0, chap_name.length-7);
        }

        var parse_str = chap.split('href="');
        var href = parse_str[1];
        parse_str = href.split('"');
        href = parse_str[0].trim();
        if(href.indexOf('http') === -1) {
            href = 'http://nettruyen.com' + href;
        }
        href = href.replace('https:','http:');

        var stt = nodes.length - i;
        check_chap(book, chap_name, href, stt);
    }
    if(count_skip_chap == total_chap) {
        total_image = 0;
        check_done();
    }
}
function check_chap(book, chap_name, href, stt) {
    var sql = 'SELECT * FROM dl_chapters WHERE book_id="'+book.id+'" AND url="'+href+'"';
    con.query(sql, function (err, result) {
        if(result.length == 0) {
            console.log('Create '+ucfirst(chap_name));
            create_chap(book, chap_name, href, stt);
        } else {
            if(result[0].stt != stt) {
                update_stt(result[0].id, stt);
            } else {
                count_skip_chap++;
                if (count_skip_chap == total_chap) {
                    total_image = 0;
                    check_done();
                }
            }
        }
    });
}
function update_stt(chapter_id, stt) {
    var sql = 'UPDATE dl_chapters SET stt='+stt+', updated_at="'+current_time()+'" WHERE id="'+chapter_id+'"';
    con.query(sql, function (err, result) {
        count_skip_chap++;
        if (count_skip_chap == total_chap) {
            total_image = 0;
            check_done();
        }
    });
}
function create_chap(book, chap_name, href, stt) {
    var sql = "INSERT INTO dl_chapters (book_id, url, name, stt, updated_at, created_at)";
    sql += " VALUES (";
    sql += '"'+book.id+'","' + href + '","' + addslashes(chap_name) + '","' + stt + '","' + current_time() + '","' + current_time() + '"';
    sql += ")";
    con.query(sql, function (err, result) {
        var sql = 'SELECT * FROM dl_chapters WHERE book_id="'+book.id+'" AND url="'+href+'"';
        con.query(sql, function (err, result) {
            if(result.length > 0) {
                clone_chap(result[0], true);
            }
        });
    });
}
var count_clone_chap = 0;
function get_request() {
    if(use_proxy) {
        var count_proxies = proxies.length;
        var ind = Math.floor(Math.random() * count_proxies);
        var proxy_url = "http://galvin24x7:egor99@" +proxies[ind];
        return request.defaults({'proxy': proxy_url})
    }
    return request;
}
function parse_chap(chap, body) {
    var dom = parser.parseFromString(body);
    if(dom == null || typeof(dom) == 'undefined') {
        clone_chap(chap, false);
    } else {
        if(is_error_page(dom)) {
            check_done();
        } else {
            var nodes = dom.getElementsByClassName('page-chapter');
            if(nodes.length == 0) {
                clone_chap(chap, false);
            } else {
                if (total_image == -1) {
                    total_image = 0;
                }
                total_image += nodes.length;
                for (var i = 0; i < nodes.length; i++) {
                    var image_str = nodes[i].innerHTML.trim();
                    var image = get_image_url(image_str);
                    if (image == '') {
                        count_image++;
                    } else {
                        var stt = i + 1;
                        create_image(chap, image, stt);
                    }
                }
            }
        }
    }
}
function clone_chap(chap, inc_count_chap) {
    var proxiedRequest = get_request();
    proxiedRequest.get( chap.url, function(error, response, body){
        if (error){
            console.log('Can not get html '+ chap.url +'. Try again....');
            clone_chap(chap, inc_count_chap);
        } else {
            if(inc_count_chap) {
                count_clone_chap++;
                console.log('count clone chap = '+ count_clone_chap);
            }
            parse_chap(chap, body);
        }
    });
}
function create_image(chap, image, stt) {
    var sql = "INSERT INTO dl_images (chapter_id, image_source, image, stt, status, updated_at, created_at)";
    sql += " VALUES (";
    sql += '"'+chap.id+'","' + image + '","error.jpg","' + stt + '",1,"' + current_time() + '","' + current_time() + '"';
    sql += ")";
    con.query(sql, function (err, result) {
        count_image++;
        check_done();
    });
}
function clear_cache() {
    if(count_skip_chap == total_chap) {
        finish_book_cron();
    } else {
        console.log(command_exit + "" + cm_book_id);
        exec(command_exit + "" + cm_book_id, function (err, stdout, stderr) {
            console.log('---- Done');
            process.exit();
        });
    }
}
function ucfirst(txt) {
    return txt.substr(0,1).toUpperCase()+txt.substr(1);
}

function current_time() {
    var di = new Date();
    var utc = di.getTime() + (di.getTimezoneOffset() * 60000);

    var currentdate = new Date(utc + (3600000*7));
    var y = currentdate.getFullYear();
    var mon = currentdate.getMonth()+1;
    if(mon < 10) {
        mon = '0' + mon;
    }
    var d = currentdate.getDate();
    if(d < 10) {
        d = '0' + d;
    }
    var h = currentdate.getHours();
    if(h < 10) {
        h = '0' + h;
    }
    var min = currentdate.getMinutes();
    if(min < 10) {
        min = '0' + min;
    }
    var s = currentdate.getSeconds();
    if(s < 10) {
        s = '0' + s;
    }
    return y + '-' + mon + "-" + d + " " + h + ":" + min + ":" + s;
}
function addslashes(string) {
    return string.replace(/\\/g, '\\\\').
    replace(/\u0008/g, '\\b').
    replace(/\t/g, '\\t').
    replace(/\n/g, '\\n').
    replace(/\f/g, '\\f').
    replace(/\r/g, '\\r').
    replace(/'/g, '\\\'').
    replace(/"/g, '\\"');
}

function generate_slug(str) {
    str = str.replace(/^\s+|\s+$/g, ''); // trim
    str = str.toLowerCase();
    var from = "àáãạảăằắẵặẳâầấậẫẩđèéẹẽẻêềếễệểìíịĩỉòóọõỏôồốỗộổơờỡớợởùúụủũưừứựữửỳýỵỹỷ";
    var to   = "aaaaaaaaaaaaaaaaadeeeeeeeeeeeiiiiiooooooooooooooooouuuuuuuuuuuyyyyy";
    for (var i=0; i<from.length ; i++) {
        str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }
    return str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
        .replace(/\s+/g, '-') // collapse whitespace and replace by -
        .replace(/-+/g, '-'); // collapse dashes
}

function is_error_page(dom) {
    var nodes = dom.getElementsByClassName('error-title');
    if(nodes.length > 0) {
        return true;
    }
    return false;
}