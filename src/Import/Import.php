<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing

namespace Re_Beehiiv\Import;

defined( 'ABSPATH' ) || exit;

class Import {
    protected ?string $method = 'auto';

    protected ?string $group_name = '';

    protected ?array $form_data = array();

    public function __construct( $form_data, $group_name, $method = 'auto' ) {
        $this->form_data = $form_data;
        $this->group_name = $group_name;
        $this->method = $method;

        $this->init();
    }

    public function init() {
        $this->import();
    }

    public function import() {
        if ( $this->method === 'auto' ) {
            $auto_importer = new Auto_Importer( $this->form_data, $this->group_name, $this->method );
            $auto_importer->import();
        } elseif ( $this->method === 'manual' ) {
            $manual_importer = new Manual_Importer( $this->form_data, $this->group_name, $this->method, 'AJAX' );
            $manual_importer->import();
        }
    }

    public function get_group_name() {
        return $this->group_name;
    }

    public function get_method() {
        return $this->method;
    }

    public function get_form_data() {
        return $this->form_data;
    }

    public static function get_import_progress() {
        return get_option( 're_beehiiv_manual_import_progress', array() );
    }

}
