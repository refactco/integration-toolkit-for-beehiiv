<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Integration_Toolkit_For_Beehiiv\Lib;

/**
 * AJAX Bulk Process
 * This class is responsible for handling AJAX requests for the Bulk Process
 */
abstract class AJAX {
    
    /**
    * AJAX action name
    *
    * @var string
    */
    protected $action = '';
    
    /**
    * AJAX nonce action
    *
    * @var string
    */
    protected $nonce_action = '';
    
    /**
    * AJAX constructor
    */
    public function __construct() {
        $this->nonce_action = $this->action . '_nonce';
        $this->register();
    }
    
    /**
    * Register AJAX actions
    *
    * @return void
    */
    public function register() {
        add_action( 'wp_ajax_' . $this->action, array( $this, 'handle' ) );
    }
    
    /**
    * Handle AJAX request
    *
    * @return void
    */
    public function handle() {
        // Check nonce.
        check_ajax_referer( $this->nonce_action, 'nonce' );

        // Handle request.
        $this->handle_request();
    }
    
    /**
    * Handle AJAX request
    *
    * @return void
    */
    abstract public function handle_request();
    
}