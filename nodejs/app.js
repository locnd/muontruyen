var request = require("request");
var mysql = require('mysql');
var DomParser = require('dom-parser');
var parser = new DomParser();

var con = mysql.createConnection({
    host: "localhost",
    user: "root",
    password: "l!o@c#n$g%u^y&e*n",
    database: "muontruyen"
});
var server_url = 'http://muontruyen.tk';

var cm_book_id = 0;
var cm_book_cron_id = 0;

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

var sql = 'SELECT * FROM dl_book_cron WHERE status=0 LIMIT 1';
con.query(sql, function (err, result) {
    if(result.length > 0) {
        var book_cron = result[0];
        cm_book_cron_id = book_cron.id;
        var sql = 'UPDATE dl_book_cron SET status=1, updated_at="'+current_time()+'" WHERE id="'+book_cron.id+'"';
        con.query(sql, function (err, result) {
            check_book(book_cron);
        });
    } else {
        console.log('Khong co book cron');
        process.exit();
    }
});

function finish_book_cron() {
    var sql = 'UPDATE dl_book_cron SET status=2, updated_at="'+current_time()+'" WHERE id="'+cm_book_cron_id+'"';
    con.query(sql, function (err, result) {
        console.log('---- done');
        process.exit();
    });
}

function check_book(cron) {
    console.log('Kiem tra url '+cron.book_url);
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
            if(book.will_reload == 1) {
                console.log('Book dang duoc reload');
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

function create_book(url) {
    request.get( url, function(error, response, body){
        if (error) {
            console.log('Khong the lay html tu book url');
            finish_book_cron();
        }
        var sql = "INSERT INTO dl_books (server_id, url, name, image_source, description, created_at, updated_at, release_date)";
        sql += " VALUES (";
        sql += '"1","'+url+'"';
        var dom = parser.parseFromString(body);

        var nodes = dom.getElementsByClassName('title-detail');
        var name = nodes[0].innerHTML.trim();
        sql += ',"'+addslashes(name)+'"';

        var nodes = dom.getElementsByClassName('col-xs-4 col-image');
        var image_str = nodes[0].innerHTML.trim();
        image_str = image_str.replace('src="//','src="http://');
        image_str = image_str.replace('https:','http:');
        var myRegex = /<img[^>]+src="(http:\/\/[^">]+)"/g;
        var image_parse = myRegex.exec(image_str);
        var image = '';
        if(typeof(image_parse[1]) != 'undefined') {
            image = image_parse[1];
        }
        sql += ',"'+image+'"';

        var nodes = dom.getElementsByClassName('detail-content');
        var description = nodes[0].getElementsByTagName('p')[0].innerHTML.trim();
        sql += ',"'+addslashes(description)+'"';

        sql += ',"'+current_time()+'"';
        sql += ',"'+current_time()+'"';
        sql += ',"'+current_time()+'"';

        sql += ")";
        con.query(sql, function (err, result) {
            var sql = 'SELECT * FROM dl_books WHERE url="'+url+'"';
            con.query(sql, function (err, result) {
                if(result.length == 0) {
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
    });
}

function update_book_without_dom(book) {
    request.get( book.url, function(error, response, body){
        if (error) {
            console.log('Khong the lay html tu book url');
            finish_book_cron();
        }
        var dom = parser.parseFromString(body);
        update_book(book, dom);
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
    if(total_image == count_image && total_author == count_author && total_tag == count_tag) {
        clear_cache(cm_book_id);
    }
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
            count_skip_chap++;
            if(count_skip_chap == total_chap) {
                total_image = 0;
                check_done();
            }
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
                clone_chap(result[0]);
            }
        });
    });
}
function clone_chap(chap) {
    request.get( chap.url, function(error, response, body){
        if (error){
            console.log('Khong the lay html tu chapter url');
            return false;
        }
        var dom = parser.parseFromString(body);

        var nodes = dom.getElementsByClassName('page-chapter');
        if(total_image == -1) {
            total_image = 0;
        }
        total_image+=nodes.length;
        for(var i=0; i<nodes.length;i++) {
            var image_str = nodes[i].innerHTML;
            image_str = image_str.replace('https:','http:').trim();
            var myRegex = /<img[^>]+src="(http:\/\/[^">]+)"/g;
            var image = myRegex.exec(image_str)[1];
            var stt = i+1;
            create_image(chap, image, stt);
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
        if(count_image == total_image) {
            check_done();
        }
    });
}
function clear_cache(book_id) {
    if(count_skip_chap == total_chap) {
        finish_book_cron();
    } else {
        var url = server_url + "/api/v1/clearcache?token=l2o4c0n7g1u9y8e8n&book_id=" + book_id;
        console.log(url);
        request.get(url, function (error, response, body) {
            if (error) {
                console.log('Khong the lay html tu clear cache url');
                return false;
            }
            console.log(body);
            console.log('---- done');
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