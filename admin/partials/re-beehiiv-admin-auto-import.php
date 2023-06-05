<?php
/**
 * Auto Import Page
 *
 * @package Re_Beehiiv
 */

use Re_Beehiiv\Import\Manage_Actions;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$args = Manage_Actions::get_auto_action_args();
$is_auto_action_exist = ! empty( $args ) ? true : false;

if ( $is_auto_action_exist ) {
	$args = reset( $args );
}

$default_args = array(
	'auto'	=> 'auto',
	'content_type' => 'free_web_content',
	'post_status' => 'publish',
	'update_existing' => 'no',
	'exclude_draft'	=> 'no',
	'taxonomy'	=> '0',
	'term'	=> '0',
	'cron_time'	=> '0',
	'post_type'	=> '0',
);

$args = wp_parse_args( $args, $default_args );


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
		<?php if ( $is_auto_action_exist ) : 
			// get term by term id
			$term = get_term( $args['term'], $args['taxonomy'] );
			?>
			<div class="current_auto_import">
				<p> Current Auto Import will run <strong><?php echo esc_html( $args['cron_time'] ); ?></strong> and will import to <strong><?php echo esc_html( $args['post_type'] ); ?></strong> post type and <strong><?php echo esc_html( $args['taxonomy'] ); ?></strong> taxonomy with <strong><?php echo esc_html( $term->name ); ?></strong> term. The default post status is <strong><?php echo esc_html( $args['post_status'] ); ?></strong>. Draft posts will <strong><?php echo $args['exclude_draft'] === 'yes' ? 'not be' : ' be'; ?></strong> imported and the Existing posts will <strong><?php echo $args['update_existing'] === 'yes' ? ' be' : 'not be'; ?></strong> updated. You can change the settings below.</p>
			</div>
		<?php endif; ?>
		<!-- set cron time -->
		<fieldset>
			<label for="re-beehiiv-cron_time"><strong>Cron Time: </strong></label>
			<p class="description">How often do you want to run the cron job?</p>
			<select name="re-beehiiv-cron_time" id="re-beehiiv-cron_time" required>
				<?php
				$cron_times = array(
					'hourly'      => 'Hourly',
					'twicedaily'  => 'Twice Daily',
					'daily'       => 'Daily',
					'weekly'      => 'Weekly',
				);

				foreach ( $cron_times as $cron_time => $cron_time_label ) {
					$selected = '';
					if ( $cron_time === $args['cron_time'] ) {
						$selected = 'selected';
					}
					echo '<option value="' . esc_attr( $cron_time ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $cron_time_label ) . '</option>';
				}
				
				?>
			</select>
		</fieldset>
		<!-- Select: Content Type -->
		<fieldset>
			<label for="re-beehiiv-content_type"><strong>Content Type: </strong></label>
			<p class="description">What kind of content do you want to import?</p>
			<select name="re-beehiiv-content_type" id="re-beehiiv-content_type">
				<?php
				$content_types = array(
					'free_web_content' => 'Free Web Content',
					'premium_web_content' => 'Premium Web Content',
					'both' => 'Both',
				);

				foreach ( $content_types as $content_type => $content_type_label ) {
					$selected = '';
					if ( $content_type === $args['content_type'] ) {
						$selected = 'selected';
					}
					echo '<option value="' . esc_attr( $content_type ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $content_type_label ) . '</option>';
				}

				?>
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
					$selected = '';
					if ( $re_post_type === $args['post_type'] ) {
						$selected = 'selected';
					}
					echo '<option value="' . esc_attr( $re_post_type ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $re_post_type ) . '</option>';
				}
				?>
			</select>
			<select name="re-beehiiv-taxonomy" id="re-beehiiv-taxonomy">
				<?php 
				if ( !empty( $args['post_type']) && $args['taxonomy'] !== '0' ) {
					foreach ( $taxonomies[ $args['post_type'] ] as $re_taxonomy ) {
						$selected = '';
						if ( $re_taxonomy['name'] === $args['taxonomy'] ) {
							$selected = 'selected';
						}
						echo '<option value="' . esc_attr( $re_taxonomy['name'] ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $re_taxonomy['label'] ) . '</option>';
					}
				} else {
					echo '<option value="0">Select Post Type First</option>';
				}
				?>
			</select>
			<div id="re-beehiiv-taxonomy_terms">
				<p>Choose a term below.</p>
				<select name="re-beehiiv-taxonomy_term" id="re-beehiiv-taxonomy_term">
					<?php
					if ( !empty( $args['post_type']) && $args['taxonomy'] !== '0' && $args['term'] !== '0' ) {

						$selected_terms = $taxonomy_terms[ $args['post_type'] ][ $args['taxonomy'] ];

						foreach ( $selected_terms as $selected_term ) {
							$selected = '';
							if ( $selected_term->term_id == $args['term'] ) {
								$selected = 'selected';
							}
							echo '<option value="' . esc_attr( $selected_term->term_id ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $selected_term->name ) . '</option>';
						}
					} else {
						echo '<option value="0">Select Taxonomy First</option>';
					}

					?>
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
					$post_statuses = array(
						'publish' => 'Publish',
						'draft' => 'Draft',
						'pending' => 'Pending',
						'private' => 'Private',
					);

					foreach ( $post_statuses as $post_status => $post_status_label ) {
						$selected = '';
						if ( $post_status === $args['post_status'] ) {
							$selected = 'selected';
						}
						echo '<option value="' . esc_attr( $post_status ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $post_status_label ) . '</option>';
					}

					?>
				</select>
				</fieldset>
				<fieldset>
					<label>
						<input type="checkbox" name="re-beehiiv-exclude_draft" id="re-beehiiv-exclude_draft" value="yes" <?php checked( $args['exclude_draft'], 'yes' ); ?>> Exclude Draft Posts
					</label>
					<p class="description">If checked, posts with draft status in Beehiiv will not be imported.</p>
					<label>
						<input type="checkbox" name="re-beehiiv-update_existing" id="re-beehiiv-update_existing" value="yes" <?php checked( $args['update_existing'], 'yes' ); ?>> Update Existing Posts
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
				<button type="button" class="button-primary" id="re-beehiiv-auto-import">Save</button>
			</div>
		</div>
	</div>
</div>
