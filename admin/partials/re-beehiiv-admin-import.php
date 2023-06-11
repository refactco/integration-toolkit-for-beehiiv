<?php
/**
 * Import Page
 *
 * @package Re_Beehiiv
 */
use Re_Beehiiv\Import\Manage_Actions;


if ( ! defined( 'WPINC' ) ) {
	die;
}
$group_name = get_transient( 'RE_BEEHIIV_manual_import_group' );
$is_running = get_transient( 'RE_BEEHIIV_manual_import_running' );
if (!$is_running && isset($_GET['status'])) {
	$status = sanitize_text_field($_GET['status']);
	if ( $status === 'started' ) {
		$is_running = true;
	}
}

$re_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$is_auto = $re_tab === 'auto-import';


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


$default_args = array(
	'auto'	=> 'manual',
	'content_type' 		=> 'free_web_content',
	'beehiiv-status'	=> 'confirmed',
	'post_tags'			=> '1',
	'post_status' 		=> 'publish',
	'import_method'		=> 'new_and_update',
	'import_interval'	=> '12',
	'cron_time'	=> '1',
);
$is_auto_action_exist = false;
if ($is_auto) {
	$args = Manage_Actions::get_auto_action_args();
	$is_auto_action_exist = ! empty( $args ) ? true : false;

	if ( $is_auto_action_exist ) {
		$args = reset( $args );
	}

	$default_args = wp_parse_args( $args, $default_args );
}
if ( $is_auto_action_exist ) {
	add_action( 're_beehiiv_admin_notices', function() use ( $args ) {
		
			$term = get_term( $args['taxonomy_term'], $args['taxonomy'] );
			$is_new_item_add 		 = $args['import_method'] !== 'update';
			$is_existing_item_update = $args['import_method'] !== 'new';
			?>
			<div class="re-beehiiv-import--notice">
				<h4>Auto Import is set</h4>
				<p class="description">Current Auto Import will run <strong><?php echo esc_html( $args['cron_time'] ); ?></strong> and will import to <strong><?php echo esc_html( $args['post_type'] ); ?></strong> post type and <strong><?php echo esc_html( $args['taxonomy'] ); ?></strong> taxonomy with <strong><?php echo esc_html( $term->name ); ?></strong> term. The default post status is <strong><?php echo esc_html( $args['post_status'] ); ?></strong>. The new items will <strong><?php echo $is_new_item_add ? 'be' : 'not be'; ?></strong> imported and the Existing posts will <strong><?php echo $is_existing_item_update ? ' be' : 'not be'; ?></strong> updated. You can change the settings below.</p>
			</div>
			<?php
	} );
}

?>
<script>
var AllTaxonomies = <?php echo json_encode( $taxonomies ); ?>;
var AllTaxonomyTerms = <?php echo json_encode( $taxonomy_terms ); ?>;
</script>
<div class="re-beehiiv-wrap">


	<?php require_once 'components/header.php'; ?>
	<div class="re-beehiiv-heading">
		<h1>Re/Beehiiv - Manual Import</h1>
		<p>Perform the import operation manually</p>
	</div>

	<div class="re-beehiiv-tabs">
		<nav class="nav-tab-wrapper">
			<a class="re-nav-tab <?php echo $is_auto ? '' : 're-nav-tab-active' ?>" data-tab="re-beehiiv-import" id="re-beehiiv-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=re-beehiiv-import' ) ); ?>">Manual Import</a>
			<a class="re-nav-tab <?php echo $is_auto ? 're-nav-tab-active' : '' ?>" data-tab="re-beehiiv-auto-import" id="re-beehiiv-auto-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=re-beehiiv-import&tab=auto-import' ) ); ?>">Auto Import</a>
		</nav>
	</div>

	<div class="re-beehiiv-wrapper">
		<div class="re-beehiiv-import--notices" id="re-beehiiv-import--notices">
			<div class="hidden re-beehiiv-import--notice re-beehiiv-import--notice-error">
				<h4>Please fix the following errors:</h4>
				<ul>
				</ul>
			</div>
			<?php do_action( 're_beehiiv_admin_notices' ); ?>
			<!-- convert notice above to new format -->
		</div>
		<?php if ( $is_auto || !$is_running ) : ?>
		<form method="post" action="<?php echo admin_url('admin-post.php') ?>" id="re-beehiiv-import-form" class="re-beehiiv-import-form">
			<div class="re-beehiiv-import-fields">
				<div class="re-beehiiv-import-fields--step import-fields--step1 <?php echo !$is_auto_action_exist ? 'active' : '' ?>">
					<h2 class="re-beehiiv-import-fields--step--title">Step 1: Select Content from Beehiiv</h2>
					<div class="re-beehiiv-import-fields--step--content">
						<fieldset>
							<label for="re-beehiiv-content_type" class="pr-2"><strong>Content Type </strong></label>
							<?php
							$content_types = array(
								'free_web_content'    => 'Free',
								'premium_web_content' => 'Premium',
							);

							foreach ( $content_types as $content_type => $label ) {
								if ( is_array( $default_args['content_type'] ) ) {
									$checked = in_array( $content_type, $default_args['content_type'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['content_type'] === $content_type ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block" >
									<input type="checkbox" name="re-beehiiv-content_type[]" id="re-beehiiv-content_type" value="<?php echo esc_attr( $content_type ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description">What kind of content do you want to import?</p>
						</fieldset>

						<fieldset>
							<label for="re-beehiiv-beehiiv-status[]" class="pr-2"><strong>Post Status on Beehiiv </strong></label>
							<?php
							$beehiiv_statuses = array(
								'confirmed' => 'Published',
								'archived'  => 'Archived',
								'draft'     => 'Draft',
							);

							foreach ( $beehiiv_statuses as $beehiiv_status => $label ) {
								if ( is_array( $default_args['beehiiv-status'] ) ) {
									$checked = in_array( $beehiiv_status, $default_args['beehiiv-status'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['beehiiv-status'] === $beehiiv_status ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block">
									<input type="checkbox" name="re-beehiiv-beehiiv-status[]" id="re-beehiiv-beehiiv-status"  value="<?php echo esc_attr( $beehiiv_status ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description">Select the status of the content you want to import.</p>
						</fieldset>
					</div>
				</div>
				<div class="re-beehiiv-import-fields--step import-fields--step2">
					<h2 class="re-beehiiv-import-fields--step--title">Step 2: Setup Post type and attributes</h2>
					<div class="re-beehiiv-import-fields--step--content">
						<fieldset>
							<label class="d-block" for="re-beehiiv-post_type"><strong>Select Post Type and Taxonomy</strong></label>
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
							<select name="re-beehiiv-taxonomy" id="re-beehiiv-taxonomy" class="hidden re-beehiiv-taxonomy" required>
								<option value="0">Select Post Type First</option>
							</select>
							<select name="re-beehiiv-taxonomy_term" id="re-beehiiv-taxonomy_term" class="hidden re-beehiiv-taxonomy_term" required>
								<option value="0">Select Term</option>
							</select>
							<p class="description">Select the post type and taxonomy you want to import the content to.</p>
						</fieldset>
						<fieldset>
							<label for="re-beehiiv-post_author" class="d-block"><strong>Post Author </strong></label>
							<select name="re-beehiiv-post_author" id="re-beehiiv-post_author" required>
								<option value="0">Select Author</option>
								<?php
								$authors = get_users( array( 'role__in' => array( 'author', 'editor', 'administrator' ) ) );
								foreach ( $authors as $author ) {
									echo '<option value="' . esc_attr( $author->ID ) . '">' . esc_html( $author->display_name ) . '</option>';
								}
								?>
							</select>
							<p class="description">The posts being imported will be assigned to this user.</p>
						</fieldset>
						<fieldset>
							<label for="re-beehiiv-post_tags"><strong>Post Tags </strong></label>
							<label class="pr-2 d-block">
								<input type="checkbox" name="re-beehiiv-post_tags" id="re-beehiiv-post_tags" value="1" <?php echo ( '1' === $default_args['post_tags'] ) ? 'checked' : '' ?>> Import Tags
							</label>
							<p class="description">If checked, the tags will be imported as post tags.</p>
						</fieldset>
						<fieldset>
							<label for="re-beehiiv-post_status"><strong>Post Status </strong></label>
							<?php
							$post_statuses = array(
								'publish' => 'Publish',
								'draft' => 'Draft',
								'pending' => 'Pending',
								'private' => 'Private',
							);

							foreach ( $post_statuses as $post_status => $post_status_name ) {
								if ( is_array( $default_args['post_status'] ) ) {
									$checked = in_array( $post_status, $default_args['post_status'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['post_status'] === $post_status ) ? 'checked' : '';
								}
								echo '<label class="pr-2 d-block">';
								echo '<input type="radio" name="re-beehiiv-post_status" id="re-beehiiv-post_status" value="' . esc_attr( $post_status ) . '" ' . esc_attr( $checked ) . '> ' . esc_html( $post_status_name );
								echo '</label>';
							}
							?>
							<p class="description">Select the post status you want to import the content to.</p>
						</fieldset>
					</div>
				</div>
				<div class="re-beehiiv-import-fields--step import-fields--step3">
					<h2 class="re-beehiiv-import-fields--step--title">Step 3: Import Options</h2>
					<div class="re-beehiiv-import-fields--step--content">
						<fieldset>
							<label for="re-beehiiv-import_method"><strong>Import Method </strong></label>
							<?php
							$import_methods = array(
								'new'          => 'Import New Items',
								'update'       => 'Update Existing Items',
								'new_and_update' => 'Import New Items and Update Existing',
							);

							foreach ( $import_methods as $import_method => $label ) {
								if ( is_array( $default_args['import_method'] ) ) {
									$checked = in_array( $import_method, $default_args['import_method'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['import_method'] === $import_method ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block">
									<input type="radio" name="re-beehiiv-import_method" id="re-beehiiv-import_method" value="<?php echo esc_attr( $import_method ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description">Choose the desired action for importing data using this form. Options include importing new items only, updating existing items only, or performing both actions simultaneously.</p>
						</fieldset>
						<?php if ($is_auto) : ?>
							<fieldset>
								<label for="re-beehiiv-cron_time" class="d-block"><strong>Import Schedule </strong></label>
								<input type="number" name="re-beehiiv-cron_time" id="re-beehiiv-cron_time" value="<?php echo esc_attr( $default_args['cron_time'] ); ?>" min="1" required>
									<p class="description">This field allows you to set the schedule for automatic imports from Beehiiv. Enter the number of hours between each import, for example, '6' for an import every 6 hours.</p>
							</fieldset>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<input type="hidden" name="action" value="<?php echo $is_auto ? 're_beehiiv_auto_import' : 're_beehiiv_manual_import'; ?>">
			<input type="hidden" name="re_beehiiv_import_nonce" id="re_beehiiv_import_nonce" value="<?php echo esc_attr( wp_create_nonce( 're_beehiiv_import_nonce' ) ); ?>">
			<?php
			$disabled = $is_running && !$is_auto ? 'disabled' : '';
			if ( $disabled ) {
				echo '<p>It is not possible to initiate another manual import while the current one is still in progress. refresh the page to update the status.</p>';
			}
			$submit_text = $is_auto ? 'Save' : 'Start Import';
			submit_button( $submit_text, 'primary components-button is-primary', 're-beehiiv-start-import', false, $disabled );
			?>
		</form>
		<?php endif; ?>
	</div>
</div>
