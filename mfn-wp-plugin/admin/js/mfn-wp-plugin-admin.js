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

    function sync(all, limit, offset, insertedAgg) {

        insertedAgg = insertedAgg || 0;
        limit = limit || 10;
        offset = offset || 0;
        offset = parseInt(offset);

        $.get(window.PLUGIN_URL + '/cc.php?mode=sync&limit=' + limit + '&offset=' + offset, function (data) {

            var fetched = parseInt(data.split(' ')[0]);
            var inserted = parseInt(data.split(' ')[1]);
            insertedAgg += inserted;


            $('#sync-status').text('Status: ' + (offset+fetched) + " fetched, " + insertedAgg + " added");

            if (all && fetched === limit) {
                sync(all, limit, offset + limit, insertedAgg);
                return
            }

            $('#sync-status').append(", Done!")

        });
    }



    function pluginUrlTest(){
        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();
        var el =  $('#plugin-url-test');


        if(!pluginUrl || pluginUrl.trim() === ''){
            el.text("Invalid, plugin url must be provided");
            return
        }

        $.get(pluginUrl + '/cc.php?mode=ping', function (data) {
            if (data === 'pong'){
                el.text("Valid");
                return
            }
            el.text("Invalid, server does not return pong");
        }).fail(function(err) {
            console.log(err);
            el.text("Invalid, address does not seem to be responding with anything, " + err.status);
        })
    }



    function hubUrlTest(){
        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();
        var el =  $('#hub-url-test');


        if(!pluginUrl || pluginUrl.trim() === ''){
            el.text("Invalid, hub url must be provided");
            return
        }

        $.get(pluginUrl + '/cc.php?mode=pinghub', function (data) {
            if (data === 'ponghub'){
                el.text("Valid");
                return
            }
            el.text("Invalid, server does not return pong \n\n (make sure php function file_get_contents is allowed to make http requests)");
        }).fail(function(err) {
            console.log(err);
            el.text("Invalid, address does not seem to be responding with anything, " + err.status);
        })
    }



    function tests() {
        pluginUrlTest();
        hubUrlTest();
    }


    function subscribe(){
        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();
        $.get(pluginUrl + '/cc.php?mode=subscribe', function (data) {
            setTimeout(function (){
                location.reload()
            }, 100);
        })

    }

    function unsubscribe(){
        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();
        $.get(pluginUrl + '/cc.php?mode=unsubscribe', function (data) {
            setTimeout(function (){
                location.reload()
            }, 100);

        })
    }



    function init() {
        $("#sync-all").click(function () {
            sync(true)
        });
        $("#sync-latest").click(function () {
            sync(false)
        });


        $("#sub-button").click(function () {
            subscribe()
        });
        $("#unsub-button").click(function () {
            unsubscribe()
        });


        tests()



    }


    $(document).ready(init);

})(jQuery);


