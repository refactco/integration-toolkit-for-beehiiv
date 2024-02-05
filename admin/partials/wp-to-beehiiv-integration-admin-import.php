<?php
/**
 * Import Page
 *
 * @package WP_to_Beehiiv_Integration
 */

use WP_to_Beehiiv_Integration\Import\Manage_Actions;


if ( ! defined( 'WPINC' ) ) {
	die;
}

$re_tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$is_auto = $re_tab === 'auto-import';

$is_canceled = false;
if ( isset( $_GET['cancel'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$is_canceled = true;
}

$is_running = false;
if ( ! $is_auto ) {
	$manual_import_data = get_option( 'wp_to_beehiiv_integration_manual_import_progress', array() );
	$is_running = ! empty( $manual_import_data ) && $manual_import_data['status'] !== 'finished' ? true : false;
}

if ( $is_canceled ) {
	$is_running = false;
	add_action(
		'wp_to_beehiiv_integration_admin_notices',
		function() {
			?>
		<div class="wp-to-beehiiv-integration-import--notice wp-to-beehiiv-integration-import--notice-canceled">
			<h4><?php esc_html_e( 'Importing posts from Beehiiv is cancelled.', 'wp-to-beehiiv-integration' ); ?></h4>
			<p class="description"><?php esc_html_e( 'The import process is cancelled. You can start the import process again.', 'wp-to-beehiiv-integration' ); ?></p>
			<?php require_once WP_TO_BEEHIIV_INTEGRATIONPATH . 'admin/partials/components/progressbar.php'; ?>
		</div>
			<?php
		}
	);

	//delete all rows pending roe from the wp_to_beehiiv_integration_import table
	$manual_import_data = get_option( 'wp_to_beehiiv_integration_manual_import_progress', array() );
	WP_to_Beehiiv_Integration\Import\Import_Table::delete_row_by_group($manual_import_data['group_name']);

	//delete the current manual import option from the database
	delete_option( 'wp_to_beehiiv_integration_manual_import_progress' );
}

if ( $is_running ) {
	$left_items	    = $manual_import_data['posts_progress']['pending_items'] ?? 0;
	$total_items    = $manual_import_data['posts_progress']['total_items'] ?? 0;
	$complete_items = $total_items - $left_items;

	$group_name       = $manual_import_data['group_name'];
	// notice with progress bar
	add_action(
		'wp_to_beehiiv_integration_admin_notices',
		function() use ( $complete_items, $total_items, $group_name ) {
			$cancel_nonce = wp_create_nonce( 'wp_to_beehiiv_integration_cancel_import' );
			$cancel_url   = add_query_arg(
				array(
					'page'   => 'wp-to-beehiiv-integration-import',
					'cancel' => $group_name,
					'nonce'  => $cancel_nonce,
				),
				admin_url( 'admin.php' )
			);
			?>
		<div class="wp-to-beehiiv-integration-import--notice">
			<h4><?php esc_html_e( '🔄 Currently Importing Content from Beehiiv...', 'wp-to-beehiiv-integration' ); ?></h4>
			<p class="description"><?php esc_html_e( 'We\'re actively importing posts from Beehiiv. You can continue with your other tasks or leave this page. We\'ll handle the rest.', 'wp-to-beehiiv-integration' ); ?>
			<br><strong><?php esc_html_e( 'Progress: ', 'wp-to-beehiiv-integration' ); ?><span class="number" id="imported_count"><?php echo $complete_items . '</span> / <span class="number" id="total_count">' . $total_items; ?></span></strong></p>
			<a class="wp-to-beehiiv-integration-button-secondary wp-to-beehiiv-integration-button-cancel" id="wp-to-beehiiv-integration-import--cancel" href="<?php echo esc_url( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 'wp-to-beehiiv-integration' ); ?></a>
			<?php require_once WP_TO_BEEHIIV_INTEGRATIONPATH . 'admin/partials/components/progressbar.php'; ?>
		</div>
			<?php
		}
	);
}


// get all taxonomies based on post type
$post_types = get_post_types(
	array(
		'public' => true,
	),
	'objects'
);


$taxonomies = array();
foreach ( $post_types as $re_post_type ) {
	if ( $re_post_type->name === 'attachment' ) {
		continue;
	}
	$post_type_taxonomies = get_object_taxonomies( $re_post_type->name, 'objects' );

	foreach ( $post_type_taxonomies as $re_taxonomy ) {
		if ( $re_taxonomy->public != 1 || $re_taxonomy->name === 'post_format' ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			continue;
		}
		$taxonomies[ $re_post_type->name ][] = array(
			'name'  => $re_taxonomy->name,
			'label' => $re_taxonomy->label,
		);
	}
}

$taxonomy_terms = array();
foreach ( $taxonomies as $re_post_type => $re_taxonomy ) {
	foreach ( $re_taxonomy as $re_tax ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $re_tax['name'],
				'hide_empty' => false,
			)
		);
		$taxonomy_terms[ $re_post_type ][ $re_tax['name'] ] = $terms;
	}
}

$wp_post_status = get_post_stati( array( 'show_in_admin_all_list' => true ), 'objects' );
$post_statuses  = array();
// Filter post statuses.

foreach ( $wp_post_status as $post_status => $post_status_object ) {
	if ( 'future' === $post_status ) {
		continue;
	}
	$post_statuses[] = array(
		'name'  => $post_status,
		'label' => $post_status_object->label,
	);
}


$default_args         = array(
	'auto'           => 'manual',
	'content_type'   => 'free_web_content',
	'beehiiv-status' => 'confirmed',
	'post_tags'      => '1',
	'post_status'    => array(
		'confirmed' => 'publish',
	),
	'import_method'  => 'new_and_update',
	'cron_time'      => '6',
);
$is_auto_action_exist = false;
if ( $is_auto ) {
	$args                 = Manage_Actions::get_auto_action_args();
	$is_auto_action_exist = ! empty( $args ) ? true : false;

	if ( $is_auto_action_exist ) {
		$args = reset( $args );
	}

	$default_args = wp_parse_args( $args, $default_args );
}
if ( $is_auto_action_exist ) {
	add_action(
		'wp_to_beehiiv_integration_admin_notices',
		function() use ( $args ) {

			if ( isset( $args['taxonomy_term'] ) && isset( $args['taxonomy'] ) ) {
				$term = get_term( $args['taxonomy_term'], $args['taxonomy'] );
			}

			$is_new_item_add         = $args['import_method'] !== 'update';
			$is_existing_item_update = $args['import_method'] !== 'new';
			?>
			<div class="wp-to-beehiiv-integration-import--notice">
				<h4><?php esc_html_e( 'Automated Content Import Activated.', 'wp-to-beehiiv-integration' ); ?></h4>
				<p class="description">
					<?php
					$post_status_str = '';
					foreach ( $args['post_status'] as $status => $post_status ) {
						if ( 'confirmed' === $status ) {
							$status = __( 'published', 'wp-to-beehiiv-integration' );
						}
						$post_status_str .= sprintf(
							// Translators: %1$s: beehiiv post status, %2$s: post status.
							esc_html__( 'a status "%1$s" in Beehiiv will be set to "%2$s"', 'wp-to-beehiiv-integration' ),
							ucwords( $status ),
							ucwords( $post_status )
						);

						// Add comma if not last item.
						if ( end( $args['post_status'] ) !== $post_status ) {
							$post_status_str .= ' and ';
						}
					}

					if ( ! $term instanceof WP_Error ) {
						$update_message='';
						
						switch( $args['import_method'] ) {
							case 'new':
								$update_message = esc_html__( 'new content will be added, but existing posts will remain unaffected.', 'wp-to-beehiiv-integration' );
								break;
							case 'update':
								$update_message = esc_html__( 'existing posts will updated.', 'wp-to-beehiiv-integration' );
								break;
							default:
								$update_message = esc_html__( 'new content will be added, and existing posts will updated', 'wp-to-beehiiv-integration' );
								break;
						}
						echo nl2br(sprintf(
							esc_html__( 'The current configuration will automatically fetch content from Beehiiv every "%1$s" hours. This content will be integrated as WordPress "%2$s" under the "%3$s" taxonomy labeled as "%4$s".

						All incoming content with  %5$s in WordPress. Please note that during this import process, %6$s
						
						Customize the automated import settings below to better match your content requirements.
						', 'wp-to-beehiiv-integration' ),
							'<strong>' . esc_html( $args['cron_time'] ) . '</strong>',
							'<strong>' . esc_html( $args['post_type'] ) . '</strong>',
							'<strong>' . esc_html( $args['taxonomy'] ) . '</strong>', 
							'<strong>' . esc_html( $term->name ) . '</strong>',
							$post_status_str,
							$update_message
						));
					} else {
						// Translators: %1$s: cron time, %2$s: post type, %3$s: post status, %4$s: new item add, %5$s: existing item update.
						echo sprintf( esc_html__( 'Current Auto Import is set to run every "%1$s" hours and will import to "%2$s" post type. %3$s. The new items will %4$s imported and the Existing posts will %5$s updated. You can modify these settings below to customize the automatic import process to your needs.', 'wp-to-beehiiv-integration' ), '<strong>' . esc_html( $args['cron_time'] ) . '</strong>', '<strong>' . esc_html( $args['post_type'] ) . '</strong>', $post_status_str, ( $is_new_item_add === true ? esc_html__( 'be', 'wp-to-beehiiv-integration' ) : esc_html__( 'not be', 'wp-to-beehiiv-integration' ) ), ( $is_existing_item_update === true ? esc_html__( 'be', 'wp-to-beehiiv-integration' ) : esc_html__( 'not be', 'wp-to-beehiiv-integration' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					}
					?>
				</p>
			</div>
			<?php
		}
	);
}
$import_title = $is_auto ? __( 'Auto', 'wp-to-beehiiv-integration' ) : __( 'Manual', 'wp-to-beehiiv-integration' );
?>
<script>
var AllTaxonomies = <?php echo wp_json_encode( $taxonomies ); ?>;
var AllTaxonomyTerms = <?php echo wp_json_encode( $taxonomy_terms ); ?>;
var AllPostStatuses = <?php echo wp_json_encode( $post_statuses ); ?>;
var AllDefaultArgs = <?php echo wp_json_encode( $default_args ); ?>;
</script>
<div class="wp-to-beehiiv-integration-wrap">


	<?php require_once 'components/header.php'; ?>
	<div class="wp-to-beehiiv-integration-heading">
		<h1>
		<?php
		esc_html_e( 'Import Content', 'wp-to-beehiiv-integration' );
		?>
		</h1>

					<p>
						<?php esc_html_e( 'Choose how to import content from Beehiiv to your WordPress Site.', 'wp-to-beehiiv-integration' ); ?>
						<br>
						<?php 
							if ( !$is_auto ) {
								esc_html_e( 'This feature allows you to pull content from Beehiiv and publish it on your WordPress website.', 'wp-to-beehiiv-integration' ); 
							} else {
								esc_html_e( 'Set up an automatic process to periodically fetch and integrate content from Beehiiv into your WordPress website.' ); 
							}
						?>
					</p>	
	</div>

	<div class="wp-to-beehiiv-integration-tabs">
		<nav class="nav-tab-wrapper">
			<a class="re-nav-tab <?php echo $is_auto ? '' : 're-nav-tab-active'; ?>" data-tab="wp-to-beehiiv-integration-import" id="wp-to-beehiiv-integration-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-to-beehiiv-integration-import' ) ); ?>"><?php esc_html_e( 'Manual Import', 'wp-to-beehiiv-integration' ); ?></a>
			<a class="re-nav-tab <?php echo $is_auto ? 're-nav-tab-active' : ''; ?>" data-tab="wp-to-beehiiv-integration-auto-import" id="wp-to-beehiiv-integration-auto-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-to-beehiiv-integration-import&tab=auto-import' ) ); ?>"><?php esc_html_e( 'Auto Import', 'wp-to-beehiiv-integration' ); ?></a>
		</nav>
	</div>

	<div class="wp-to-beehiiv-integration-wrapper border-t-0">
		<div class="wp-to-beehiiv-integration-import--notices <?php echo ! $is_running && $is_canceled ? 'wp-to-beehiiv-integration-import-form--hide_canceled' : ''; ?>" id="wp-to-beehiiv-integration-import--notices">
			<div class="hidden wp-to-beehiiv-integration-import--notice wp-to-beehiiv-integration-import--notice-error">
				<h4><?php esc_html_e( 'Please fix the following errors:', 'wp-to-beehiiv-integration' ); ?></h4>
				<ul>
				</ul>
			</div>
			<?php do_action( 'wp_to_beehiiv_integration_admin_notices' ); ?>
			<!-- convert notice above to new format -->
		</div>
		<?php if ( ! $is_running || ( $is_canceled && ! $is_running ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wp-to-beehiiv-integration-import-form" class="wp-to-beehiiv-integration-import-form">
			<div class="wp-to-beehiiv-integration-import-fields">
				<div class="wp-to-beehiiv-integration-import-fields--step import-fields--step1 <?php echo ! $is_auto_action_exist ? 'active' : ''; ?>">
					<h2 class="wp-to-beehiiv-integration-import-fields--step--title" data-error-count="0"><?php esc_html_e( 'Step1: Choose data from Beehiiv', 'wp-to-beehiiv-integration' ); ?></h2>
					<div class="wp-to-beehiiv-integration-import-fields--step--content">
						<fieldset>
							<label for="wp-to-beehiiv-integration-content_type" class="pr-2"><strong><?php esc_html_e( 'Content Type', 'wp-to-beehiiv-integration' ); ?></strong>
							<small id="step1_content_type">
							   <i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
							</small>
						</label>
							<?php
							$content_types = array(
								'free_web_content'    => __( 'Free', 'wp-to-beehiiv-integration' ),
								'premium_web_content' => __( 'Premium', 'wp-to-beehiiv-integration' ),
							);

							foreach ( $content_types as $content_type => $label ) {
								if ( is_array( $default_args['content_type'] ) ) {
									$checked = in_array( $content_type, $default_args['content_type'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['content_type'] === $content_type ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block" >
									<input type="checkbox" name="wp-to-beehiiv-integration-content_type[]" id="wp-to-beehiiv-integration-content_type" value="<?php echo esc_attr( $content_type ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Select the type of content you want to import.', 'wp-to-beehiiv-integration' ); ?></p>
						</fieldset>

						<fieldset>
							<label for="wp-to-beehiiv-integration-beehiiv-status[]" class="pr-2">
								<strong><?php esc_html_e( 'Post Status', 'wp-to-beehiiv-integration' ); ?></strong>
								<small id="step1_post_status">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<?php
							$beehiiv_statuses = array(
								'confirmed' => __( 'Published', 'wp-to-beehiiv-integration' ),
								'archived'  => __( 'Archived', 'wp-to-beehiiv-integration' ),
								'draft'     => __( 'Draft', 'wp-to-beehiiv-integration' ),
							);

							foreach ( $beehiiv_statuses as $beehiiv_status => $label ) {
								if ( is_array( $default_args['beehiiv-status'] ) ) {
									$checked = in_array( $beehiiv_status, $default_args['beehiiv-status'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['beehiiv-status'] === $beehiiv_status ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block">
									<input type="checkbox" name="wp-to-beehiiv-integration-beehiiv-status[]" id="wp-to-beehiiv-integration-beehiiv-status"  value="<?php echo esc_attr( $beehiiv_status ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Choose the status of the content you want to import.', 'wp-to-beehiiv-integration' ); ?></p>
						</fieldset>
					</div>
				</div>
				<div class="wp-to-beehiiv-integration-import-fields--step import-fields--step2" data-error-count="0">
					<h2 class="wp-to-beehiiv-integration-import-fields--step--title" data-error-count="0"><?php esc_html_e( 'Step 2: Import data to WordPress', 'wp-to-beehiiv-integration' ); ?></h2>
					<div class="wp-to-beehiiv-integration-import-fields--step--content">
						<fieldset>
							<label class="d-block" for="wp-to-beehiiv-integration-post_type">
								<strong><?php esc_html_e( 'Select Post Type and Taxonomy', 'wp-to-beehiiv-integration' ); ?></strong>
								<small id="step2_post_type">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="wp-to-beehiiv-integration-post_type" id="wp-to-beehiiv-integration-post_type" required>
								<option value="0"><?php esc_html_e( 'Select Post Type', 'wp-to-beehiiv-integration' ); ?></option>
								<?php
								foreach ( $post_types as $re_post_type ) {
									if ( $re_post_type->name === 'attachment' ) {
										continue;
									}
									echo '<option value="' . esc_attr( $re_post_type->name ) . '">' . esc_html( $re_post_type->labels->singular_name ) . '</option>';
								}
								?>
							</select>
							<select name="wp-to-beehiiv-integration-taxonomy" id="wp-to-beehiiv-integration-taxonomy" class="hidden wp-to-beehiiv-integration-taxonomy" required>
								<option value="0"><?php esc_html_e( 'Select Post Type First', 'wp-to-beehiiv-integration' ); ?></option>
							</select>
							<select name="wp-to-beehiiv-integration-taxonomy_term" id="wp-to-beehiiv-integration-taxonomy_term" class="hidden wp-to-beehiiv-integration-taxonomy_term" required>
								<option value="0"><?php esc_html_e( 'Select Term', 'wp-to-beehiiv-integration' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Choose the post type and taxonomy for the imported content.', 'wp-to-beehiiv-integration' ); ?></p>
						</fieldset>
						<fieldset>
							<label for="wp-to-beehiiv-integration-post_author" class="d-block">
								<strong><?php esc_html_e( 'Content Author', 'wp-to-beehiiv-integration' ); ?></strong>
								<small id="step2_post_author">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="wp-to-beehiiv-integration-post_author" id="wp-to-beehiiv-integration-post_author" required>
								<option value="0"><?php esc_html_e( 'Select Author', 'wp-to-beehiiv-integration' ); ?></option>
								<?php
								$authors = get_users( array( 'role__in' => array( 'author', 'editor', 'administrator' ) ) );
								foreach ( $authors as $author ) {
									echo '<option value="' . esc_attr( $author->ID ) . '">' . esc_html( $author->display_name ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Assign the imported posts to a specific user.', 'wp-to-beehiiv-integration' ); ?></p>
						</fieldset>
						<fieldset>
							<label for="wp-to-beehiiv-integration-post_tags">
								<strong><?php esc_html_e( 'Beehiiv Tags', 'wp-to-beehiiv-integration' ); ?></strong>
								<small id="step2_post_tags">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="wp-to-beehiiv-integration-post_tags-taxonomy" id="wp-to-beehiiv-integration-post_tags-taxonomy" class="wp-to-beehiiv-integration-post_tags-taxonomy">
								<option value="0"><?php esc_html_e( 'Select post type first', 'wp-to-beehiiv-integration' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'To import tags from Beehiiv, select the taxonomy and term you want to use for the imported tags.', 'wp-to-beehiiv-integration' ); ?></p>
						</fieldset>
						<fieldset id="wp-to-beehiiv-integration-post_status">
							<label for="wp-to-beehiiv-integration-post_status">
								<strong><?php esc_html_e( 'Post Status', 'wp-to-beehiiv-integration' ); ?></strong>
								<small id="step2_post_status">
									<i claversion-1.0.0ss="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<div class="wp-to-beehiiv-integration-post_status--fields"></div>
							<p class="description"><?php esc_html_e( 'Assign Post Status to the imported content for each Post Status selected in step 1.', 'wp-to-beehiiv-integration' ); ?></p>
						</fieldset>

						<fieldset>
							<label for="wp-to-beehiiv-integration-import_method"><strong><?php esc_html_e( 'Import Option', 'wp-to-beehiiv-integration' ); ?></strong>
								<small id="step2_import_method">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<?php
							$import_methods = array(
								'new'            => __( 'Import new items', 'wp-to-beehiiv-integration' ),
								'update'         => __( 'Update existing items', 'wp-to-beehiiv-integration' ),
								'new_and_update' => __( 'Do both', 'wp-to-beehiiv-integration' ),
							);

							foreach ( $import_methods as $import_method => $label ) {
								if ( is_array( $default_args['import_method'] ) ) {
									$checked = in_array( $import_method, $default_args['import_method'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['import_method'] === $import_method ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block">
									<input type="radio" name="wp-to-beehiiv-integration-import_method" id="wp-to-beehiiv-integration-import_method" value="<?php echo esc_attr( $import_method ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Choose the desired action for importing data.', 'wp-to-beehiiv-integration' ); ?></p>
						</fieldset>
						<?php if ( $is_auto ) : ?>
							<fieldset>
								<label for="wp-to-beehiiv-integration-cron_time" class="d-block">
									<strong><?php esc_html_e( 'Import Schedule', 'wp-to-beehiiv-integration' ); ?></strong>
									<small id="step2_cron_time">
										<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
									</small>
						    	</label>
								<input type="number" name="wp-to-beehiiv-integration-cron_time" id="wp-to-beehiiv-integration-cron_time" value="<?php echo esc_attr( $default_args['cron_time'] ); ?>" min="1" required placeholder="<?php esc_attr_e( 'Enter interval in hours', 'wp-to-beehiiv-integration' ); ?>"> Hour(s)
									<p class="description"><?php esc_html_e( 'Enter the desired time intervals in hours and set the frequency of auto imports from your Beehiiv to your WordPress site.', 'wp-to-beehiiv-integration' ); ?></p>
							</fieldset>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<input type="hidden" name="action" value="<?php echo $is_auto ? 'wp_to_beehiiv_integration_auto_import' : 'wp_to_beehiiv_integration_manual_import'; ?>">
			<input type="hidden" name="wp_to_beehiiv_integration_import_nonce" id="wp_to_beehiiv_integration_import_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wp_to_beehiiv_integration_import_nonce' ) ); ?>">
			<?php
			$disabled = $is_running && ! $is_auto ? 'disabled' : '';
			if ( $disabled ) {
				echo '<p>';
				esc_html_e( 'It is not possible to initiate another manual import while the current one is still in progress. refresh the page to update the status.', 'wp-to-beehiiv-integration' );
				echo '</p>';
			}
			$submit_text = $is_auto ? __( 'Save', 'wp-to-beehiiv-integration' ) : __( 'Start Import', 'wp-to-beehiiv-integration' );
			submit_button( $submit_text, 'primary components-button is-primary', 'wp-to-beehiiv-integration-start-import', false, $disabled );
			?>
		</form>
		<?php endif; ?>
	</div>
</div>
