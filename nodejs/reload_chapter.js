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
var cm_book_id = 0;
var total_chapter = -1;
var count_chapter = 0;

con.connect(function(err) {
    console.log("Da ket noi database MySQL");
});

var point_count_clone_chap = -1;
var dem_delay = 0;
var interval = setInterval(function() {
    if(cm_book_id > 0) {
        if (point_count_clone_chap < count_chapter) {
            point_count_clone_chap = count_chapter;
        } else {
            console.log('cloned chap = ' + count_chapter + ' / ' + total_chapter);
            clearInterval(interval);
            exec(command_exit + "" + cm_book_id, function (err, stdout, stderr) {
                var sql = 'UPDATE dl_settings SET value="", updated_at="' + current_time() + '" WHERE name="reloading"';
                con.query(sql, function (err, result) {
                    console.log('---- Done');
                    process.exit();
                });
            });
        }
    } else {
        if(dem_delay > 3) {
            var sql = 'UPDATE dl_settings SET value="", updated_at="' + current_time() + '" WHERE name="reloading"';
            con.query(sql, function (err, result) {
                console.log('---- Done');
                process.exit();
            });
        } else {
            dem_delay++;
        }
    }
}, 60000);

var sql = 'SELECT * FROM dl_settings WHERE name="reloading" LIMIT 1';
con.query(sql, function (err, result) {
    if (result.length > 0) {
        if(result[0].value=='') {
            var sql = 'UPDATE dl_settings SET value="yes", updated_at="' + current_time() + '" WHERE name="reloading"';
            con.query(sql, function (err, result) {
                get_chapter_reload();
            });
        } else {
            console.log('----- reloading');
            process.exit();
        }
    } else {
        var sql = "INSERT INTO dl_settings (name, value, created_at, updated_at)";
        sql += " VALUES ('reloading','yes','"+current_time()+"','"+current_time()+"')";
        con.query(sql, function (err, result) {
            get_chapter_reload();
        });
    }
});

function get_chapter_reload() {
    var sql = 'SELECT * FROM dl_chapters WHERE will_reload=1 LIMIT 1';
    con.query(sql, function (err, result) {
        if (result.length > 0) {
            cm_book_id = result[0].book_id;
            get_list_chapter_reload(result[0]);
        } else {
            var sql = 'UPDATE dl_settings SET value="", updated_at="' + current_time() + '" WHERE name="reloading"';
            con.query(sql, function (err, result) {
                console.log('Khong co chapter reload');
                console.log('---- Done');
                process.exit();
            });
        }
    });
}
function get_list_chapter_reload(chapter) {
    var sql = 'SELECT * FROM dl_chapters WHERE will_reload=1 AND book_id='+chapter.book_id;
    con.query(sql, function (err, result) {
        if (result.length > 0) {
            chapters = result;
            total_chapter = result.length;
            for(var i=0;i<result.length;i++) {
                console.log('Reload chapter ' + result[i].url);
                update_chapter(result[i]);
            }
        } else {
            var sql = 'UPDATE dl_settings SET value="", updated_at="' + current_time() + '" WHERE name="reloading"';
            con.query(sql, function (err, result) {
                console.log('Khong co chapter reload');
                console.log('---- Done');
                process.exit();
            });
        }
    });
}

function update_chapter(chapter) {
    var sql = 'UPDATE dl_chapters SET will_reload=0,status=0, updated_at="' + current_time() + '" WHERE id="' + chapter.id + '"';
    con.query(sql, function (err, result) {
        delete_images(chapter);
    });
}

function delete_images(chapter) {
    var sql = 'Delete FROM dl_images WHERE chapter_id="'+chapter.id+'"';
    con.query(sql, function (err, result) {
        clone_chap(chapter);
    });
}

function clone_chap(chap) {
    var proxiedRequest = get_request();
    proxiedRequest.get( chap.url, function(error, response, body){
        if (error){
            console.log('Khong lay duoc html '+chap.url+'. Thu lai...');
            clone_chap(chap);
        } else {
            parse_chapter(chap, body);
        }
    });
}
function parse_chapter(chap, body) {
    var dom = parser.parseFromString(body);
    if(dom == null || typeof(dom) == 'undefined') {
        clone_chap(chap);
    } else {
        if(is_error_page(dom)) {
            count_chapter++;
        } else {
            var nodes = dom.getElementsByClassName('page-chapter');
            if (nodes.length == 0) {
                clone_chap(chap);
            } else {
                chap.total_image = nodes.length;
                chap.count_image = 0;
                for (var i = 0; i < nodes.length; i++) {
                    var image_str = nodes[i].innerHTML.trim();
                    var image = get_image_url(image_str);
                    if (image == '') {
                        chap.count_image++;
                    } else {
                        var stt = i + 1;
                        console.log('create image ' + stt);
                        create_image(chap, image, stt);
                    }
                }
            }
        }
    }
}
function get_request() {
    if(use_proxy) {
        var count_proxies = proxies.length;
        var ind = Math.floor(Math.random() * count_proxies);
        var proxy_url = "http://galvin24x7:egor99@" +proxies[ind];
        return request.defaults({'proxy': proxy_url})
    }
    return request;
}
function create_image(chap, image, stt) {
    var sql = "INSERT INTO dl_images (chapter_id, image_source, image, stt, status, updated_at, created_at)";
    sql += " VALUES (";
    sql += '"'+chap.id+'","' + image + '","error.jpg","' + stt + '",1,"' + current_time() + '","' + current_time() + '"';
    sql += ")";
    con.query(sql, function (err, result) {
        chap.count_image++;
        if(chap.count_image == chap.total_image) {
            count_chapter++;
            if(total_chapter == count_chapter) {
                console.log(command_exit + "" + cm_book_id);
                exec(command_exit + "" + cm_book_id, function (err, stdout, stderr) {
                    var sql = 'UPDATE dl_settings SET value="", updated_at="' + current_time() + '" WHERE name="reloading"';
                    con.query(sql, function (err, result) {
                        console.log('---- Done');
                        process.exit();
                    });
                });
            }
        }
    });
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
function get_image_url(image_str) {
    image_str = image_str.replace('src="//','src="http://').replace('original="//','original="http://');
    if(image_str.indexOf('original="') > -1) {
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
    if(image_str.indexOf('src="') > -1) {
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

function is_error_page(dom) {
    var nodes = dom.getElementsByClassName('error-title');
    if(nodes.length > 0) {
        return true;
    }
    return false;
}