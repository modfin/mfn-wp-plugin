<?php
require_once('../../../config.php');

$is_admin = current_user_can('manage_options');

if (!$is_admin) {
    echo "you are not admin";
    die();
}

$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);

$actions = null;

$body = mfn_get_text('modal_restore_body');
$heading = mfn_get_text('modal_restore_heading');
$warning = mfn_get_text('alert_restore_warning');

// modal
echo '
<svg class="mfn-checkbox-symbol">
  <symbol id="check" viewbox="0 0 12 10">
    <polyline
        points="1.5 6 4.5 9 10.5 1"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
    ></polyline>
  </symbol>
</svg>
<div class="mfn-modal mfn-do-fade" id="mfn-modal">
    <div class="mfn-modal-dialog">
        <div class="mfn-modal-dialog-header">
            <h2>
                <span class="dashicons dashicons-undo mfn-clear-settings-icon mfn-do-fade"></span>
                ' . $heading . '
            </h2>
            <span class="mfn-close-dialog mfn-close-modal">&times;</span>
        </div>
        <div class="mfn-modal-dialog-body">
            <h4>' . $body . '</h4>

        </div>
        <div class="mfn-modal-dialog-footer">
            <div class="mfn-modal-dialog-buttons-container">
                <span class="mfn-modal-dialog-actions">';
                if (is_array($actions)) {
                    foreach ($actions as $key => $value) {
                        // modal action
                        echo '
                                    <div class="mfn-checkbox-container">
                                        <input class="mfn-checkbox-input" id="' . $value->id  . '" type="checkbox" />
                                        <label class="mfn-checkbox" for=' . $value->id  . '>
                                                <span>
                                                  <svg width="12px" height="10px">
                                                    <use xlink:href="#check"></use>
                                                  </svg>
                                                </span>
                                            <span>' . $value->msg . '</span>
                                        </label>
                                    </div>
                        ';
                    }
                }
                echo $warning . '
                </span>
                <span>
                    <button class="button mfn-cancel-button mfn-close-modal">Cancel</button>
                </span>
                <span>
                    <button id="mfn-item-restore-confirm-button" class="button button-primary">Restore</button>
                </span>
            </div>
        </div>
    </div>
</div>';