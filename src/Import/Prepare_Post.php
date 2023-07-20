<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Re_Beehiiv\Import;
use Re_Beehiiv\Lib\Logger;
use WP_Error;

class Prepare_Post {

    /**
     * @var array
     */
    private $form_data;

    /**
     * @var array
     */
    private $item;

    public function __construct( $item, $form_data ) {
        $this->item = $item;
        $this->form_data = $form_data;
    }

    /**
     * Prepare post data for creating post in WordPress
     *
     * @return array|\Exception
     */
    public function prepare_post() {
        if ( ! $this->item ) {
            return array();
        }

        // Maybe skip the post based on form data
        try {
            $this->maybe_skip_item_based_on_form_data();
        } catch ( \Exception $e ) {
            return $e;
        }
        
        // Prepare post data
        $post_data = apply_filters(
            're_beehiiv_import_prepare_post',
            $this->prepare_beehiiv_data_for_wp(
                $this->item,
            ),
            $this->item,
            $this->form_data
        );

        return $post_data;
    }

    public function maybe_skip_item_based_on_form_data() {
        // Maybe skip the post based on import method
        if ( $this->form_data['import_method'] === 'new' ) {
            // check if the post already exists
            if ( $this->is_unique_post( $this->item['id'] ) ) {
                throw new \Exception(
                    sprintf(
                        __( '%1$s - %2$s is already exists', 're-beehiiv' ),
                        $this->item['id'],
                        $this->item['title'],
                    )
                );
            }
        } elseif ( $this->form_data['import_method'] === 'update' ) {
            // check if the post already exists
            if ( ! $this->is_unique_post( $this->item['id'] ) ) {
                throw new \Exception(
                    sprintf(
                        __( '%1$s - %2$s is not exists', 're-beehiiv' ),
                        $this->item['id'],
                        $this->item['title'],
                    )
                );
            }
        }

        // Maybe skip the post based on beehiiv status
        if ( ! in_array( $this->item['status'], $this->form_data['beehiiv-status'], true ) ) {
            throw new \Exception(
                sprintf(
                    __( '%1$s - %2$s is not in selected status', 're-beehiiv' ),
                    $this->item['id'],
                    $this->item['title'],
                )
            );
        }

        return false;
    }
    

    /**
	 * Check if post is unique
	 *
	 * @param int $post_id post id on beehiiv
	 */
	public function is_unique_post( $post_id ) {
		$args  = array(
			'meta_key'       => 're_beehiiv_post_id',
			'meta_value'     => $post_id,
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => 1,
		);
		$posts = get_posts( $args );

		// return post id if exists
		if ( isset( $posts[0] ) ) {
			return $posts[0]->ID;
		}
		return false;
	}


    /**
	 * Prepare beehiiv data for creating post in WordPress
	 *
	 * @param array $value
	 * @return array
	 */
	private function prepare_beehiiv_data_for_wp( $value ) {

		// create a post
		$data = array(
			'post' => array(
				'post_title'   => $value['title'],
				'post_excerpt' => $value['subtitle'],
				'post_author'  => 1,
				'post_type'    => 'post',
				'post_name'    => $value['slug'],
			),
			'tags' => $value['content_tags'],
			'meta' => array(
				'content_type' => $this->form_data['content_type'],
				'status'       => $value['status'],
				'post_id'      => $value['id'],
				'post_url'     => $value['web_url'],
			),
			// 'auto' => $args['auto'] ?? 'manual',
			// 'args' => $args,
		);

		// set post status
		if ( ! isset( $this->form_data['post_status'][ $value['status'] ] ) ) {
			$data['post']['post_status'] = 'draft';
		} else {
			$data['post']['post_status'] = $this->form_data['post_status'][ $value['status'] ];
		}

		// set content
		if ( isset( $value['content'] ) ) {
			$content                      = $this->get_post_content( $value['content'], $this->form_data['content_type'] );
			$data['post']['post_content'] = $this->filter_unnecessary_content( $content );
		}

		// set post author
		if ( isset( $this->form_data['post_author'] ) ) {
			$data['post']['post_author'] = (int) $this->form_data['post_author'];
		}

		// set post date
		$data['post']['post_date'] = isset( $value['publish_date'] ) ? date( 'Y-m-d H:i:s', $value['publish_date'] ) : date( 'Y-m-d H:i:s', time() ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

		// maybe set premium content
		if ( isset( $value['content']['premium']['web'] ) ) {
			$data['meta']['premium_content'] = $value['content']['premium']['web'];
		}

		return $data;
	}

    /**
	 * Remove unnecessary content from html
	 * This function will remove unnecessary content from html
	 * It will remove html, head, body, style, class attributes and doctype
	 * It will also remove all inline styles
	 *
	 * @param string $content
	 * @return string
	 */
	private function filter_unnecessary_content( $content ) {
		$pattern = '/<!DOCTYPE.*?>|<head>.*?<\/head>|<body.*?>|<\/body>|style=[\'"].*?[\'"]|<style.*?<\/style>|class=[\'"][^\'"]*[\'"]|<html.*?>|<\/html>/s';

		return preg_replace( $pattern, '', $content );
	}

    /**
	 * Get post content
	 *
	 * @param array $content
	 * @param array $content_type
	 * @return string
	 */
	private function get_post_content( $content, array $content_type ) {
		if ( isset( $content['premium']['web'] ) && in_array( 'premium_web_content', $content_type, true ) ) {
			return $content['premium']['web'];
		} elseif ( isset( $content['free']['web'] ) && in_array( 'free_web_content', $content_type, true ) ) {
			return $content['free']['web'];
		} else {
			return '';
		}
	}


}