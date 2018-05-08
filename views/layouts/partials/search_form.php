<input id="open-filter-btn" class="btn btn-success btn-lg p-5-15" type="button" value="<?php echo 'Search';?>" style="margin: 10px;"
    onclick="$(this).remove();$('.dl-filter-form').show();">

<form class="m-t-20 dl-filter-form" style="display:none">
<?php
if (!isset($filters)) {
    $filters = array();
}
foreach ($filters as $key => $value) { ?>
    <div class="form-group col-md-6 m-b-10">
        <label class="col-md-4 control-label label-search-1"><?php echo $key; ?></label>
        <div class="col-md-8">
            <?php if ($key == 'from_date' || $key == 'to_date') { 
                $option_from_to_date = array(
                    'name' => $key,
                    'type' => 'text',
                    'class' => 'form-control w-100-per input-datepicker',
                    'value' => $value,
                    'placeholder' => $key
                );
                echo_input($option_from_to_date); ?>
                <span class="input-group-addon dl-input-calendar"><i class="fa fa-calendar"></i></span>
            <?php } elseif ($key == 'is_admin') {
                $option_user_type = array(
                    'name' => $key,
                    'type' => 'select',
                    'class' => 'form-control'
                );
                $items = array(
                    ''=>'Please select',
                    '0'=>'User',
                    '1'=>'Admin'
                );
                echo_input($option_user_type, $items, $value==='' ? '' : $value);
            } elseif ($key == 'status') {
                $option_status = array(
                    'name' => $key,
                    'type' => 'select',
                    'class' => 'form-control'
                );
                $status_arr = array(
                    ''=>'Please select',
                    '0'=>'Inactive',
                    '1'=>'Active'
                );
                echo_input($option_status, $status_arr, $value==='' ? '' : $value);
            } elseif ($key == 'book_id') {
                $option_book = array(
                    'name' => $key,
                    'type' => 'select',
                    'class' => 'form-control dl_combobox'
                );
                $tmp_books = app\models\Book::find()->all();
                $books_arr = array(
                    ''=>'Please select'
                );
                foreach ($tmp_books as $tmp_book) {
                    $books_arr[$tmp_book->id] = $tmp_book->name;
                }
                echo_input($option_book, $books_arr, $value==='' ? '' : $value);
            } elseif ($key == 'user_id') {
                $option_user = array(
                    'name' => $key,
                    'type' => 'select',
                    'class' => 'form-control dl_combobox'
                );
                $tmp_users = app\models\User::find()->all();
                $users_arr = array(
                    ''=>'Please select'
                );
                foreach ($tmp_users as $tmp_user) {
                    $users_arr[$tmp_user->id] = $tmp_user->name;
                }
                echo_input($option_user, $users_arr, $value==='' ? '' : $value);
            } else {
                $option_else = array(
                    'name' => $key,
                    'type' => 'text',
                    'class' => 'form-control w-100-per',
                    'value' => $value,
                    'placeholder' => $key
                );
                echo_input($option_else);
            } ?>
        </div>
    </div>
<?php } ?>
<div class="clear0"></div>
<div class="form-group col-md-6 m-b-20 m-t-10">
    <label class="col-md-4 control-label label-search-1"></label>
    <div class="col-md-8">
        <input class="btn btn-success btn-lg p-5-15" type="submit" value="<?php echo 'Search';?>">
    </div>
</div>
</form>
<script>
    $( function() {
        $.widget( "custom.combobox", {
            _create: function() {
                this.wrapper = $( "<span>" )
                    .addClass( "custom-combobox" )
                    .insertAfter( this.element );

                this.element.hide();
                this._createAutocomplete();
                this._createShowAllButton();
            },

            _createAutocomplete: function() {
                var selected = this.element.children( ":selected" ),
                    value = selected.val() ? selected.text() : "";

                this.input = $( "<input>" )
                    .appendTo( this.wrapper )
                    .val( value )
                    .attr( "title", "" )
                    .addClass( "custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left" )
                    .autocomplete({
                        delay: 0,
                        minLength: 0,
                        source: $.proxy( this, "_source" )
                    })
                    .tooltip({
                        classes: {
                            "ui-tooltip": "ui-state-highlight"
                        }
                    });

                this._on( this.input, {
                    autocompleteselect: function( event, ui ) {
                        ui.item.option.selected = true;
                        this._trigger( "select", event, {
                            item: ui.item.option
                        });
                    },

                    autocompletechange: "_removeIfInvalid"
                });
            },

            _createShowAllButton: function() {
                var input = this.input,
                    wasOpen = false;

                $( "<a>" )
                    .attr( "tabIndex", -1 )
                    .attr( "title", "Show All Items" )
                    .tooltip()
                    .appendTo( this.wrapper )
                    .button({
                        icons: {
                            primary: "ui-icon-triangle-1-s"
                        },
                        text: false
                    })
                    .removeClass( "ui-corner-all" )
                    .addClass( "custom-combobox-toggle ui-corner-right" )
                    .on( "mousedown", function() {
                        wasOpen = input.autocomplete( "widget" ).is( ":visible" );
                    })
                    .on( "click", function() {
                        input.trigger( "focus" );

                        // Close if already visible
                        if ( wasOpen ) {
                            return;
                        }

                        // Pass empty string as value to search for, displaying all results
                        input.autocomplete( "search", "" );
                    });
            },

            _source: function( request, response ) {
                var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
                response( this.element.children( "option" ).map(function() {
                    var text = $( this ).text();
                    if ( this.value && ( !request.term || matcher.test(text) ) )
                        return {
                            label: text,
                            value: text,
                            option: this
                        };
                }) );
            },

            _removeIfInvalid: function( event, ui ) {

                // Selected an item, nothing to do
                if ( ui.item ) {
                    return;
                }

                // Search for a match (case-insensitive)
                var value = this.input.val(),
                    valueLowerCase = value.toLowerCase(),
                    valid = false;
                this.element.children( "option" ).each(function() {
                    if ( $( this ).text().toLowerCase() === valueLowerCase ) {
                        this.selected = valid = true;
                        return false;
                    }
                });

                // Found a match, nothing to do
                if ( valid ) {
                    console.log(this.element.val());
                    return;
                }

                // Remove invalid value
                console.log(this.input.val());
                this.input
                    .val( "" )
                    .attr( "title", value + " didn't match any item" )
                    .tooltip( "open" );
                this.element.val( "" );
                this._delay(function() {
                    this.input.tooltip( "close" ).attr( "title", "" );
                }, 2500 );
                this.input.autocomplete( "instance" ).term = "";
            },

            _destroy: function() {
                this.wrapper.remove();
                this.element.show();
            }
        });
    });
    $(document).ready(function () {
        <?php
        $tmp_filter = $filters;
        foreach ($tmp_filter as $k => $v) {
            if ($v === '' || ($k == 'to_date' && $v === date('d-m-Y'))) {
                unset($tmp_filter[$k]);
            }
        }
        if(!empty($tmp_filter)) { ?>
        $('#open-filter-btn').click();
        <?php } ?>
        $('.dl_combobox').combobox();
    });
</script>