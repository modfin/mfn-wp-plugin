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

    var errorClass = 'mfn-highlight-input-error mfn-do-fade';
    var subscriptionId = mfn_admin_params.subscription_id;
    var statusPageUrl = mfn_admin_params.plugin_url + '/admin/partials/sections/mfn-wp-plugin-admin-status.php';
    var verificationRetries = 5;

    $(document).keydown(function(event) {
        var modalEl = $('.mfn-modal');
        if (event.keyCode === 27) {
            if (modalEl.length) {
                modalEl.remove();
            }
        }
    });

    const mfn_delay = function(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    function mfn_sync_single_item(post_id) {
        $.get(mfn_admin_params.plugin_url + '/cc.php?mode=sync&limit=1&offset=0&post_id=' + post_id)
            .done(function () {
            if (post_id !== null) {
                $('#mfn-item-restore-status').html('');
                location.reload(false);
            }
        });
    }

    function disableActionButtons(disable) {
        disable = disable ?? false;
        var prefix = 'mfn-';
        [
            prefix + 'sync-tax',
            prefix + 'sync-all',
            prefix + 'sync-latest',
        ].forEach(function (btn) {
            $('#' + btn).prop('disabled', disable);
        });
    }

    function mfn_sync(all, limit, offset, insertedAgg) {

        insertedAgg = insertedAgg || 0;
        limit = limit || 10;
        offset = offset || 0;
        offset = parseInt(offset);

        $.get(mfn_admin_params.plugin_url + '/cc.php?mode=sync&limit=' + limit + '&offset=' + offset, function (data) {
            var fetched = parseInt(data.split(' ')[0]);
            var inserted = parseInt(data.split(' ')[1]);
            insertedAgg += inserted;

            var syncStatusEl = $('#mfn-sync-status');

            if (all) {
                mfn_sync_all_click();
            } else {
                mfn_sync_latest_click();
            }

            if (isNaN(fetched)) {
                var error = data.split("sync-url-error:")[1];
                if (!error) {
                    error = data;
                }
                syncStatusEl.addClass("mfn-sync-status-error");
                syncStatusEl.html('<p class="update-nag notice notice-warning inline mfn-do-fade"><i class="dashicons dashicons-dismiss mfn-error-icon"></i>' + error + '</p>');
                disableActionButtons(false);
                return;
            }

            syncStatusEl.html('<b>Syncing...</b>&nbsp;<span class="mfn-do-fade">' + (offset+fetched) + '</span>&nbsp;fetched&nbsp;<span class="mfn-do-fade"><b>' + insertedAgg + "</b></span>&nbsp;upserted");

            if (all && fetched === limit) {
                syncStatusEl.append('<div class=\'mfn-do-fade mfn-spinner-container\'><span class=\'mfn-spinner\'></span></div>');
                mfn_sync(all, limit, offset + limit, insertedAgg);
                return;
            }

            syncStatusEl.html('<span class="mfn-do-fade"><b>Result:</b></span>&nbsp;<span class="mfn-do-fade">' + (offset+fetched) + '</span>&nbsp;fetched&nbsp;<span class="mfn-do-fade"><b>' + insertedAgg + "</b></span>&nbsp;upserted");

            disableActionButtons(false);
            syncStatusEl.append("&nbsp;&nbsp;<div class='mfn-status-container mfn-do-fade mfn-highlight-status'><span class=\"dashicons dashicons-yes do-fade mfn-success-icon\"></span>Done!</div>");
        });

    }

    async function syncTax() {
        $('#mfn-sync-tax-status').html('<div class=\'mfn-do-fade mfn-spinner-container\'><span class=\'mfn-spinner\'></span></div>');
        await $.get(mfn_admin_params.plugin_url + '/cc.php?mode=sync-tax', function () {
            $('#mfn-sync-tax-status')
                .html("<div class='mfn-status-container mfn-highlight-status mfn-do-slide-top'><span class=\"dashicons dashicons-yes mfn-success-icon mfn-do-fade\"></span>Done!</div>");
        });
    }

    function mfn_sync_url_test(testEntityIdSuccess) {
        var syncUrl = $('#mfn-wp-plugin-sync_url').val();
        var entityId = $('#mfn-wp-plugin-entity_id').val();

        if (syncUrl === 'https://mfn.se') {
            syncUrl = 'https://widget.datablocks.se/api/rose/proxy/mfn';
        }

        var syncUrlTestEl = $('#mfn-sync-url-test');

        if(!syncUrl || syncUrl.trim() === '') {
            $('#mfn-wp-plugin-sync_url').addClass(errorClass);
            el.html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Invalid, sync url must be provided");
            return;
        }

        if (testEntityIdSuccess) {
            var baseUrl = syncUrl + '/all/s.json?type=all&.author.entity_id=';

            if (syncUrl && syncUrl.startsWith('https://feed.mfn.')) {
                baseUrl = syncUrl + '/feed/';
            }

            $.get(baseUrl + entityId, function (data) {
                if (data instanceof Object) {
                    if ('items' in data && 'version' in data) {
                        syncUrlTestEl
                            .html("<span class=\"dashicons dashicons-yes mfn-success-icon mfn-do-fade\"></span><span style='cursor:default;' title='" + mfn_admin_params.sync_url + "'>Valid</span>");
                    }
                } else {
                    $('#mfn-wp-plugin-sync_url').addClass(errorClass);
                    syncUrlTestEl
                        .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Request to sync API url failed ");
                }
            }).fail(function (err) {
                $('#mfn-wp-plugin-sync_url').addClass(errorClass);
                syncUrlTestEl
                    .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Ping to sync API url failed " + "(" + err.status + ")");
            });
        } else {
            syncUrlTestEl
                .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Unable to ping the Sync API URL");
        }
    }

    function mfn_entity_id_test() {
        var succeeded = false;
        var syncUrl = $('#mfn-wp-plugin-sync_url').val();
        var entityId = $('#mfn-wp-plugin-entity_id').val();

        if (syncUrl === 'https://mfn.se') {
            syncUrl = 'https://widget.datablocks.se/api/rose/proxy/mfn';
        }

        var entityIdTestEl = $('#mfn-entity-id-test');

        if(!syncUrl || syncUrl.trim() === '') {
            $('#mfn-wp-plugin-sync_url').addClass(errorClass);
            entityIdTestEl
                .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Invalid, sync url must be provided");
            return;
        }

        if (entityId.length === 36) {
            succeeded = true;
            entityIdTestEl
                .html("<span class=\"dashicons dashicons-yes mfn-success-icon mfn-do-fade\"></span><span style='cursor:default;' title='" + mfn_admin_params.sync_url  + "'>Valid</span>");
        } else {
            $('#mfn-wp-plugin-entity_id').addClass(errorClass);
            entityIdTestEl
                .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> The Feed ID looks to be empty or malformed ");
        }

        return succeeded;
    }

    function mfn_hub_url_test() {
        var hubUrl = mfn_admin_params.hub_url;
        var hubUrlTestEl =  $('#mfn-hub-url-test');

        if(!hubUrl || hubUrl.trim() === '') {
            $('#mfn-wp-plugin-hub_url').addClass(errorClass);
            hubUrlTestEl.html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Invalid, couldn't determine the hub url");
            return;
        }

        $.get(hubUrl, function (data) {
            if (typeof data === 'string' && data.indexOf('- WebSub hub server, https://www.w3.org/TR/websub') !== -1) {
                hubUrlTestEl
                    .html("<span class=\"dashicons dashicons-yes mfn-success-icon mfn-do-fade\"></span><span style='cursor:default;' title='" + mfn_admin_params.hub_url  + "'><strong>Defaulting to:</strong> (" + mfn_admin_params.hub_url + ")</strong></span>");
                return;
            }
            $('#mfn-wp-plugin-hub_url').addClass(errorClass);
            hubUrlTestEl
                .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Invalid, server does not return pong \n\n - make sure php function <i>file_get_contents</i> is allowed to make http requests \n\n (" + data + ")");
        }).fail(function(err) {
            console.error(err);
            $('#mfn-wp-plugin-hub_url').addClass(errorClass);
            hubUrlTestEl
                .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Ping to " + hubUrl + " url failed " + "(" + err.status + ")");
        });
    }

    function mfn_plugin_url_test() {
        var pluginUrl = mfn_admin_params.plugin_url;
        var pluginUrlTestEl =  $('#mfn-plugin-url-test');

        if(!pluginUrl || pluginUrl.trim() === '') {
            pluginUrlTestEl
                .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Invalid, plugin url must be provided");
            return;
        }

        $.get(pluginUrl + '/cc.php?mode=ping', function (data) {
            if (data === 'pong') {
                pluginUrlTestEl
                    .html("<span class=\"dashicons dashicons-yes mfn-success-icon mfn-do-fade\"></span><span style='cursor:default;' title='" + mfn_admin_params.plugin_url  + "'><strong>Current:</strong> (" + mfn_admin_params.plugin_url + ")</span>");
                return;
            }
            pluginUrlTestEl
                .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Invalid, server does not return pong");
        }).fail(function(err) {
            console.error(err);
            pluginUrlTestEl
                .html("<span class=\"dashicons dashicons-warning mfn-warning-icon mfn-do-fade\"></span> Invalid, address does not seem to be responding with anything " + "(" + err.status + ")");
        });
    }

    function mfn_tests() {
        var testEntityIdSuccess = mfn_entity_id_test();
        mfn_sync_url_test(testEntityIdSuccess);
        mfn_hub_url_test();
        mfn_plugin_url_test();
    }

    function mfn_subscription_error(msg, id) {
        var subStatusEl = $('#' + id);
        subStatusEl.css("flex-basis", "100%");
        subStatusEl.load(statusPageUrl, function() {
            subStatusEl
                .html('<p class="update-nag notice notice-warning inline mfn-do-slide-top"><i class="dashicons dashicons-dismiss mfn-error-icon"></i>' + msg + '</p>');
            $('#mfn-sub-button').attr("disabled", false);
            mfn_tests();
        });
    }

    function mfn_subscription_success(msg, id) {
        var subStatusEl = $('#' + id);
        subStatusEl.css("flex-basis", "initial");
        var mfn_subscribed_html = '<span class=\'mfn-do-fade\'><i class=\'dashicons dashicons-yes mfn-success-icon mfn-do-fade\'></i>' + msg + '</span>';
        subStatusEl
            .html('<div class=\'mfn-status-container mfn-do-fade mfn-highlight-status\'>' + mfn_subscribed_html + '</div>');
        $('#mfn-unsub-button').attr("disabled", false);
        mfn_lock_settings(true);
        mfn_tests();
    }

    function mfn_verify_subscription(pluginUrl, retries, msg = '') {
        var isSubscribed = false;
        var testDelayArr = [4000, 4000, 4000, 2000, 1000];
        var subStatusEl = $('#mfn-subscription-status');
        var subStatusContainerEl = $('#mfn-status-container');

        $.get(pluginUrl + '/cc.php?mode=verify-subscription').done(function (res) {
            // got error from websub verification
            if (msg !== '') {
                mfn_subscription_error(msg, 'mfn-subscription-status');
                return;
            }

            if (!isSubscribed) {
                // verifying state
                subStatusEl.css("flex-basis", "100%");
                subStatusEl
                    .html('<p class="update-nag notice notice-warning inline mfn-do-fade" style="border-left-color: orange;"><i class="dashicons dashicons-hourglass mfn-warning-icon"></i>Awaiting verification...</p>');
            }
            // got subscription
            if (res !== "") {
                setTimeout(function () {
                    subStatusContainerEl.load(statusPageUrl, function() {
                        mfn_subscription_success('Subscribed!', 'mfn-subscription-status');
                    });
                }, 1000);
                return;
            }

            retries--;

            if (retries >= 0) {
                mfn_delay(testDelayArr[retries]).then(function () {
                    mfn_verify_subscription(pluginUrl, retries, msg = '');
                });
            } else { // failed
                mfn_subscription_error('Wasn\'t able to verify the subscription', 'mfn-subscription-status');
            }
        });
    }

    function mfn_subscribe() {
        var pluginUrl = mfn_admin_params.plugin_url;
        var subStatusEl = $('#mfn-subscription-status');
        $('#mfn-sub-button').attr("disabled", true);

        $.get(pluginUrl + '/cc.php?mode=subscribe', function (msg) {
            subStatusEl.html('<div class=\'mfn-do-fade mfn-spinner-container\'><span class=\'mfn-spinner\'></span></div>');
            mfn_verify_subscription(pluginUrl, verificationRetries, msg);
        });
    }

    function mfn_unsubscribe() {
        var subStatusEl = $('#mfn-subscription-status');
        var pluginUrl = mfn_admin_params.plugin_url;
        $('#mfn-unsub-button').attr("disabled", true);
        $('#save-submit-btn').prop("disabled", false);

        $.get(pluginUrl + '/cc.php?mode=unsubscribe', function () {
            subStatusEl
                .html('<div class=\'mfn-do-fade mfn-spinner-container\'><span class=\'mfn-spinner\'></span></div>');

            // reload status section
            setTimeout(function () {
                $('#mfn-status-container')
                    .load(statusPageUrl,
                        function() {
                            var mfn_unsubscribed_html = '<span class=\'mfn-do-fade\'><i class=\'dashicons dashicons-no-alt mfn-unlink-icon mfn-do-fade\'></i>Unsubscribed!</span>';
                            subStatusEl
                                .html('<div class=\'mfn-status-container mfn-do-fade mfn-highlight-status\'>' + mfn_unsubscribed_html + '</div>');
                            $('#mfn-sub-button').attr("disabled", false);
                            mfn_tests();
                            subscriptionId = '';
                            mfn_lock_settings(false);
                        });
            }, 1000);
        });
    }

    function mfn_clear_settings(){
        var pluginUrl = mfn_admin_params.plugin_url;

        $.get(pluginUrl + '/cc.php?mode=clear-settings', function () {
            setTimeout(function () {
                location.reload();
            }, 100);
        });
    }

    function mfn_delete_tags() {
        var dzStatusEl = $('#mfn-danger-zone-status');
        var delTagsBtnEl = $('#mfn-delete-tags-btn');
        var delTagsInputEl = $('#mfn-delete-tags-input');
        var delTagsBtnSpanEl = $('#mfn-delete-tags-btn span');

        delTagsBtnEl.prop('disabled', true);
        delTagsInputEl.prop('disabled', true);

        var pluginUrl = mfn_admin_params.plugin_url;

        dzStatusEl
            .html('<span class="mfn-status-container mfn-status-container-delete mfn-do-fade"><div class=\'mfn-do-fade mfn-spinner-container\'><span class=\'mfn-spinner\'></span></div> <b>Deleting tags...</b></span>');
        delTagsBtnSpanEl.removeClass("dashicons dashicons-tag");
        delTagsBtnSpanEl.html('<div class=\'mfn-do-fade\'><span class=\'mfn-spinner\'></span></div>');
        $.get(pluginUrl + '/cc.php?mode=delete-all-tags&limit=10', function (data) {
            var parts = data.split(';');
            var i = parseInt(parts[0]);
            var deleted = parseInt(parts[1]);

            dzStatusEl
                .html('<span class="mfn-status-container mfn-status-container-delete mfn-do-fade"><div class="mfn-status-container mfn-highlight-status mfn-do-slide-top"><span class=\"dashicons dashicons-yes mfn-do-fade mfn-success-icon\"></span>Action completed!</div>&nbsp;&nbsp; <b>' + deleted + '</b>&nbsp;(of ' + i + ') MFN tags were deleted</span>');
            delTagsBtnEl.prop('disabled', false);
            delTagsInputEl.prop('disabled', false);
            delTagsInputEl.val('')
            delTagsBtnSpanEl.html('');
            delTagsBtnSpanEl.addClass("dashicons dashicons-tag");
        });

    }

    var total = 0;

    async function loadModal(type) {
        var acceptedTypes = ['delete-posts', 'delete-tags', 'clear-settings', 'restore-item'];
        if (acceptedTypes.includes(type)) {

            var modalUrl = mfn_admin_params.plugin_url + '/admin/partials/modals/mfn-modal-' + type + '.php';
            await $.get(modalUrl, function (data) {
                $('body').append(data);
                // modal main events
                $('.mfn-close-modal').click(function() {
                    $(".mfn-modal").remove();
                });
            });
        }
    }

    function mfn_delete_posts(total, includeDirty) {
        total = total || 0;
        includeDirty = includeDirty || false;

        var dzStatusEl = $('#mfn-danger-zone-status');
        var delPostsBtnEl = $('#mfn-delete-posts-btn');
        var delPostsBtnSpanEl = $('#mfn-delete-posts-btn span');

        delPostsBtnEl.prop('disabled', true);
        var pluginUrl = mfn_admin_params.plugin_url;

        $.get(pluginUrl + '/cc.php?mode=delete-all-posts&limit=10&include-dirty=' + includeDirty, function (data) {
            var parts = data.split(';');
            var i = parseInt(parts[0]);
            var deleted = parseInt(parts[1]);
            total += deleted;

            if (i === 10) {
                mfn_delay(100).then(function() {
                    dzStatusEl.html('<b>Deleting...</b>&nbsp;<span class="mfn-status-container mfn-status-container-delete mfn-do-fade">' + total + ' posts ' + '</span>');
                    mfn_delete_posts(total, includeDirty);
                });
                return;
            }

            if(total === 0 || total > 1) {
                dzStatusEl
                    .html('<span class="mfn-status-container mfn-status-container-delete mfn-do-fade"><div class="mfn-status-container mfn-highlight-status mfn-do-slide-top"><span class=\"dashicons dashicons-yes mfn-do-fade mfn-success-icon\"></span>Action completed!</div>&nbsp;&nbsp;<b>' + total + ' </b>&nbsp;MFN posts were deleted');
            } else {
                dzStatusEl
                    .html('<span class="mfn-status-container mfn-status-container-delete mfn-do-fade"><div class="mfn-status-container mfn-highlight-status mfn-do-slide-top"><span class=\"dashicons dashicons-yes mfn-do-fade mfn-success-icon\"></span>Action completed!</div>&nbsp;&nbsp;<b>' + total + ' </b>&nbsp;MFN post was deleted');
            }

            delPostsBtnEl.prop('disabled', false);
            delPostsBtnSpanEl.html('');
            delPostsBtnSpanEl.addClass("dashicons dashicons-admin-post");
        });
    }

    function mfn_sync_all_click() {
        $("#mfn-sync-all").one("click", async function (e) {
            e.stopPropagation();
            disableActionButtons(true);
            // sync taxonomy first
            await syncTax();
            $('#mfn-sync-status').html('');
            mfn_sync(true);
        });
    }
    function mfn_sync_latest_click() {
        $("#mfn-sync-latest").one("click", async function (event) {
            event.stopPropagation();
            disableActionButtons(true);
            // sync taxonomy first
            await syncTax();
            $('.mfn-spinner-container').remove();
            $('#mfn-sync-status').html('');
            mfn_sync(false);
        });
    }
    function mfn_sync_tax_click() {
        $("#mfn-sync-tax").click( function (e) {
            e.stopPropagation();
            disableActionButtons(true);
            syncTax().then(function () {
                disableActionButtons(false);
            });
        });
    }

    function mfn_restore_item() {
        var delay = 1000;
        var post_id = $("#mfn-item-restore-button").attr('data-mfn-post-id');

        $('#mfn-item-restore-status').html("<div class=\'mfn-do-fade mfn-spinner-container\'>Re-syncing ... &nbsp;<span class=\'mfn-spinner\'></span></div>");
        setTimeout(function () {
            mfn_sync_single_item(post_id);
        }, delay);
    }

    function mfn_restore_news_item_click() {
        $('#mfn-item-restore-button').click(async function() {
            await loadModal('restore-item');
            // restore item related modal events
            $('#mfn-item-restore-confirm-button').click(() => {
                $(".mfn-modal").remove();
                mfn_restore_item();
            });
        });
    }

    function mfn_lock_settings(locked) {
        $("#unlock-settings-btn")
            .html("<span class=\"dashicons dashicons-unlock mfn-unlock-icon\"></span> Unlock");
        var keep_locked = ['mfn-wp-plugin-cus_query'];
        var skip_types = ['button', 'submit'];
        $(":input").each(function() {
            $('#' + this.id).removeAttr('readonly');
            $('label').css('pointer-events', 'initial');
            if (keep_locked.includes(this.id)) {
                $('#' + this.id + '').prop('readonly', true);
                this.readonly = true;
            } else {
                if (!skip_types.includes(this.type)) {
                    $('#' + this.id + '').prop('readonly', locked);
                }
            }
        });
    }

    function init() {
        // load status container
        $('#mfn-status-container')
            .load(statusPageUrl,
                function() {
                    mfn_tests();
                });

        mfn_sync_all_click();
        mfn_sync_latest_click();
        mfn_sync_tax_click();
        mfn_restore_news_item_click();

        $("#mfn-sub-button").click(function (e) {
            e.preventDefault();
            mfn_subscribe();
            $('#unlock-settings-btn').prop("disabled", false);
        });

        $("#mfn-unsub-button").click(function (e) {
            e.preventDefault();
            mfn_unsubscribe();
            $('#unlock-settings-btn').prop("disabled", true);
        });

        $("#mfn-clear-settings-btn").click(async function (e) {
            e.preventDefault();
            await loadModal('clear-settings');

            // clear settings specific modal events
            $('#mfn-clear-settings-button').click(function() {
                $(".mfn-modal").remove();
                mfn_clear_settings();
            });
        });

        $("#mfn-delete-posts-btn").click(async function (e) {
            e.preventDefault();
            await loadModal('delete-posts');

            var includeDirty = false;
            var delPostsBtnSpan = $('#mfn-delete-posts-btn span');

            // delete posts specific modal events
            $('#mfn-action-include-modified-posts').click(function () {
                includeDirty = this.checked;
            });

            $('#mfn-delete-posts-button').click(async function() {
                $(".mfn-modal").remove();
                delPostsBtnSpan.removeClass("dashicons dashicons-admin-post");
                delPostsBtnSpan.html('<div class=\'mfn-do-fade\'><span class=\'mfn-spinner\'></span></div>');
                mfn_delete_posts(total, includeDirty);

            });
        });

        $("#mfn-delete-tags-btn").click(async function (e) {
            e.preventDefault();
            await loadModal('delete-tags');

            // delete posts specific modal events
            $('#mfn-delete-tags-button').click(function() {
                $(".mfn-modal").remove();
                mfn_delete_tags();
            });
        });

        $("#unlock-settings-btn").click(function (e) {
            e.preventDefault();
            mfn_lock_settings(false);
            this.disabled = true;
        });

        $("#mfn-unlock-settings-btn-language").click(function (e) {
            e.preventDefault();
            mfn_lock_settings(false)
            $("#" + this.id).html("<span class=\"dashicons dashicons-unlock mfn-unlock-icon\"></span> Unlock");
            this.disabled = true;
            $('#save-submit-btn').prop("disabled", false);
            $("#unlock-settings-btn").prop("disabled", true);
        });

        $('a.mfn-nav-tab').first().addClass('mfn-nav-tab-active');
        $('.mfn-lang-table').first().removeClass('mfn-hide').addClass("mfn-do-fade");

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

            $('.mfn-lang-table').addClass('mfn-hide mfn-do-fade');
            $('.mfn-lang-table-' + lang).removeClass('mfn-hide');
        });
    }

    $(document).ready(init);

})(jQuery);