<?php
if (!defined('WPINC')) die;


// if process is running
$createPostProcess = new Re_Beehiiv\BackgroundProcess\CreatePost();

if ($createPostProcess->is_processing()) {
    $last_id = get_option('RE_BEEHIIV_last_check_id', false);
    $count   = get_option('RE_BEEHIIV_manual_total_items', false);
    $percent = intval( ( $last_id / $count) * 100);
    echo '<script>re_beehiiv_refresh_manual_import_progress()</script>';
} else {
    $last_id = 0;
    $count = 0;
    $percent = 0;
}

?>
<div class="wrap">
    <h1>Re Beehiiv - Import</h1>
    
    <div class="re-beehiiv-wrapper">
        <p>Import all posts from Beehiiv</p>
        <div class="wpfac-card">
            <label for="re-beehiiv-content_type">Content Type: </label>
            <select name="re-beehiiv-content_type" id="re-beehiiv-content_type">
                <option value="both">Both</option>
                <option value="free_web_content">Free</option>
                <option value="premium_web_content">Premium</option>
            </select>
        </div>
        <div class="wpfac-card">
            <label for="re-beehiiv-category">Category: </label>
            <select name="re-beehiiv-category" id="re-beehiiv-category">
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
        <div id="re-beehiiv-progress">
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
                <button type="button" class="button-primary" id="re-beehiiv-start-import">Start</button>
            <?php } else { ?>
                <button type="button" class="button-primary" id="re-beehiiv-start-import">Continue</button>
            <?php } ?>
            <a href="<?= admin_url('admin.php?page=re-beehiiv-import') ?>" class="button-secondary" id="re-beehiiv-pause-import" style="display:none;">Pause</a>
        </div>
    </div>
</div>