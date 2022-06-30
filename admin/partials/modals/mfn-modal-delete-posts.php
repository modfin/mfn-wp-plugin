<?php
require_once('../../../config.php');

$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);

$status = new stdClass;
$status->id = 'mfn-action-include-modified-posts';
$status->msg = mfn_get_text("action_include_modified_posts");

$actions = array(
    'stat1' => $status,
);

$posts_status = mfn_fetch_posts_status();
$num_total = $posts_status[0] ?? 0;
$num_modified = $posts_status[1] ?? 0;
$num_trash = $posts_status[2] ?? 0;
$is_disabled = $num_total === 0 ? 'disabled' : '';

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
            <span class="dashicons dashicons-admin-post mfn-clear-posts-icon mfn-do-fade"></span>
            Delete all MFN Posts
            </h2>
            <span class="mfn-close-dialog mfn-close-modal">&times;</span>
        </div>
        <div class="mfn-modal-dialog-body">
            <h4>Are you sure that you want to delete all MFN Posts?</h4>
            <p class="mfn-modal-dialog-body-info mfn-do-slide-top">
                Total number of MFN posts: <b>' . $num_total . '</b> <i>Modified: ' . $num_modified . '</i> <i>In trash: ' . $num_trash . '</i>
            </p>
        </div>
        <div class="mfn-modal-dialog-footer">
            <div class="mfn-modal-dialog-buttons-container">
                <span class="mfn-modal-dialog-actions">';
                if ($num_modified > 0 || $num_trash > 0) {
                    foreach ($actions as $key => $value) {
                        // modal action
                        echo '
                                    <div class="mfn-checkbox-container">
                                        <input class="mfn-checkbox-input" id="' . $value->id  . '" type="checkbox" ' . $is_disabled . '/>
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
                } else {
                    echo mfn_get_text('modal_explanation_delete_posts');
                }
                echo '

                </span>
                <span>
                    <button class="button mfn-cancel-button mfn-close-modal">Cancel</button>
                </span>
                <span>
                    <button id="mfn-delete-posts-button" class="button button-primary" ' . $is_disabled . '>Delete</button>
                </span>
            </div>
        </div>
    </div>
</div>';