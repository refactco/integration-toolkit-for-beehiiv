<?php
if (!defined('WPINC')) die;

$last_id = (int) get_option('RE_BEEHIIV_ajax_last_check_id', false);
$count = (int) get_option('RE_BEEHIIV_ajax_all_recurly_accounts', 0);
$percent=($count!=0)?intval($last_id / $count * 100):0;

if (isset($_GET['delete']) and $_GET['delete'] == 'true') {   
    // delete all options
    delete_option('RE_BEEHIIV_ajax_last_check_id');
    delete_option('RE_BEEHIIV_ajax_all_recurly_accounts');
    delete_transient('RE_BEEHIIV_get_all_recurly_accounts');
}
?>
<div class="wrap">
    <h1>WP AJAX Requests</h1>
    
    <div class="wp-faculty-wrapper">
        <div class="wpfac-card">
            <select name="wp-faculty-content_type" id="wp-faculty-content_type">
                <option value="free_web_content">Free</option>
                <option value="premium_web_content">Premium</option>
            </select>
        </div>
        <div class="wpfac-card">
            <select name="wp-faculty-category" id="wp-faculty-category">
                <option value="0">Select Category</option>
                <?php
                $categories = get_categories( array(
                    'hide_empty'    => false,
                ));
                foreach ($categories as $category) {
                    echo '<option value="' . $category->term_id . '">' . $category->name . '</option>';
                }
                ?>
            </select>
        </div>
        <div id="wp-faculty-progress">
            <div class="cssProgress">
                <div class="progress3">
                    <div class="cssProgress-bar cssProgress-success" style="width: <?= $percent ?>%;">
                        <span class="cssProgress-label">(<?= $last_id ?> / <?= $count ?>) <?= $percent ?>%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="wpfac-card">
            <input type="hidden" name="RE_BEEHIIV_ajax_import-nonce" id="RE_BEEHIIV_ajax_import-nonce" value="<?= wp_create_nonce('RE_BEEHIIV_ajax_import') ?>">
            <?php if ($last_id == 0 or $last_id == $count) { ?>
                <button type="button" class="button-primary" id="wp-faculty-start-import">Start</button>
            <?php } else { ?>
                <button type="button" class="button-primary" id="wp-faculty-start-import">Continue</button>
            <?php } ?>
            <a href="<?= admin_url('admin.php?page=re-beehiiv-import') ?>" class="button-secondary" id="wp-faculty-pause-import" style="display:none;">Pause</a>
        </div>
    </div>
</div>