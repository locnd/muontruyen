$(document).ready(function () {
    App.init();
    if ($('.data-table').length > 0) {
        $('.data-table').DataTable({
            responsive: true,
            paging:   false,
            ordering: false,
            info:     false,
            searching:   false
        });
    }
    $('.input-datepicker').datepicker({
        dateFormat: 'dd-mm-yy'
    });
    $('.dl-input-calendar').click(function() {
        $(this).parent().find('.input-datepicker').focus();
    });
    
    $('#input_profile_image').change(function() {
        $('#avatar_error').text('');
        uploadImage(this, 'input_profile_image', 'avatar_error', 'show_profile_image','/ajax/user/uploadAvatar');
    });
    $('#input_shop_image').change(function() {
        $('#image_error').text('');
        uploadImage(this, 'input_shop_image', 'image_error', 'show_shop_image','/ajax/shop/uploadImage');
    });
    $('#input_product_image').change(function() {
        $('#image_error').text('');
        uploadImage(this, 'input_product_image', 'image_error', 'show_product_image','/ajax/productItem/uploadImage');
    });
    $('#input_image').change(function() {
        $('#image_error').text('');
        upload_attribute_image(this, 'input_image', 'image_error', '/ajax/productItem/uploadAttributeImage');
    });
    
    if($('.combobox').length > 0) {
        $('.combobox').combobox();
    }
    $('#input_theme').change(function() {
        var theme = $(this).val();
        $('#preview_theme').attr('src', '/uploads/theme/'+theme+'.png');
    });
    
    var document_width = $(window).width() - 100;
    var document_height = $(window).height() - 100;
    $('#preview_image_modal').find('.image-fullsize').attr('style','max-width:'+document_width+'px;max-height:'+document_height+'px;');

    $('#input_shop_id').change(function() {
        $('#input_product_category_id').html('<option value="">'+l('Please select')+'</option>');
        var shop_id = $('#input_shop_id').val();
        if(shop_id !== '') {
            $.ajax({
                url: '/ajax/common/getProductCategories',
                type: 'get',
                data: {
                    shop_id: shop_id
                },
                dataType: 'json',
                success: function (result) {
                    if(result.success) {
                        var category_html = '<option value="">'+l('Please select')+'</option>';
                        for(var i=0; i< result.data.length;i++) {
                            var cat = result.data[i];
                            category_html += '<option value="'+cat.id+'">'+cat.name+'</option>';
                        }
                        $('#input_product_category_id').html(category_html);
                    }
                },
                error: function (xhr) {
                    ajax_error();
                }
            });
        }
    });
    $('#mess_content').keyup(function (e) {
        var content = $('#mess_content').html();
        var ban_dau = content;
        for (var ind in smileys) {
            var st = '<img src="/assets/images/smileys/'+smileys[ind]+'">';
            var point = content.indexOf(st);
            while (point > -1) {
                content = content.replace(st, ind);
                point = content.indexOf(st);
            }
        }
        for (var ind in smileys) {
            var point = content.indexOf(ind);
            while (point > -1) {
                content = content.replace(ind, '<img src="/assets/images/smileys/'+smileys[ind]+'">');
                point = content.indexOf(ind);
            }
        }
        if(ban_dau != content) {
            $('#mess_content').html(content);
            placeCaretAtEnd($('#mess_content').get(0));
        }
    });
    $('#mess_content').keydown(function (e) {
        if (e.which == 13 && !e.shiftKey){
            e.stopPropagation();
            e.preventDefault();
            send_message();
        }
    });
    $('.scroll_style').mouseover(function() {
        $(this).css('overflow-y', 'scroll');
        $(this).css('padding-right', '0px');
    });
    $('.scroll_style').mouseout(function() {
        $(this).css('overflow-y', 'hidden');
        $(this).css('padding-right', '1px');
    });
    
    $(".mess-detail-list .mess-area").scroll(function() {
        var pos = $(".mess-detail-list .mess-area").scrollTop();
        if (pos < 10) {
            load_more_old();
        }
    });
});
function placeCaretAtEnd(el) {
    el.focus();
    if (typeof window.getSelection != "undefined"
            && typeof document.createRange != "undefined") {
        var range = document.createRange();
        range.selectNodeContents(el);
        range.collapse(false);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    } else if (typeof document.body.createTextRange != "undefined") {
        var textRange = document.body.createTextRange();
        textRange.moveToElementText(el);
        textRange.collapse(false);
        textRange.select();
    }
}
function change_language(lang) {
    $.ajax({
        url: '/ajax/common/language',
        type: 'post',
        data: {
            language: lang
        },
        dataType: 'json',
        success: function (result) {
            save_form_success(result, []);
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}
function delete_attribute_image(id) {
    var confirm_text = l('confirm_delete');
    var cancel_text = l('Skip');
    var submit_text = l('Submit');
    confirm_text += ' '+ l('this image')+' ?';
    swal({
        title: '',
        text: confirm_text,
        type: "warning",
        showCancelButton: true,
        cancelButtonText: cancel_text,
        confirmButtonText: submit_text
    }, function () {
        $.ajax({
            url: '/ajax/productItem/deleteAttributeImage',
            type: 'post',
            data: {
                id: id
            },
            dataType: 'json',
            success: function (result) {
                $('#image_'+id).remove();
                save_form_success(result, []);
            },
            error: function (xhr) {
                ajax_error();
            }
        });
    });
}
function upload_attribute_image(input, input_id, error_id, url) {
    if (input.files && input.files[0]) {
        if(!validation_image(input_id, error_id)) {
            return false;
        }
        var data = new FormData();
        data.append('image', $('#'+input_id)[0].files[0]);
        $.ajax({
            url: url,
            type: "POST",
            data: data,
            enctype: 'multipart/form-data',
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(result) {
            $('#'+input_id).val('');
            save_form_success(result, {image: 'image_error'});
            var image_html = '';
            image_html+='<div id="image_'+result.data.id+'" class="preview-product-image profile-image">';
                image_html+='<img onclick="preview_this(this)" class="show_profile_image" src="/uploads/attribute/small/'+result.data.image+'">';
                image_html+='<i title="'+l('Delete')+'" onclick="delete_attribute_image('+result.data.id+');" class="fa fa-times"></i>';
                image_html+='<input name="product_images[]" value="'+result.data.id+'">';
            image_html+='</div>';
            $('#image-area').append(image_html);
        });
    }
    return true;
}
function validation_image(input_id, error_id) {
    var imgname  =  $('#'+input_id).val();
    var ext = imgname.substr((imgname.lastIndexOf('.') +1));
    var accept_ext = ['jpg','jpeg','png','gif'];
    if($.inArray(ext.toLowerCase(),accept_ext) === -1) {
        $('#'+error_id).text(l('File type not accept'));
        $('#'+input_id).val('');
        return false;
    }
    var size  =  $('#'+input_id)[0].files[0].size;
    if(size > 2*1024*1024) {
        $('#'+error_id).text(l('File size need to be less than 2MB'));
        $('#'+input_id).val('');
        return false;
    }
    return true;
}
function uploadImage(input, input_id, error_id, show_id, url) {
    if (input.files && input.files[0]) {
        if(!validation_image(input_id, error_id)) {
            return false;
        }
        
        var reader = new FileReader();
        reader.onload = function (e) {
            $('#'+show_id).attr('src', e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
        
        var data = new FormData();
        if($('#user_id').length > 0) {
            data.append('avatar', $('#'+input_id)[0].files[0]);
            data.append('user_id', $('#user_id').val());
        }
        if($('#shop_id').length > 0) {
            data.append('image', $('#'+input_id)[0].files[0]);
            data.append('shop_id', $('#shop_id').val());
        }
        if($('#product_id').length > 0) {
            data.append('image', $('#'+input_id)[0].files[0]);
            data.append('product_id', $('#product_id').val());
        }
        $.ajax({
            url: url,
            type: "POST",
            data: data,
            enctype: 'multipart/form-data',
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(result) {
            $('#'+input_id).val('');
            save_form_success(result, {avatar: 'avatar_error', image: 'image_error'});
        });
    }
    return true;
}

function delete_item(type, id) {
    var confirm_text = l('confirm_delete');
    var cancel_text = l('Skip');
    var submit_text = l('Submit');
    if($('#item-'+id).find('.item-type').length > 0) {
       confirm_text += ' '+ $('#item-'+id).find('.item-type').text().toLowerCase();
    }
    confirm_text += ' "'+ $('#item-'+id).find('.item-name').text()+'" ?';
    swal({
        title: '',
        text: confirm_text,
        type: "warning",
        showCancelButton: true,
        cancelButtonText: cancel_text,
        confirmButtonText: submit_text
    }, function () {
        $.ajax({
            url: '/ajax/'+type+'/delete',
            type: 'post',
            data: {
                id: id
            },
            dataType: 'json',
            success: function (result) {
                save_form_success(result, []);
            },
            error: function (xhr) {
                ajax_error();
            }
        });
    });
}
function restore_item(type, id) {
    var confirm_text = l('confirm_restore');
    var cancel_text = l('Skip');
    var submit_text = l('Submit');
    if($('#item-'+id).find('.item-type').length > 0) {
       confirm_text += ' '+ $('#item-'+id).find('.item-type').text().toLowerCase();
    }
    confirm_text += ' "'+ $('#item-'+id).find('.item-name').text()+'" ?';
    swal({
        title: '',
        text: confirm_text,
        type: "warning",
        showCancelButton: true,
        cancelButtonText: cancel_text,
        confirmButtonText: submit_text
    }, function () {
        $.ajax({
            url: '/ajax/'+type+'/restore',
            type: 'post',
            data: {
                id: id
            },
            dataType: 'json',
            success: function (result) {
                save_form_success(result, []);
            },
            error: function (xhr) {
                ajax_error();
            }
        });
    });
}
function save_form_success(result, errors_map) {
    if(result.reload) {
        window.location.reload();
    } else {
        if(!result.success) {
            $.each(result.messages, function( key, value ) {
                var error_id = errors_map[key];
                $('#'+error_id).text(l(value));
                $('#'+error_id).show();
                if(key == 'error') {
                    var title = l('Error');
                    swal({
                        title: title,
                        text: value,
                        type: "error"
                    }, function () {
                        window.location.reload();
                    });
                }
            });
        }
    }
}
function ajax_error() {
    var title = l('Error');
    var text = l('Please contact to administrator');
    swal({
        title: title,
        text: text,
        type: "error"
    }, function () {
        window.location.reload();
    });
}

function open_form_modal(id) {
    $('#form_modal .validation-error').hide();
    $('#form_modal #item_id').val(id);
    if($('#form_modal #item_name').length > 0) {
        $('#form_modal #item_name').val('');
    }
    if($('#form_modal #item_slug').length > 0) {
        $('#form_modal #item_slug').val('');
    }
    if($('#form_modal #item_status').length > 0) {
        $('#form_modal #item_status').val('');
    }
    if($('#form_modal #item_description').length > 0) {
        $('#form_modal #item_description').html('');
    }
    if($('#form_modal #item_shop').length > 0) {
        if($('#form_modal #item_shop').val() != '') {
            $('#form_modal').find('.glyphicon.glyphicon-remove').trigger('click');
        }
    }
    if($('#form_modal #item_main_category').length > 0) {
        $('#form_modal #item_main_category').val('');
    }
    if($('#form_modal #item_sale_off').length > 0) {
        $('#form_modal #item_sale_off').val('');
    }
    if($('#form_modal #item_sale_off_type').length > 0) {
        $('#form_modal #item_sale_off_type').val('%');
    }
    if($('#form_modal #item_start_date').length > 0) {
        $('#form_modal #item_start_date').val('');
    }
    if($('#form_modal #item_end_date').length > 0) {
        $('#form_modal #item_end_date').val('');
    }
    if($('#form_modal #item_product_category_id').length > 0) {
        $('#form_modal #item_product_category_id').html('');
    }
    if($('#form_modal #item_product_item_id').length > 0) {
        $('#form_modal #item_product_item_id').html('');
    }
    if(id == 0) {
        $('#form_modal h3').text(l('Add'));
        $('#form_modal h3.modal-title').text(l('Add'));
    } else {
        $('#form_modal h3').text(l('Edit'));
        $('#form_modal h3.modal-title').text(l('Edit'));
        if($('#form_modal #item_name').length > 0) {
            var name = $('#item-'+id+' .item-name').text();
            $('#form_modal #item_name').val(name);
            $('#form_modal h3.modal-title').text(l('Edit')+' '+name);
        }
        if($('#form_modal #item_slug').length > 0) {
            var slug = $('#item-'+id+' .item-slug').text();
            $('#form_modal #item_slug').val(slug);
        }
        if($('#form_modal #item_status').length > 0) {
            var status = $('#item-'+id).attr('data-status');
            $('#form_modal #item_status').val(status);
        }
        if($('#form_modal #item_description').length > 0) {
            var description = $('#item-'+id+' .item-description').html();
            $('#form_modal #item_description').html(description);
        }
        if($('#form_modal #item_shop').length > 0) {
            var shop_id = $('#item-'+id).attr('data-shop');
            $('#form_modal #item_shop').val(shop_id);
            var category_id = 0;
            var product_id = 0;
            if($('#form_modal #item_product_category_id').length > 0) {
                category_id = $('#item-'+id).attr('data-product_category');
            }
            if($('#form_modal #item_product_item_id').length > 0) {
                product_id = $('#item-'+id).attr('data-product_item');
            }
            load_data_of_shop(category_id, product_id);
            $('#form_modal #item_shop.combobox').combobox('refresh', true);
        }
        if($('#form_modal #item_main_category').length > 0) {
            var main_category = $('#item-'+id).attr('data-main_category');
            $('#form_modal #item_main_category').val(main_category);
        }
        if($('#form_modal #item_sale_off').length > 0) {
            var sale_off = $('#item-'+id).attr('data-sale_off');
            $('#form_modal #item_sale_off').val(sale_off);
        }
        if($('#form_modal #item_sale_off_type').length > 0) {
            var sale_off_type = $('#item-'+id).attr('data-sale_off_type');
            $('#form_modal #item_sale_off_type').val(sale_off_type);
        }
        if($('#form_modal #item_start_date').length > 0) {
            var start_date = $('#item-'+id).attr('data-start_date');
            var parse_date = start_date.split('-');
            $('#form_modal #item_start_date').val(parse_date[2]+'-'+parse_date[1]+'-'+parse_date[0]);
        }
        if($('#form_modal #item_end_date').length > 0) {
            var end_date = $('#item-'+id).attr('data-end_date');
            var parse_date = end_date.split('-');
            $('#form_modal #item_end_date').val(parse_date[2]+'-'+parse_date[1]+'-'+parse_date[0]);
        }
    }
    $('#form_modal').modal();
}
function load_data_of_shop(category_id, product_id) {
    var shop_id = $('#form_modal #item_shop').val();
    var load_category = 0;
    var load_product = 0;
    if($('#form_modal #item_product_category_id').length > 0) {
        $('#form_modal #item_product_category_id').html('<option value="">Xin lựa chọn</option>');
        load_category = 1;
    }
    if($('#form_modal #item_product_item_id').length > 0) {
        $('#form_modal #item_product_item_id').html('<option value="">Xin lựa chọn</option>');
        load_product = 1;
    }
    if((load_category == 0 && load_product == 0) || (shop_id == 0 || shop_id == '')) {
        return true;
    }
    $.ajax({
        url: '/ajax/shop/loadItem',
        type: 'post',
        data: {
            shop_id : shop_id,
            load_category : load_category,
            load_product : load_product
        },
        dataType: 'json',
        success: function (result) {
            if(result.success) {
                if($('#form_modal #item_product_category_id').length > 0) {
                    for(var i=0;i<result.data.categories.length;i++) {
                        var category = result.data.categories[i];
                        var sel = '';
                        if(parseInt(category.id) == category_id) {
                            sel = 'selected';
                        }
                        var html = '<option value="'+category.id+'" '+sel+'>'+category.name+'</option>';
                        $('#form_modal #item_product_category_id').append(html);
                    }
                }
                if($('#form_modal #item_product_item_id').length > 0) {
                    for(var i=0;i<result.data.products.length;i++) {
                        var product = result.data.products[i];
                        var sel = '';
                        if(parseInt(product.id) == product_id) {
                            sel = 'selected';
                        }
                        var html = '<option value="'+product.id+'" '+sel+'>'+product.name+'</option>';
                        $('#form_modal #item_product_item_id').append(html);
                    }
                }
            }
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}

function save_item(type) {
    $('#form_modal .validation-error').hide();
    var fom_data = {};
    fom_data['id'] = $('#form_modal #item_id').val();
    var error = false;
    if($('#form_modal #item_name').length > 0) {
        fom_data['name'] = $('#form_modal #item_name').val();
    }
    if($('#form_modal #item_description').length > 0) {
        fom_data['description'] = $('#form_modal #item_description').html();
    }
    if($('#form_modal #item_status').length > 0) {
        fom_data['status'] = $('#form_modal #item_status').val();
    }
    if($('#form_modal #item_shop').length > 0) {
        fom_data['shop_id'] = $('#form_modal #item_shop').val();
    }
    if($('#form_modal #item_slug').length > 0) {
        fom_data['slug'] = $('#form_modal #item_slug').val();
    }
    if($('#form_modal #item_main_category').length > 0) {
        fom_data['main_category_id'] = $('#form_modal #item_main_category').val();
    }
    if($('#form_modal #item_product_category_id').length > 0) {
        fom_data['product_category_id'] = $('#form_modal #item_product_category_id').val();
    }
    if($('#form_modal #item_product_item_id').length > 0) {
        fom_data['product_item_id'] = $('#form_modal #item_product_item_id').val();
    }
    if($('#form_modal #item_sale_off').length > 0) {
        fom_data['sale_off'] = $('#form_modal #item_sale_off').val();
    }
    if($('#form_modal #item_sale_off_type').length > 0) {
        fom_data['sale_off_type'] = $('#form_modal #item_sale_off_type').val();
    }
    if($('#form_modal #item_start_date').length > 0) {
        fom_data['start_date'] = $('#form_modal #item_start_date').val();
    }
    if($('#form_modal #item_end_date').length > 0) {
        fom_data['end_date'] = $('#form_modal #item_end_date').val();
    }
    if(error) {
        return false;
    }
    $.ajax({
        url: '/ajax/'+type+'/save',
        type: 'post',
        data: fom_data,
        dataType: 'json',
        success: function (result) {
            var errors_map = {
                name: 'item_name_error', 
                slug: 'item_slug_error', 
                description: 'item_description_error',
                status: 'item_status_error', 
                shop_id: 'item_shop_error', 
                main_category_id: 'item_main_category_error', 
                start_date: 'item_start_date_error', 
                end_date: 'item_end_date_error', 
                sale_off_type: 'item_sale_off_type_error', 
                sale_off: 'item_sale_off_error'
            };
            save_form_success(result, errors_map);
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}
function validation_js(value, value_name, error_id, rules) {
    for(var i = 0; i< rules.length; i++) {
        switch(rules[i]) {
            case 'required':
                if(value == '' || parseInt(value) == 0) {
                    $('#'+error_id).text(value_name+' '+l('can not be blank'));
                    $('#'+error_id).show();
                    return true;
                }
                break;
            case 'number' :
                var tmp_value = value.replace(/[^0-9\.]/g,'');
                if(tmp_value !== value){
                    $('#'+error_id).text(value_name+' '+l('must be numberic'));
                    $('#'+error_id).show();
                    return true;
                }
            default:
                if(rules[i].indexOf("-") > -1) {
                    var exp = rules[i].split('-');
                    switch(exp[0]) {
                        case 'min':
                            if(value.length > 0 && value.length < parseInt(exp[1])) {
                                $('#'+error_id).text(value_name+' quá ngắn (ít nhất '+exp[1]+' ký tự)');
                                $('#'+error_id).show();
                                return true;
                            }
                            break;
                        case 'max':
                            if(value.length > 0 && value.length > parseInt(exp[1])) {
                                $('#'+error_id).text(value_name+' quá dài (nhiều nhất '+exp[1]+' ký tự)');
                                $('#'+error_id).show();
                                return true;
                            }
                            break;
                        default:
                            break;
                    }
                }
                break;
        }
    }
    return false;
}
function arrange_items(type) {
    $.ajax({
        url: '/ajax/'+type+'/arrange',
        dataType: 'json',
        success: function (result) {
            save_form_success(result, {});
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}

function open_stt_modal(type, id) {
    $('#stt_modal #item_id').val(id);
    var stt = $('#item-'+id+ ' .stt-editable').text();
    $('#stt_modal #item_stt').val(stt);
    $('#stt_modal').modal('show');
}
function save_item_stt(type) {
    $('#stt_modal .validation-error').hide();
    var fom_data = {};
    fom_data['id'] = $('#stt_modal #item_id').val();
    var error = false;
    fom_data['stt'] = $('#stt_modal #item_stt').val();
    error = error || validation_js(fom_data['stt'], l('Stt'), 'item_stt_error',['required','number']);
    if(error) {
        return false;
    }
    $.ajax({
        url: '/ajax/'+type+'/stt',
        type: 'post',
        data: fom_data,
        dataType: 'json',
        success: function (result) {
            var errors_map = {
                stt: 'item_stt_error'
            };
            save_form_success(result, errors_map);
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}
function preview_this(ele) {
    var src = $(ele).attr('src');
    $('#preview_image_modal').find('.image-fullsize').attr('src',src.replace('/small',''));
    $('#preview_image_modal').modal('show');
    setTimeout(function() {
        var image_height = $('#preview_image_modal').find('.image-fullsize').height();
        if($(window).height() > image_height) {
            var margin_top = ($(window).height() - image_height) / 2;
            $('#preview_image_modal').find('.modal-content').animate({ marginTop: margin_top+'px'}, 500);
        }
    },500);
}
function load_more_new() {
    var last_time = $('#last_time').val();
    var group_id = parseInt($('#group_id').val());
    if(group_id == 0 || last_time == '') {
        return false;
    }
    $('#last_time').val('');
    $.ajax({
        url: '/ajax/message/loadMoreNew',
        type: 'post',
        data: {
            group_id:group_id,
            last_time: last_time
        },
        dataType: 'json',
        success: function (result) {
            save_form_success(result, {});
            if(result.success) {
                if(result.data.length == 0) {
                    $('#last_time').val(last_time);
                }
                var last_time_next = '';
                for(var i=0;i<result.data.length;i++) {
                    display_message(result.data[i], true);
                    last_time_next = result.data[i].created_date;
                }
                if(last_time_next != '') {
                    $('#last_time').val(last_time_next);
                }
                if(result.data.length > 0) {
                    scroll_to_last_message();
                }
            }
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}
function load_more_old() {
    var last_old_time = $('#last_old_time').val();
    var group_id = parseInt($('#group_id').val());
    if(group_id == 0 || last_old_time == '') {
        return false;
    }
    $('#last_old_time').val('');
    $.ajax({
        url: '/ajax/message/loadMoreOld',
        type: 'post',
        data: {
            group_id:group_id,
            last_old_time: last_old_time
        },
        dataType: 'json',
        success: function (result) {
            save_form_success(result, {});
            if(result.success) {
                for(var i=result.data.length-1;i>=0;i--) {
                    display_message(result.data[i], false);
                    if(i==0) {
                        $('#last_old_time').val(result.data[i].created_date);
                    }
                }
                if(result.data.length > 0) {
                    var height = result.data.length * 85;
                    $(".mess-detail-list .mess-area").animate({
                        scrollTop: height
                    }, 0);
                }
            }
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}
function read_message_group(group_id) {
    var current_group_id = parseInt($('#group_id').val());
    if(current_group_id == group_id || group_id == 0) {
        return false;
    }
    $.ajax({
        url: '/ajax/message/loadGroup',
        type: 'post',
        data: {
            group_id:group_id
        },
        dataType: 'json',
        success: function (result) {
            save_form_success(result, {});
            if(result.success) {
                $('.mess-area ul.chats').html('');
                $('.mess-group-list li.new_message').removeClass('new_message');
                $('#group_chat_'+group_id).addClass('new_message');
                $('#group_id').val(group_id);
                $('.mess-detail-list .panel-body').show();
                $('.mess-detail-list .panel-footer').show();
                var last_time = '';
                for(var i=0;i<result.data.length;i++) {
                    display_message(result.data[i], true);
                    if(i==0) {
                        $('#last_old_time').val(result.data[i].created_date);
                    }
                    last_time = result.data[i].created_date;
                }
                if(last_time != '') {
                    $('#last_time').val(last_time);
                }
                scroll_to_last_message();
                mark_read(group_id);
            }
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}
function mark_read(group_id) {
    $('#group_chat_'+group_id+' .count_new').fadeOut("slow");
}
function display_message(message, new_mess) {
    var session_user_id = parseInt($('#session_user_id').val());
    var html = '';
    if(parseInt(message.is_system) == 1) {
        html += '<li">';
        html += '<div class="system_message">';
        html += message.user_name + ' ' + message.content;
        html += '</div>';
        html += '</li>';
    }else{
        var float = 'left';
        if(message.user_id == session_user_id) {
            float = 'right';
        }
        html += '<li class="'+float+'">';
        html += '<span class="date-time">'+message.time+'</span>';
        html += '<a href="'+message.url+'" class="name">'+message.user_name+''+message.user_type+'</a>';
        html += '<a href="'+message.url+'" class="image"><img alt="" src="'+message.user_avatar+'"></a>';
        html += '<div class="message">';
        html += message.content;
        html += '</div>';
        html += '</li>';
    }
    if(new_mess) {
        $('.mess-area ul.chats').append(html);
    } else {
        $('.mess-area ul.chats').prepend(html);
    }
}
function scroll_to_last_message() {
    var mess_area = $(".mess-detail-list .mess-area")[0];
    $(mess_area).scrollTop($(mess_area).prop("scrollHeight"));
}
function send_message() {
    var group_id = parseInt($('#group_id').val());
    var content = $('#mess_content').html();
    if(group_id == 0 || $.trim(content) == '' || $.trim(content) == '<div><br></div>') {
        return false;
    }
    var last_time = $('#last_time').val();

    $.ajax({
        url: '/ajax/message/send',
        type: 'post',
        data: {
            group_id:group_id,
            last_time:last_time,
            content:content
        },
        dataType: 'json',
        success: function (result) {
            save_form_success(result, {});
            if(result.success) {
                var last_time = '';
                for(var i=0;i<result.data.length;i++) {
                    display_message(result.data[i], true);
                    last_time = result.data[i].created_date;
                }
                if(last_time != '') {
                    $('#last_time').val(last_time);
                }
                scroll_to_last_message();
                mark_read(group_id);
                $('#mess_content').html('');
            }
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}
function current_datetime() {
    var currentdate = new Date(); 
    var year = currentdate.getFullYear();
    var month = currentdate.getMonth()+1;
    if(month < 10) {
        month = '0' + month;
    }
    var day = currentdate.getDate();
    if(day < 10) {
        day = '0' + day;
    }
    var hour = currentdate.getHours();
    if(hour < 10) {
        hour = '0' + hour;
    }
    var minu = currentdate.getMinutes();
    if(minu < 10) {
        minu = '0' + minu;
    }
    var secon = currentdate.getSeconds();
    if(secon < 10) {
        secon = '0' + secon;
    }
    return year+'-'+month+'-'+day+' '+hour+':'+minu+':'+secon;
}
function remove_attribute(ind) {
    $('#attribute_'+ind).remove();
    arrange_attribute_index();
}
function add_attribute() {
    var attribute= '<div class="class_attribute">';
        attribute+= '<div class="col-md-6 pd-l-0">';
            attribute+= '<input type="text" class="form-control" placeholder="'+l("Name")+'">';
        attribute+= '</div>';
        attribute+= '<div class="col-md-5">';
            attribute+= '<input type="text" class="form-control" placeholder="'+l("Value")+'">';
        attribute+= '</div>';
        attribute+= '<div class="btn btn-danger"><i class="fa fa-minus"></i></div>';
        attribute+= '<div class="clear10"></div>';
    attribute+= '</div>';
    $('#attribute-area').append(attribute);
    arrange_attribute_index();
}
function arrange_attribute_index() {
    var attributes = $('#attribute-area .class_attribute');
    for (var j=0;j<attributes.length;j++) {
        var attribute = attributes[j];
        var i = j+1;
        $(attribute).attr('id', 'attribute_'+i);
        $(attribute).find('.col-md-6 input').attr('name','attribute_'+i+'[name]');
        $(attribute).find('.col-md-5 input').attr('name','attribute_'+i+'[value]');
        $(attribute).find('.btn-danger').attr('onclick','remove_attribute('+i+')');
    }
}
function open_new_messages_modal() {
    $('#new_message_modal .selected_users').html('');
    $('#new_message_modal #selected_user_ids').val('');
    $('#new_message_modal').modal('show');
}
function select_this_user() {
    var user_id = $('#new_message_modal #item_users').val();
    if(user_id == 0 || user_id == '' || user_id == '0') {
        return false;
    }
    setTimeout(function(){
        $('#new_message_modal').find('.glyphicon.glyphicon-remove').trigger('click');
    },1);
    $('#item_users_error').html('');
    var selected_user_ids = $('#new_message_modal #selected_user_ids').val();
    if (selected_user_ids.indexOf(','+user_id+',') >= 0) {
        return false;
    }
    selected_user_ids += ','+user_id+',';
    $('#new_message_modal #selected_user_ids').val(selected_user_ids);
    var options = $('#new_message_modal #item_users').find('option');
    var name = '';
    for(var j=0;j<options.length;j++) {
        if($(options[j]).attr('value')==user_id) {
            name = $(options[j]).text(); break;
        }
    }
    $('#new_message_modal .selected_users').append('<div id="next_mess_'+user_id+'"><i onclick="remove_on('+user_id+')" class="fa fa-trash on_mess_remove"></i>'+name+'</div>');
}
function remove_on(user_id) {
    $('#next_mess_'+user_id).remove();
    var selected_user_ids = $('#new_message_modal #selected_user_ids').val();
    selected_user_ids = selected_user_ids.replace(','+user_id+',','');
    $('#new_message_modal #selected_user_ids').val(selected_user_ids);
}
function create_new_message() {
    var selected_user_ids = $('#new_message_modal #selected_user_ids').val();
    if(selected_user_ids == '') {
        $('#item_users_error').html(l('Please select user'));
        return false;
    }
    $.ajax({
        url: '/ajax/message/create',
        type: 'post',
        data: {
            user_ids:selected_user_ids
        },
        dataType: 'json',
        success: function (result) {
            save_form_success(result, {});
            if(result.success) {
                $('.mess-area ul.chats').html('');
                $('.mess-group-list li.new_message').removeClass('new_message');
                var group = result.data.group;
                var messages = result.data.messages;
                if($('.mess-group-list #group_chat_'+group.id).length == 0) {
                    display_message_group(group);
                } else {
                    $('#group_chat_'+group.id).addClass('new_message');
                }
                $('#group_id').val(group.id);
                var last_time = '';
                $('.mess-detail-list .panel-body').show();
                $('.mess-detail-list .panel-footer').show();
                for(var i=0;i<messages.length;i++) {
                    display_message(messages[i], true);
                    last_time = messages[i].created_date;
                }
                if(last_time != '') {
                    $('#last_time').val(last_time);
                }
                scroll_to_last_message();
                mark_read(group.id);
                $('#new_message_modal').modal('hide');
            }
        },
        error: function (xhr) {
            ajax_error();
        }
    });
}
function display_message_group(group) {
    var html = '<li id="group_chat_'+group.id+'" class="media new_message">';
        html += '<a onclick="read_message_group('+group.id+')">';
            html += '<div class="media-left"><img src="'+group.image+'" class="media-object" alt=""></div>';
            html += '<div class="media-body" style="vertical-align: middle;">';
                html += '<h5 class="media-heading">'+group.name+'</h5>';
                html += '<div class="text-muted">'+group.time+'</div>';
            html += '</div>';
        html += '</a>';
    html += '</li>';
    $('#dl_mess_area').prepend(html);
}

function insert_smiley(ele) {
    var content = $('#mess_content').html();
    var src = $(ele).attr('src');
    content += '<img src="'+src+'">';
    $('#mess_content').html(content);
    placeCaretAtEnd($('#mess_content').get(0));
}
function edit_group() {
    var group_id = parseInt($('#group_id').val());
    if(group_id == 0) {
        return false;
    }
    var name = $('#group_chat_'+group_id).find('.media-heading').text();
    $('#edit_group_modal').find('#item_name').val(name);
    var img = $('#group_chat_'+group_id).find('.media-object').attr('src');
    $('#edit_group_modal').find('img').attr('src', img);
    $('#edit_group_modal').modal('show');
}
function submit_edit_group() {
    var group_id = parseInt($('#group_id').val());
    if(group_id == 0) {
        return false;
    }
    var data = new FormData();
    data.append('group_id', group_id);
    data.append('last_time', $('#last_time').val());
    var name = $('#edit_group_modal').find('#item_name').val();
    data.append('name', name);
    var image = $('#edit_group_modal').find('#item_image').val();
    if(image != '') {
        if(!validation_image('item_image', 'item_image_error')) {
            return false;
        }
        data.append('image', $('#item_image')[0].files[0]);
    }
    $.ajax({
        url: '/ajax/message/editGroup',
        type: "POST",
        data: data,
        enctype: 'multipart/form-data',
        processData: false,
        contentType: false,
        dataType: 'json'
    }).done(function(result) {
        save_form_success(result, {name: 'item_name_error',image: 'item_image_error'});
        var group = result.data.group;
        $('#group_chat_'+group_id).find('.media-heading').text(group.name);
        $('#group_chat_'+group_id).find('.media-object').attr('src',group.image);
        $('#edit_group_modal').modal('hide');
        var messages = result.data.messages;
        var last_time = '';
        for(var i=0;i<messages.length;i++) {
            display_message(messages[i], true);
            last_time = messages[i].created_date;
        }
        if(last_time != '') {
            $('#last_time').val(last_time);
        }
        scroll_to_last_message();
        mark_read(group_id);
        $('#mess_content').html('');
    });
}