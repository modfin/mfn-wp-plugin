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


    function syncTax() {


        $.get(window.PLUGIN_URL + '/cc.php?mode=sync-tax', function (data) {

            console.log(data)

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
        var hubUrl = $('#mfn-wp-plugin-hub_url').val();
        var el =  $('#hub-url-test');


        if (!hubUrl || hubUrl.trim() === '') {
            el.text("Invalid, hub url must be provided");
            return
        }

        $.get(hubUrl, function (data) {
            if (typeof data === 'string' && data.indexOf('- WebSub hub server, https://www.w3.org/TR/websub') !== -1) {
                el.text("Valid");
                return
            }
            el.text("Invalid, hub does not return a proper response \n\n (make sure php function file_get_contents is allowed to make http requests) \n\n " + data );
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



    function clearSettings(){

        var command = $('#clear-settings-input').val().trim().toLowerCase();

        if (command !== 'clear'){
            alert("You must write the word \"clear\" in the input field next to the button to show intent for this action")
            return
        }

        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();

        if(pluginUrl.length < 3){
            pluginUrl = "/wp-content/plugins/mfn-wp-plugin";
        }

        $.get(pluginUrl + '/cc.php?mode=clear-settings', function (data) {
            setTimeout(function (){
                location.reload()
            }, 100);
        })

    }


    function deletePosts(total){




        total = total || 0;

        var command = $('#delete-posts-input').val().trim().toLowerCase();

        if (command !== 'delete'){
            alert("You must write the word \"delete\" in the input field next to the button to show intent for this action");
            return
        }

        $('#delete-posts-btn').prop('disabled', true);
        $('#delete-posts-input').prop('disabled', true);

        var pluginUrl = $('#mfn-wp-plugin-plugin_url').val();

        if(pluginUrl.length < 3){
            pluginUrl = "/wp-content/plugins/mfn-wp-plugin";
        }

        $.get(pluginUrl + '/cc.php?mode=delete-all-posts&limit=10', function (data) {

            var deleted = parseInt(data);
            total += deleted;
            if (deleted === 10){
                $('#delete-posts-nfo').text(total + " deleted");
                deletePosts(total);
                return
            }

            $('#delete-posts-nfo').text(total + " deleted, done");
            $('#delete-posts-btn').prop('disabled', false);
            $('#delete-posts-input').prop('disabled', false);
            $('#delete-posts-input').val('')

        })

    }



    function init() {
        $("#sync-all").click(function () {
            sync(true)
        });
        $("#sync-latest").click(function () {
            sync(false)
        });

        $("#sync-tax").click(function () {
            syncTax()
        });


        $("#sub-button").click(function () {
            subscribe()
        });
        $("#unsub-button").click(function () {
            unsubscribe()
        });


        $("#clear-settings-btn").click(function () {
            clearSettings()
        });

        $("#delete-posts-btn").click(function () {
            deletePosts()
        });

        tests()



    }


    $(document).ready(init);

})(jQuery);


