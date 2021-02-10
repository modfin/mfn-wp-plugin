(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    var errorClass = 'highlight-input-error';

    $( document ).ready(function() {
        $('a.mfn-nav-tab').first().addClass('mfn-nav-tab-active');
        $('.mfn-lang-table').first().removeClass('mfn-hide').addClass("do-fade");

        if ($('#mfn-wp-plugin-entity_id').val() === '') {
            $('#mfn-wp-plugin-entity_id').addClass(errorClass);
        }
        $('#mfn-wp-plugin-entity_id').keyup(function () {
            if ($(this).val() === '') {
                $('#mfn-wp-plugin-entity_id').addClass(errorClass);
            }
            else {
                $('#mfn-wp-plugin-entity_id').removeClass(errorClass);
            }
        });

        $('span.mfn-info-icon-wrapper').hover(function() {
            $('.mfn-info').addClass('mfn-info-box');
        }, function() {
            $('.mfn-info').removeClass('mfn-info-box');
        });

        $('a.mfn-nav-tab').click(function (e) {
            e.preventDefault();
            var lang = $(this).attr('data-lang');
            $('a.mfn-nav-tab').removeClass('mfn-nav-tab-active');
            $(this).addClass('mfn-nav-tab-active');

            $('.mfn-lang-table').addClass('mfn-hide do-fade');
            $('.mfn-lang-table-' + lang).removeClass('mfn-hide');
        });
    });

    function sync(all, limit, offset, insertedAgg) {

        insertedAgg = insertedAgg || 0;
        limit = limit || 10;
        offset = offset || 0;
        offset = parseInt(offset);

        $.get(window.PLUGIN_URL + '/cc.php?mode=sync&limit=' + limit + '&offset=' + offset, function (data) {

            var fetched = parseInt(data.split(' ')[0]);
            var inserted = parseInt(data.split(' ')[1]);
            insertedAgg += inserted;

            if (isNaN(fetched)) {
                $('#sync-status').html("<div class='do-fade mfn-error-entity-id'><span class=\"dashicons dashicons-dismiss mfn-error-icon\"></span> <span class='mfn-error-entity-id-text'>Failed (Check Entity ID and Sync URL)</span></div>");
                return;
            }

            $('#sync-status').html('<b>Status</b>: <span class="do-fade">' + (offset+fetched) + '</span> fetched <span class="do-fade"><b>' + insertedAgg + "</b></span> added");

            if (all && fetched === limit) {
                $('#sync-status').append("<span class='mfn-spinner'></span>");
                sync(all, limit, offset + limit, insertedAgg);
                return;
            }

            if (all) {
                registerSyncAllClick();
                // also sync taxonomy
                syncTax();
            } else {
                registerSyncLatestClick();
            }

            $('#sync-status').append("<span class=\"dashicons dashicons-yes mfn-success-icon\"></span>Done!");
        });

    }

    function syncTax() {
        $('#sync-tax-status').html("<span class='mfn-spinner'></span>");

        $.get(window.PLUGIN_URL + '/cc.php?mode=sync-tax', function () {
            registerSyncTaxClick();
            $('#sync-tax-status').html("<span class=\"dashicons dashicons-yes mfn-success-icon do-fade\"></span>Done!");
        });
    }

    function pluginUrlTest() {
        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();
        var el =  $('#plugin-url-test');

        if(!pluginUrl || pluginUrl.trim() === '') {
            el.html("<span class=\"dashicons dashicons-warning mfn-warning-icon do-fade\"></span> Invalid, plugin url must be provided");
            return;
        }

        $.get(pluginUrl + '/cc.php?mode=ping', function (data) {
            if (data === 'pong') {
                el.html("<span class=\"dashicons dashicons-yes mfn-success-icon do-fade\"></span>Valid");
                return;
            }
            $('#mfn-wp-plugin-plugin_url').addClass(errorClass);
            el.html("<span class=\"dashicons dashicons-warning mfn-warning-icon do-fade\"></span> Invalid, server does not return pong");
        }).fail(function(err) {
            console.error(err);
            $('#mfn-wp-plugin-plugin_url').addClass(errorClass);
            el.html("<span class=\"dashicons dashicons-warning mfn-warning-icon do-fade\"></span> Invalid, address does not seem to be responding with anything " + "(" + err.status + ")");
        })
    }

    function hubUrlTest(){
        var hubUrl = $('#mfn-wp-plugin-hub_url').val();
        var el =  $('#hub-url-test');

        if(!hubUrl || hubUrl.trim() === '') {
            $('#mfn-wp-plugin-hub_url').addClass(errorClass);
            el.html("<span class=\"dashicons dashicons-warning mfn-warning-icon do-fade\"></span> Invalid, hub url must be provided");
            return;
        }

        $.get(hubUrl, function (data) {
            if (typeof data === 'string' && data.indexOf('- WebSub hub server, https://www.w3.org/TR/websub') !== -1) {
                el.html("<span class=\"dashicons dashicons-yes mfn-success-icon do-fade\"></span>Valid");
                return;
            }
            $('#mfn-wp-plugin-hub_url').addClass(errorClass);
            el.html("<span class=\"dashicons dashicons-warning mfn-warning-icon do-fade\"></span> Invalid, server does not return pong \n\n - make sure php function <i>file_get_contents</i> is allowed to make http requests \n\n (" + data + ")");
        }).fail(function(err) {
            console.error(err);
            $('#mfn-wp-plugin-hub_url').addClass(errorClass);
            el.html("<span class=\"dashicons dashicons-warning mfn-warning-icon do-fade\"></span> Ping to hub url failed " + "(" + err.status + ")");
        })
    }

    function tests() {
        pluginUrlTest();
        hubUrlTest();
    }

    function subscribe(){
        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();
        $.get(pluginUrl + '/cc.php?mode=subscribe', function () {
            setTimeout(function () {
                location.reload();
            }, 100);
        })

    }

    function unsubscribe(){
        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();
        $.get(pluginUrl + '/cc.php?mode=unsubscribe', function () {
            setTimeout(function () {
                location.reload();
            }, 100);

        })
    }

    function clearSettings(){

        var command = $('#clear-settings-input').val().trim().toLowerCase();

        if (command !== 'clear'){
            alert("You must write the word \"clear\" in the input field next to the button to show intent for this action");
            return;
        }

        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();

        if(pluginUrl.length < 3){
            pluginUrl = "/wp-content/plugins/mfn-wp-plugin";
        }

        $.get(pluginUrl + '/cc.php?mode=clear-settings', function () {
            setTimeout(function () {
                location.reload();
            }, 100);
        })

    }

    function deletePosts(total) {

        total = total || 0;

        var command = $('#delete-posts-input').val().trim().toLowerCase();

        if (command !== 'delete') {
            alert("You must write the word \"delete\" in the input field next to the button to show intent for this action");
            return;
        }

        $('#delete-posts-btn').prop('disabled', true);
        $('#delete-posts-input').prop('disabled', true);

        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();

        if(pluginUrl.length < 3) {
            pluginUrl = "/wp-content/plugins/mfn-wp-plugin";
        }

        $.get(pluginUrl + '/cc.php?mode=delete-all-posts&limit=10', function (data) {

            var deleted = parseInt(data);
            total += deleted;
            if (deleted === 10){
                $('#delete-posts-info').html(total + " deleted");
                deletePosts(total);
                return;
            }

            $('#delete-posts-info').html('<span class="do-fade">' + total + '</span>' + " deleted <span class=\"dashicons dashicons-yes mfn-success-icon do-fade\"></span>Done!");
            $('#delete-posts-btn').prop('disabled', false);
            $('#delete-posts-input').prop('disabled', false);
            $('#delete-posts-input').val('');

        });

    }

    function registerSyncAllClick() {
        $("#sync-all").one("click", function (event) {
            event.stopPropagation();
            sync(true);
        });
    }
    function registerSyncLatestClick() {
        $("#sync-latest").one("click", function (event) {
            event.stopPropagation();
            sync(false);
        });
    }
    function registerSyncTaxClick() {
        $("#sync-tax").one("click", function () {
            syncTax();
        });
    }

    function init() {

        registerSyncAllClick();
        registerSyncLatestClick();
        registerSyncTaxClick();

        $("#sub-button").click(function () {
            subscribe();
        });
        $("#unsub-button").click(function () {
            unsubscribe();
        });

        $("#clear-settings-btn").click(function () {
            clearSettings();
        });

        $("#delete-posts-btn").click(function () {
            deletePosts();
        });

        tests();

    }

    $(document).ready(init);

})(jQuery);


