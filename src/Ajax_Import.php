<?php
namespace Re_Beehiiv;
use Re_Beehiiv\API\V2\Posts;

defined( 'ABSPATH' ) || exit;

class Ajax_Import {

    public function callback() {

        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'RE_BEEHIIV_ajax_import')) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid nonce'
            ]);
            exit;
        }

        // check category
        $cat = (isset($_POST['cat']) && $_POST['cat']) ? get_term_by('id', $_POST['cat'], 'category') : false;
        if (!$cat) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid category'
            ]);
            exit;
        }

        // check content type
        $content_type = (isset($_POST['content_type']) && $_POST['content_type']) ? $_POST['content_type'] : false;
        if (!$content_type) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid content type'
            ]);
            exit;
        }

        $content_type = $content_type == 'both' ? array('free_web_content', 'premium_web_content') : $content_type;

        // GET ALL DATA (CACHED)
        $data = $this->get_all_data(false, $content_type);

        if (isset($data['error'])) {
            wp_send_json($data);
            exit;
        }

        $last_id = (int) get_option('RE_BEEHIIV_ajax_last_check_id', false);
        $count = count($data);
        $percent = intval($last_id / $count * 100);
        if ($percent == 100) {
            $last_id = 0;
            // reset last check id option
            update_option('RE_BEEHIIV_ajax_last_check_id', $last_id);
            // reset results option
            update_option('RE_BEEHIIV_ajax_import_results', array(
                'success' => 0,
                'error' => 0,
                'message' => array()
            ));
        }

        $index = 0;
        
        // set results option
        $results = get_option('RE_BEEHIIV_ajax_import_results', array(
            'success' => 0,
            'error' => 0,
            'message' => array()
        ));

        foreach ($data as $value) {
            $index++;
            if ($last_id && $last_id >= $index) continue;

            //create a post
            $data = [
                'post'          => [
                    'post_title'    => $value['title'],
                    'post_excerpt'  => $value['subtitle'],
                    'post_author'   => 1,
                    'post_type'     => 'post',
                    'post_category' => array($cat->term_id),
                    'post_name'     => $value['slug']
                ],
                'category'      => array($cat->term_id),
                'tags'          => $value['content_tags'],
                'meta'          => [
                    'content_type' => $content_type,
                    'status'       => $value['status'],
                    'post_id' => $value['id'],
                    'post_url' => $value['web_url']
                ]
            ];

            if ($value['status'] == 'confirmed') {
                $data['post']['post_status'] = 'publish';
            } else {
                $data['post']['post_status'] = 'draft';
            }

            if ( isset($value['content']) ) {
                $content = $this->get_post_content($value['content'], $content_type);
                if (!$content) {
                    $results['error']++;
                    $results['message'][] = 'Content not found - ' . $value['id'];
                    continue;
                }
                $data['post']['post_content'] = $content;
            }

            $data['post']['post_date'] = isset($value['publish_date']) ? date('Y-m-d H:i:s', $value['publish_date']) : date('Y-m-d H:i:s', time());

            $data = apply_filters('RE_BEEHIIV_ajax_import_before_create_post', $data);
            try {
                new Create_Post($data);
                $results['success']++;
            } catch (\Exception $e) {
                $results['error']++;
                $results['message'][] = $e->getMessage();
            }

            // ACTIONS
            update_option('RE_BEEHIIV_ajax_last_check_id', $index);
            update_option('RE_BEEHIIV_ajax_import_results', $results);
            $last_id = $index;
            break;
        }
        $percent = intval($last_id / $count * 100);
        wp_send_json([
            'success' => true,
            'percent' => $percent,
            'count' => $count,
            'last_id' => $last_id,
            'results' => $results,
        ]);
        exit;
    }

    public function get_all_data($force = false, $content_type = array()){
        $cached = get_transient('RE_BEEHIIV_get_all_recurly_accounts');
        if ($cached && $force == false) return $cached;
        $data = Posts::get_all_posts($content_type);
        set_transient('RE_BEEHIIV_get_all_recurly_accounts', $data, DAY_IN_SECONDS);
        update_option('RE_BEEHIIV_ajax_all_recurly_accounts', count($data));
        return $data;
    }

    private function get_post_content($content, $content_type) {
        if ($content_type == 'premium_web_content') {
            if (!isset($content['premium']['web'])) {
                return false;
            }
            return $content['premium']['web'];
        } else if ($content_type == 'free_web_content') {
            if (!isset($content['free']['web'])) {
                return false;
            }
            return $content['free']['web'];
        } else {
            if (isset($content['premium']['web'])) {
                return $content['premium']['web'];
            } else if (isset($content['free']['web'])) {
                return $content['free']['web'];
            } else {
                return '';
            }
        }
    }

}