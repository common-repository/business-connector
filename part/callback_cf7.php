<?php

if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' && !empty(get_option('cf7_form')) && !empty(get_option('cf7_page')) ) ){
    add_action( 'wpcf7_submit', array( $this, 'wpcf7_save_message'), 10, 2 );
}

