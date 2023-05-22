<?php
if (!defined('WPINC')) die;


// if process is running
$createPostProcess = new Re_Beehiiv\Import\BackgroundProcess\CreatePost();
$is_processing = $createPostProcess->is_processing();
$is_paused = $createPostProcess->is_paused();
if ( $is_processing ) {
    $last_id = get_option('RE_BEEHIIV_last_check_id', 0);
    $count   = get_option('RE_BEEHIIV_manual_total_items', 0);
    $percent = intval( ( $last_id / $count) * 100);
    echo '<script>re_beehiiv_refresh_manual_import_progress()</script>';
} else {
    $last_id = 0;
    $count = 0;
    $percent = 0;
}


// get all taxonomies based on post type
$post_types = get_post_types( array(
    'public'    => true,
));
$taxonomies = array();
foreach ($post_types as $post_type) {
    if ($post_type == 'attachment') continue;
    $post_type_taxonomies = get_object_taxonomies($post_type, 'objects');
    
    foreach ($post_type_taxonomies as $taxonomy) {
        $taxonomies[$post_type][] = [
            'name' => $taxonomy->name,
            'label' => $taxonomy->label
        ];
    }
}

$taxonomy_terms = array();
foreach ($taxonomies as $post_type => $taxonomy) {
    foreach ($taxonomy as $tax) {
        $terms = get_terms( array(
            'taxonomy' => $tax['name'],
            'hide_empty' => false,
        ));
        $taxonomy_terms[$post_type][$tax['name']] = $terms;
    }
}

?>
<script>
var AllTaxonomies = <?= json_encode($taxonomies) ?>;
var AllTaxonomyTerms = <?= json_encode($taxonomy_terms) ?>;
</script>
<div class="wrap">
    <h1>Re Beehiiv - Import</h1>
    
    <div class="re-beehiiv-wrapper">
        <!-- Select: Content Type -->
        <fieldset>
            <label for="re-beehiiv-content_type"><strong>Content Type: </strong></label>
            <p class="description">What kind of content do you want to import?</p>
            <select name="re-beehiiv-content_type" id="re-beehiiv-content_type">
                <option value="free_web_content">Free</option>
                <option value="premium_web_content">Premium</option>
                <option value="both">Both</option>
            </select>
        </fieldset>

        <fieldset>
            <label><strong>Select Post Type and Taxonomy</strong></label>
            <p class="description">Select the post type and taxonomy you want to import the content to.</p>
            <select name="re-beehiiv-post_type" id="re-beehiiv-post_type" required>
                <option value="0">Select Post Type</option>
                <?php
                foreach ($post_types as $post_type) {
                    if ($post_type == 'attachment') continue;
                    echo '<option value="' . $post_type . '">' . $post_type . '</option>';
                }
                ?>
            </select>
            <select name="re-beehiiv-taxonomy" id="re-beehiiv-taxonomy">
                <option value="0">Select Post Type First</option>
            </select>
            <div id="re-beehiiv-taxonomy_terms">
                <p>Choose a term below.</p>
                <select name="re-beehiiv-taxonomy_term" id="re-beehiiv-taxonomy_term">
                    <option value="0">Select Term</option>
                </select>
            </div>
        </fieldset>

        <div class="beehiiv-toggle-section">
            <h3 onclick="toggleSection(this)">➤ Additional Options</h3>
            <div class="content">
                <fieldset>
                <!-- Add Select box for post status -->
                <label for="re-beehiiv-post_status"><strong>Post Status: </strong></label>
                <p class="description">Select the post status you want to import the content to.</p>
                <select name="re-beehiiv-post_status" id="re-beehiiv-post_status">
                    <?php
                    $post_statuses = get_post_statuses();
                    foreach ($post_statuses as $post_status) {
                        echo '<option value="' . $post_status . '">' . $post_status . '</option>';
                    }
                    ?>
                </select>
                </fieldset>
                <fieldset>
                    <label>
                        <input type="checkbox" name="re-beehiiv-update_existing" id="re-beehiiv-update_existing" value="yes"> Update existing posts
                    </label>
                </fieldset>
            </div>
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
                <div class="re-beehiiv-import-running <?php if (!$is_processing || $is_paused) echo 'hidden' ?>">
                    <p class="description">Import is running. Please wait until it finishes.</p>
                    <button type="button" class="button-secondary" id="re-beehiiv-pause-import" onclick="ChangeImportProgressStatus('pause')">Pause</button>
                    <button type="button" class="button-secondary" id="re-beehiiv-stop-import" onclick="ChangeImportProgressStatus('stop')">Cancel</button>
                </div>
                <div class="re-beehiiv-import-not-running <?php if ($is_processing || $is_paused) echo 'hidden' ?>">
                    <button type="button" class="button-primary <?php if ($is_processing) echo 'hidden' ?>" id="re-beehiiv-start-import">Start</button>
                </div>
                <div class="re-beehiiv-import-paused <?php if (!$is_paused) echo 'hidden' ?>">
                    <button type="button" class="button-primary" id="re-beehiiv-resume-import" onclick="ChangeImportProgressStatus('resume')">Resume</button>
                    <button type="button" class="button-secondary" id="re-beehiiv-stop-import" onclick="ChangeImportProgressStatus('cancel')">Cancel</button>
                </div>
        </div>
    </div>
</div>