<?php
/**
 * Auto Import Page
 *
 * @package Re_Beehiiv
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}


// get all taxonomies based on post type
$post_types = get_post_types(
	array(
		'public' => true,
	)
);
$taxonomies = array();
foreach ( $post_types as $re_post_type ) {
	if ( $re_post_type === 'attachment' ) {
		continue;
	}
	$post_type_taxonomies = get_object_taxonomies( $re_post_type, 'objects' );

	foreach ( $post_type_taxonomies as $re_taxonomy ) {
		if ( $re_taxonomy->public != 1 || $re_taxonomy->hierarchical != 1 ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			continue;
		}
		$taxonomies[ $re_post_type ][] = array(
			'name'  => $re_taxonomy->name,
			'label' => $re_taxonomy->label,
		);
	}
}

$taxonomy_terms = array();
foreach ( $taxonomies as $re_post_type => $re_taxonomy ) {
	foreach ( $re_taxonomy as $re_tax ) {
		$terms                                        = get_terms(
			array(
				'taxonomy'   => $re_tax['name'],
				'hide_empty' => false,
			)
		);
		$taxonomy_terms[ $re_post_type ][ $re_tax['name'] ] = $terms;
	}
}

?>
<script>
var AllTaxonomies = <?php echo wp_json_encode( $taxonomies ); ?>;
var AllTaxonomyTerms = <?php echo wp_json_encode( $taxonomy_terms ); ?>;
</script>
<div class="wrap">
	<h1>Re Beehiiv - Auto Import</h1>
	<div class="re-beehiiv-wrapper">
		<div class="re-beehiiv-tabs">
			<nav class="nav-tab-wrapper">
				<a class="nav-tab" data-tab="re-beehiiv-import" id="re-beehiiv-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=re-beehiiv-import' ) ); ?>">Manual Import</a>
				<a class="nav-tab nav-tab-active" data-tab="re-beehiiv-auto-import" id="re-beehiiv-auto-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=re-beehiiv-import&tab=auto-import' ) ); ?>">Auto Import</a>
			</nav>
		</div>
		<!-- set cron time -->
		<fieldset>
			<label for="re-beehiiv-cron_time"><strong>Cron Time: </strong></label>
			<p class="description">How often do you want to run the cron job?</p>
			<select name="re-beehiiv-cron_time" id="re-beehiiv-cron_time" required>
				<option value="hourly">Hourly</option>
				<option value="twicedaily">Twice Daily</option>
				<option value="daily">Daily</option>
				<option value="weekly">Weekly</option>
			</select>
		</fieldset>
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
				foreach ( $post_types as $re_post_type ) {
					if ( $re_post_type === 'attachment' ) {
						continue;
					}
					echo '<option value="' . esc_attr( $re_post_type ) . '">' . esc_html( $re_post_type ) . '</option>';
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
					<option value="publish">Publish</option>
					<option value="draft">Draft</option>
					<option value="pending">Pending</option>
					<option value="private">Private</option>
				</select>
				</fieldset>
				<fieldset>
					<label>
						<input type="checkbox" name="re-beehiiv-exclude_draft" id="re-beehiiv-exclude_draft" value="yes"> Exclude draft posts
					</label>
					<p class="description">If checked, posts with draft status in Beehiiv will not be imported.</p>
					<label>
						<input type="checkbox" name="re-beehiiv-update_existing" id="re-beehiiv-update_existing" value="yes"> Update existing posts
					</label>
					<p class="description">If checked, posts that have been imported before will be updated.</p>
				</fieldset>
			</div>
		</div>
		<div class="wpfac-card">
			<input type="hidden" name="RE_BEEHIIV_ajax_import-nonce" id="RE_BEEHIIV_ajax_import-nonce" value="<?php echo esc_attr( wp_create_nonce( 'RE_BEEHIIV_ajax_import' ) ); ?>">
			<div class="hidden re-beehiiv-import-running">
				<p class="description">Import is running. Please wait until it finishes.</p>
			</div>
			<div class="re-beehiiv-import-not-running">
				<button type="button" class="button-primary" id="re-beehiiv-auto-import">Start</button>
			</div>
		</div>
	</div>
</div>
