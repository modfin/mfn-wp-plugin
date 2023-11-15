<?php
require_once('../../../config.php');

$is_admin = current_user_can('manage_options');

if (!$is_admin) {
    echo "you are not admin";
    die();
}

$subscriptions = get_option("mfn-subscriptions");
$subscription = mfn_get_subscription_by_plugin_url($subscriptions, mfn_plugin_url());
$subscription_id = $subscription['subscription_id'] ?? "";
$subscription_msg = $subscription_id;

if ($subscription_id == "") {
    $subscription_msg = '<strong>' . $subscription_id . '</strong>';
    $subscription_msg .= ' <span class="mfn-status-error"><strong>' . mfn_get_text('status_not_subscribed') . '</strong></span>';
} else {
    $subscription_msg = " <span class='mfn-status-success'><strong>$subscription_id</strong></span>";
}

$posthook_secret = $subscription['posthook_secret'] ?? "";
$posthook_name = $subscription['posthook_name'] ?? "";

echo '
<table class="mfn-status-table">
    <tbody>
        <tr>
            <th>' . mfn_get_text('status_subscription_id') . '</th>
            <td>
                ' . $subscription_msg . '
            </td>
        </tr>
        <tr>
              <th>' . mfn_get_text('status_post_hook_secret') . '</th>
            <td>
                ' . $posthook_secret . '
            </td>
        </tr>
        <tr>
              <th>' . mfn_get_text('status_post_hook_name') . '</th>
            <td>
                ' . $posthook_name . '
            </td>
        </tr>
        <tr>
              <th>' . mfn_get_text('status_sync_url') . '</th>
            <td id="mfn-sync-url-test"></td>
        </tr>
        <tr>
              <th>' . mfn_get_text('status_feed_id') . '</th>
            <td id="mfn-entity-id-test"></td>
        </tr>
        <tr>
              <th>' . mfn_get_text('status_hub_url') . '</th>
            <td id="mfn-hub-url-test"></td>
        </tr>
        <tr>
              <th>' . mfn_get_text('status_plugin_url') . '</th>
            <td id="mfn-plugin-url-test"></td>
        </tr>';
       if ($subscription_id == "") {
           echo '
        <tr>
            <td colspan="2">
                <div class="update-nag notice notice-warning inline mfn-do-slide-top" style="border-left-color: orange;">
                    ' . mfn_get_text('alert_subscribe_warning') . '
                </div>
            </td>
        </tr>';
       }
       echo '
    </tbody>
</table>
<script>
    window.SUBSCRIPTION_ID = "' . $subscription_id . '";
</script>';