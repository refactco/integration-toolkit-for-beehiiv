<?php
/**
 * Import Page
 *
 * @package Integration_Toolkit_For_Beehiiv
 */

use Integration_Toolkit_For_Beehiiv\Import\Manage_Actions;


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
	$manual_import_data = get_option( 'integration_toolkit_for_beehiiv_manual_import_progress', array() );
	$is_running = ! empty( $manual_import_data ) && $manual_import_data['status'] !== 'finished' ? true : false;
}

if ( $is_canceled ) {
	$is_running = false;
	add_action(
		'integration_toolkit_for_beehiiv_admin_notices',
		function() {
			?>
		<div class="integration-toolkit-for-beehiiv-import--notice integration-toolkit-for-beehiiv-import--notice-canceled">
			<h4><?php esc_html_e( 'Importing posts from is cancelled.', 'integration-toolkit-for-beehiiv' ); ?></h4>
			<p class="description"><?php esc_html_e( 'The import process is cancelled. You can start the import process again.', 'integration-toolkit-for-beehiiv' ); ?></p>
			<?php require_once INTEGRATION_TOOLKIT_FOR_BEEHIIV_PATH . 'admin/partials/components/progressbar.php'; ?>
		</div>
			<?php
		}
	);

	//delete all rows pending roe from the integration_toolkit_for_beehiiv_import table
	$manual_import_data = get_option( 'integration_toolkit_for_beehiiv_manual_import_progress', array() );
	Integration_Toolkit_For_Beehiiv\Import\Import_Table::delete_row_by_group($manual_import_data['group_name']);

	//delete the current manual import option from the database
	delete_option( 'integration_toolkit_for_beehiiv_manual_import_progress' );
}

if ( $is_running ) {
	$left_items	    = $manual_import_data['posts_progress']['pending_items'] ?? 0;
	$total_items    = $manual_import_data['posts_progress']['total_items'] ?? 0;
	$complete_items = $total_items - $left_items;

	$group_name       = $manual_import_data['group_name'];
	// notice with progress bar
	add_action(
		'integration_toolkit_for_beehiiv_admin_notices',
		function() use ( $complete_items, $total_items, $group_name ) {
			$cancel_nonce = wp_create_nonce( 'integration_toolkit_for_beehiiv_cancel_import' );
			$cancel_url   = add_query_arg(
				array(
					'page'   => 'integration-toolkit-for-beehiiv-import',
					'cancel' => $group_name,
					'nonce'  => $cancel_nonce,
				),
				admin_url( 'admin.php' )
			);
			?>
		<div class="integration-toolkit-for-beehiiv-import--notice">
			<h4><?php esc_html_e( '🔄 Currently Importing Content from...', 'integration-toolkit-for-beehiiv' ); ?></h4>
			<p class="description"><?php esc_html_e( 'We\'re actively importing posts from. You can continue with your other tasks or leave this page. We\'ll handle the rest.', 'integration-toolkit-for-beehiiv' ); ?>
			<br><strong><?php esc_html_e( 'Progress: ', 'integration-toolkit-for-beehiiv' ); ?><span class="number" id="imported_count"><?php echo esc_html( $complete_items ) . '</span> / <span class="number" id="total_count">' . esc_html( $total_items ); ?></span></strong></p>
			<a class="integration-toolkit-for-beehiiv-button-secondary integration-toolkit-for-beehiiv-button-cancel" id="integration-toolkit-for-beehiiv-import--cancel" href="<?php echo esc_url( $cancel_url ); ?>"><?php esc_html_e( 'Cancel', 'integration-toolkit-for-beehiiv' ); ?></a>
			<?php require_once INTEGRATION_TOOLKIT_FOR_BEEHIIV_PATH . 'admin/partials/components/progressbar.php'; ?>
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
		'integration_toolkit_for_beehiiv_admin_notices',
		function() use ( $args ) {

			if ( isset( $args['taxonomy_term'] ) && isset( $args['taxonomy'] ) ) {
				$term = get_term( $args['taxonomy_term'], $args['taxonomy'] );
			}

			$is_new_item_add         = $args['import_method'] !== 'update';
			$is_existing_item_update = $args['import_method'] !== 'new';
			?>
			<div class="integration-toolkit-for-beehiiv-import--notice">
				<h4><?php esc_html_e( 'Automated Content Import Activated.', 'integration-toolkit-for-beehiiv' ); ?></h4>
				<p class="description">
					<?php
					$post_status_str = '';
					foreach ( $args['post_status'] as $status => $post_status ) {
						if ( 'confirmed' === $status ) {
							$status = __( 'published', 'integration-toolkit-for-beehiiv' );
						}
						$post_status_str .= sprintf(
							// Translators: %1$s: beehiiv post status, %2$s: post status.
							esc_html__( 'a status "%1$s" in will be set to "%2$s"', 'integration-toolkit-for-beehiiv' ),
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
								$update_message = esc_html__( 'new content will be added, but existing posts will remain unaffected.', 'integration-toolkit-for-beehiiv' );
								break;
							case 'update':
								$update_message = esc_html__( 'existing posts will updated.', 'integration-toolkit-for-beehiiv' );
								break;
							default:
								$update_message = esc_html__( 'new content will be added, and existing posts will updated', 'integration-toolkit-for-beehiiv' );
								break;
						}

						// Translators: 1: Cron time, 2: Post type, 3: Taxonomy, 4: Taxonomy term, 5: Post status, 6: Update message
						echo nl2br(
							sprintf(
							esc_html__(
								'The current configuration will automatically fetch content every "%1$s" hours. This content will be integrated as WordPress "%2$s" under the "%3$s" taxonomy labeled as "%4$s". All incoming content with %5$s in WordPress. Please note that during this import process, %6$s Customize the automated import settings below to better match your content requirements.',
								'integration-toolkit-for-beehiiv'
							),
							'<strong>' . esc_html( $args['cron_time'] ) . '</strong>',  // Escaping and formatting cron time
							'<strong>' . esc_html( $args['post_type'] ) . '</strong>',  // Escaping and formatting post type
							'<strong>' . esc_html( $args['taxonomy'] ) . '</strong>',   // Escaping and formatting taxonomy
							'<strong>' . esc_html( $term->name ) . '</strong>',         // Escaping and formatting taxonomy term
							esc_html( $post_status_str ),                              // Escaping post status
							esc_html( $update_message )                                // Escaping update message
							)
						);

					} else {

						/* translators: 1: Cron time in hours, 2: Post type, 3: Post status description, 4: New item action (be/not be), 5: Existing item update action (be/not be) */
						$formatted_string = sprintf(
							esc_html__(
								'Current Auto Import is set to run every "%1$s" hours and will import to "%2$s" post type. %3$s The new items will %4$s imported and the Existing posts will %5$s updated. You can modify these settings below to customize the automatic import process to your needs.',
								'integration-toolkit-for-beehiiv'
							),
							'<strong>' . esc_html( $args['cron_time'] ) . '</strong>',
							'<strong>' . esc_html( $args['post_type'] ) . '</strong>',
							esc_html( $post_status_str ),
							esc_html( $is_new_item_add === true ? 'be' : 'not be' ),
							esc_html( $is_existing_item_update === true ? 'be' : 'not be' )
						);
						echo esc_html( $formatted_string );
					}
					?>
				</p>
			</div>
			<?php
		}
	);
}
$import_title = $is_auto ? __( 'Auto', 'integration-toolkit-for-beehiiv' ) : __( 'Manual', 'integration-toolkit-for-beehiiv' );
?>
<script>
var AllTaxonomies = <?php echo wp_json_encode( $taxonomies ); ?>;
var AllTaxonomyTerms = <?php echo wp_json_encode( $taxonomy_terms ); ?>;
var AllPostStatuses = <?php echo wp_json_encode( $post_statuses ); ?>;
var AllDefaultArgs = <?php echo wp_json_encode( $default_args ); ?>;
</script>
<div class="integration-toolkit-for-beehiiv-wrap">


	<?php require_once 'components/header.php'; ?>
	<div class="integration-toolkit-for-beehiiv-heading">
		<h1>
		<?php
		esc_html_e( 'Import Content', 'integration-toolkit-for-beehiiv' );
		?>
		</h1>

					<p>
						<?php esc_html_e( 'Choose how to import content from to your WordPress Site.', 'integration-toolkit-for-beehiiv' ); ?>
						<br>
						<?php 
							if ( !$is_auto ) {
								esc_html_e( 'This feature allows you to pull content from and publish it on your WordPress website.', 'integration-toolkit-for-beehiiv' ); 
							} else {
								esc_html_e( 'Set up an automatic process to periodically fetch and integrate content from into your WordPress website.' ); 
							}
						?>
					</p>	
	</div>

	<div class="integration-toolkit-for-beehiiv-tabs">
		<nav class="nav-tab-wrapper">
			<a class="re-nav-tab <?php echo $is_auto ? '' : 're-nav-tab-active'; ?>" data-tab="integration-toolkit-for-beehiiv-import" id="integration-toolkit-for-beehiiv-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=integration-toolkit-for-beehiiv-import' ) ); ?>"><?php esc_html_e( 'Manual Import', 'integration-toolkit-for-beehiiv' ); ?></a>
			<a class="re-nav-tab <?php echo $is_auto ? 're-nav-tab-active' : ''; ?>" data-tab="integration-toolkit-for-beehiiv-auto-import" id="integration-toolkit-for-beehiiv-auto-import-tab" href="<?php echo esc_url( admin_url( 'admin.php?page=integration-toolkit-for-beehiiv-import&tab=auto-import' ) ); ?>"><?php esc_html_e( 'Auto Import', 'integration-toolkit-for-beehiiv' ); ?></a>
		</nav>
	</div>

	<div class="integration-toolkit-for-beehiiv-wrapper border-t-0">
		<div class="integration-toolkit-for-beehiiv-import--notices <?php echo ! $is_running && $is_canceled ? 'integration-toolkit-for-beehiiv-import-form--hide_canceled' : ''; ?>" id="integration-toolkit-for-beehiiv-import--notices">
			<div class="hidden integration-toolkit-for-beehiiv-import--notice integration-toolkit-for-beehiiv-import--notice-error">
				<h4><?php esc_html_e( 'Please fix the following errors:', 'integration-toolkit-for-beehiiv' ); ?></h4>
				<ul>
				</ul>
			</div>
			<?php do_action( 'integration_toolkit_for_beehiiv_admin_notices' ); ?>
			<!-- convert notice above to new format -->
		</div>
		<?php if ( ! $is_running || ( $is_canceled && ! $is_running ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="integration-toolkit-for-beehiiv-import-form" class="integration-toolkit-for-beehiiv-import-form">
			<div class="integration-toolkit-for-beehiiv-import-fields">
				<div class="integration-toolkit-for-beehiiv-import-fields--step import-fields--step1 <?php echo ! $is_auto_action_exist ? 'active' : ''; ?>">
					<h2 class="integration-toolkit-for-beehiiv-import-fields--step--title" data-error-count="0"><?php esc_html_e( 'Step1: Choose data from', 'integration-toolkit-for-beehiiv' ); ?></h2>
					<div class="integration-toolkit-for-beehiiv-import-fields--step--content">
						<fieldset>
							<label for="integration-toolkit-for-beehiiv-content_type" class="pr-2"><strong><?php esc_html_e( 'Content Type', 'integration-toolkit-for-beehiiv' ); ?></strong>
							<small id="step1_content_type">
							   <i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
							</small>
						</label>
							<?php
							$content_types = array(
								'free_web_content'    => __( 'Free', 'integration-toolkit-for-beehiiv' ),
								'premium_web_content' => __( 'Premium', 'integration-toolkit-for-beehiiv' ),
							);

							foreach ( $content_types as $content_type => $label ) {
								if ( is_array( $default_args['content_type'] ) ) {
									$checked = in_array( $content_type, $default_args['content_type'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['content_type'] === $content_type ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block" >
									<input type="checkbox" name="integration-toolkit-for-beehiiv-content_type[]" id="integration-toolkit-for-beehiiv-content_type" value="<?php echo esc_attr( $content_type ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Select the type of content you want to import.', 'integration-toolkit-for-beehiiv' ); ?></p>
						</fieldset>

						<fieldset>
							<label for="integration-toolkit-for-beehiiv-beehiiv-status[]" class="pr-2">
								<strong><?php esc_html_e( 'Post Status', 'integration-toolkit-for-beehiiv' ); ?></strong>
								<small id="step1_post_status">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<?php
							$beehiiv_statuses = array(
								'confirmed' => __( 'Published', 'integration-toolkit-for-beehiiv' ),
								'archived'  => __( 'Archived', 'integration-toolkit-for-beehiiv' ),
								'draft'     => __( 'Draft', 'integration-toolkit-for-beehiiv' ),
							);

							foreach ( $beehiiv_statuses as $beehiiv_status => $label ) {
								if ( is_array( $default_args['beehiiv-status'] ) ) {
									$checked = in_array( $beehiiv_status, $default_args['beehiiv-status'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['beehiiv-status'] === $beehiiv_status ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block">
									<input type="checkbox" name="integration-toolkit-for-beehiiv-beehiiv-status[]" id="integration-toolkit-for-beehiiv-beehiiv-status"  value="<?php echo esc_attr( $beehiiv_status ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Choose the status of the content you want to import.', 'integration-toolkit-for-beehiiv' ); ?></p>
						</fieldset>
					</div>
				</div>
				<div class="integration-toolkit-for-beehiiv-import-fields--step import-fields--step2" data-error-count="0">
					<h2 class="integration-toolkit-for-beehiiv-import-fields--step--title" data-error-count="0"><?php esc_html_e( 'Step 2: Import data to WordPress', 'integration-toolkit-for-beehiiv' ); ?></h2>
					<div class="integration-toolkit-for-beehiiv-import-fields--step--content">
						<fieldset>
							<label class="d-block" for="integration-toolkit-for-beehiiv-post_type">
								<strong><?php esc_html_e( 'Select Post Type and Taxonomy', 'integration-toolkit-for-beehiiv' ); ?></strong>
								<small id="step2_post_type">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="integration-toolkit-for-beehiiv-post_type" id="integration-toolkit-for-beehiiv-post_type" required>
								<option value="0"><?php esc_html_e( 'Select Post Type', 'integration-toolkit-for-beehiiv' ); ?></option>
								<?php
								foreach ( $post_types as $re_post_type ) {
									if ( $re_post_type->name === 'attachment' ) {
										continue;
									}
									echo '<option value="' . esc_attr( $re_post_type->name ) . '">' . esc_html( $re_post_type->labels->singular_name ) . '</option>';
								}
								?>
							</select>
							<select name="integration-toolkit-for-beehiiv-taxonomy" id="integration-toolkit-for-beehiiv-taxonomy" class="hidden integration-toolkit-for-beehiiv-taxonomy" required>
								<option value="0"><?php esc_html_e( 'Select Post Type First', 'integration-toolkit-for-beehiiv' ); ?></option>
							</select>
							<select name="integration-toolkit-for-beehiiv-taxonomy_term" id="integration-toolkit-for-beehiiv-taxonomy_term" class="hidden integration-toolkit-for-beehiiv-taxonomy_term" required>
								<option value="0"><?php esc_html_e( 'Select Term', 'integration-toolkit-for-beehiiv' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Choose the post type and taxonomy for the imported content.', 'integration-toolkit-for-beehiiv' ); ?></p>
						</fieldset>
						<fieldset>
							<label for="integration-toolkit-for-beehiiv-post_author" class="d-block">
								<strong><?php esc_html_e( 'Content Author', 'integration-toolkit-for-beehiiv' ); ?></strong>
								<small id="step2_post_author">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="integration-toolkit-for-beehiiv-post_author" id="integration-toolkit-for-beehiiv-post_author" required>
								<option value="0"><?php esc_html_e( 'Select Author', 'integration-toolkit-for-beehiiv' ); ?></option>
								<?php
								$authors = get_users( array( 'role__in' => array( 'author', 'editor', 'administrator' ) ) );
								foreach ( $authors as $author ) {
									echo '<option value="' . esc_attr( $author->ID ) . '">' . esc_html( $author->display_name ) . '</option>';
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( 'Assign the imported posts to a specific user.', 'integration-toolkit-for-beehiiv' ); ?></p>
						</fieldset>
						<fieldset>
							<label for="integration-toolkit-for-beehiiv-post_tags">
								<strong><?php esc_html_e( 'Beehiiv Tags', 'integration-toolkit-for-beehiiv' ); ?></strong>
								<small id="step2_post_tags">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<select name="integration-toolkit-for-beehiiv-post_tags-taxonomy" id="integration-toolkit-for-beehiiv-post_tags-taxonomy" class="integration-toolkit-for-beehiiv-post_tags-taxonomy">
								<option value="0"><?php esc_html_e( 'Select post type first', 'integration-toolkit-for-beehiiv' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'To import tags from, select the taxonomy and term you want to use for the imported tags.', 'integration-toolkit-for-beehiiv' ); ?></p>
						</fieldset>
						<fieldset id="integration-toolkit-for-beehiiv-post_status">
							<label for="integration-toolkit-for-beehiiv-post_status">
								<strong><?php esc_html_e( 'Post Status', 'integration-toolkit-for-beehiiv' ); ?></strong>
								<small id="step2_post_status">
									<i claversion-1.0.0ss="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<div class="integration-toolkit-for-beehiiv-post_status--fields"></div>
							<p class="description"><?php esc_html_e( 'Assign Post Status to the imported content for each Post Status selected in step 1.', 'integration-toolkit-for-beehiiv' ); ?></p>
						</fieldset>

						<fieldset>
							<label for="integration-toolkit-for-beehiiv-import_method"><strong><?php esc_html_e( 'Import Option', 'integration-toolkit-for-beehiiv' ); ?></strong>
								<small id="step2_import_method">
									<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
								</small>
							</label>
							<?php
							$import_methods = array(
								'new'            => __( 'Import new items', 'integration-toolkit-for-beehiiv' ),
								'update'         => __( 'Update existing items', 'integration-toolkit-for-beehiiv' ),
								'new_and_update' => __( 'Do both', 'integration-toolkit-for-beehiiv' ),
							);

							foreach ( $import_methods as $import_method => $label ) {
								if ( is_array( $default_args['import_method'] ) ) {
									$checked = in_array( $import_method, $default_args['import_method'], true ) ? 'checked' : '';
								} else {
									$checked = ( $default_args['import_method'] === $import_method ) ? 'checked' : '';
								}
								?>
								<label class="pr-2 d-block">
									<input type="radio" name="integration-toolkit-for-beehiiv-import_method" id="integration-toolkit-for-beehiiv-import_method" value="<?php echo esc_attr( $import_method ); ?>" <?php echo esc_attr( $checked ); ?>> <?php echo esc_html( $label ); ?>
								</label>
								<?php
							}
							?>
							<p class="description"><?php esc_html_e( 'Choose the desired action for importing data.', 'integration-toolkit-for-beehiiv' ); ?></p>
						</fieldset>
						<?php if ( $is_auto ) : ?>
							<fieldset>
								<label for="integration-toolkit-for-beehiiv-cron_time" class="d-block">
									<strong><?php esc_html_e( 'Import Schedule', 'integration-toolkit-for-beehiiv' ); ?></strong>
									<small id="step2_cron_time">
										<i class="fa-solid fa-circle-question" style="color: #65696c;"></i>
									</small>
						    	</label>
								<input type="number" name="integration-toolkit-for-beehiiv-cron_time" id="integration-toolkit-for-beehiiv-cron_time" value="<?php echo esc_attr( $default_args['cron_time'] ); ?>" min="1" required placeholder="<?php esc_attr_e( 'Enter interval in hours', 'integration-toolkit-for-beehiiv' ); ?>"> Hour(s)
									<p class="description"><?php esc_html_e( 'Enter the desired time intervals in hours and set the frequency of auto imports from your to your WordPress site.', 'integration-toolkit-for-beehiiv' ); ?></p>
							</fieldset>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<input type="hidden" name="action" value="<?php echo $is_auto ? 'integration_toolkit_for_beehiiv_auto_import' : 'integration_toolkit_for_beehiiv_manual_import'; ?>">
			<input type="hidden" name="integration_toolkit_for_beehiiv_import_nonce" id="integration_toolkit_for_beehiiv_import_nonce" value="<?php echo esc_attr( wp_create_nonce( 'integration_toolkit_for_beehiiv_import_nonce' ) ); ?>">
			<?php
			$disabled = $is_running && ! $is_auto ? 'disabled' : '';
			if ( $disabled ) {
				echo '<p>';
				esc_html_e( 'It is not possible to initiate another manual import while the current one is still in progress. refresh the page to update the status.', 'integration-toolkit-for-beehiiv' );
				echo '</p>';
			}
			$submit_text = $is_auto ? __( 'Save', 'integration-toolkit-for-beehiiv' ) : __( 'Start Import', 'integration-toolkit-for-beehiiv' );
			submit_button( $submit_text, 'primary components-button is-primary', 'integration-toolkit-for-beehiiv-start-import', false, $disabled );
			?>
		</form>
		<?php endif; ?>
	</div>
</div>
