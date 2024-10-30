<div class="wrap">
    <h1><?php _e( 'All-in-one Messenger settings', AIO_ME_TEXT );?></h1>
    <hr>
    <form method="post" action="options.php">
        <?php settings_fields( 'aio-messenger' ); ?>
        <?php do_settings_sections( 'aio-messenger' ); ?>
        <h2><?php _e( 'Facebook Messenger API settings', AIO_ME_TEXT );?></h2>
        <p><?php _e( 'Go to <a href="https://developers.facebook.com" target="_blank">Facebook Developer website</a> and create a new App, after add Messenger product to your app and fill these settings.', 'messenger' );?></p>
        <p><?php _e( 'Remember to add the "messages" and "messaging_postbacks" calls to the Webhook.', AIO_ME_TEXT );?></p>
        <table class="form-table business-messenger">
            <tr valign="top">
                <th scope="row">> <?php _e( 'Page access token', AIO_ME_TEXT );?></th>
                <td><input type="text" name="facebook_token" value="<?php echo esc_attr( get_option('facebook_token') ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">> <?php _e( 'Webhooks callback url', AIO_ME_TEXT );?></th>
                <td><input type="text" value="<?php echo admin_url( '?chat_bot=facebook' );?>" onclick="this.select();" readonly/></td>
            </tr>
            <tr valign="top">
                <th scope="row">> <?php _e( 'Webhooks callback token', AIO_ME_TEXT );?></th>
                <td><input type="text" name="facebook_callback_token" value="<?php echo esc_attr( get_option('facebook_callback_token') ); ?>" /></td>
            </tr>
        </table>

        <?php if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ){ ?>
        <hr>
        <h2><?php _e( 'Contact Form 7 settings', AIO_ME_TEXT );?></h2>
        <p><?php _e( 'Complete these settings to manage conversations from the dashboard.<br>A link will be added to each email sent to the customer to continue the conversation.<br>Only messages received by the form (frontend) will be shown in conversations and not direct replies via email (we can\'t read your emails inbox).', AIO_ME_TEXT );?></p>
        <p><?php _e( 'To work you need to insert a field named "email" in the form. (ex: [email* <strong>email</strong>] )', AIO_ME_TEXT );?></p>
        <table class="form-table business-messenger">
            <tr valign="top">
                <th scope="row">> <?php _e( 'Choose the form', AIO_ME_TEXT );?></th>
                <td><select name="cf7_form">
                    <option value=""><?php _e( 'Choose...', AIO_ME_TEXT );?></option>
                    <?php
                                                                                global $wpdb;
                                                                                $query = "
    SELECT $wpdb->posts.* 
    FROM $wpdb->posts
    WHERE $wpdb->posts.post_type = 'wpcf7_contact_form'
    AND $wpdb->posts.post_status = 'publish'
    ORDER BY $wpdb->posts.post_date DESC";

                                                                                $cf7_forms = $wpdb->get_results($query, OBJECT);

                                                                                foreach($cf7_forms as $cf7_form){
                                                                                    echo '<option value="'.$cf7_form->ID.'" '.selected( esc_attr( get_option('cf7_form') ), $cf7_form->ID ).'>'.$cf7_form->post_title.'</option>';
                                                                                }
                    ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">> <?php _e( 'Page containing the form', AIO_ME_TEXT );?></th>
                <td><select name="cf7_page">
                    <option value=""><?php _e( 'Choose...', AIO_ME_TEXT );?></option>
                    <?php
                                                                                global $wpdb;
                                                                                $query = "
    SELECT $wpdb->posts.* 
    FROM $wpdb->posts
    WHERE $wpdb->posts.post_type = 'page'
    AND $wpdb->posts.post_status = 'publish'
    ORDER BY $wpdb->posts.post_date DESC";

                                                                                $pages = $wpdb->get_results($query, OBJECT);

                                                                                foreach($pages as $page){
                                                                                    echo '<option value="'.$page->ID.'" '.selected( esc_attr( get_option('cf7_page') ), $page->ID ).'>'.$page->post_title.'</option>';
                                                                                }
                    ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">> <?php _e( 'Sender name for emails', AIO_ME_TEXT );?></th>
                <td><input type="text" name="cf7_sender_name" value="<?php echo esc_attr( get_option('cf7_sender_name') ); ?>" placeholder="<?php echo get_bloginfo( 'name' ); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">> <?php _e( 'Sender email', AIO_ME_TEXT );?></th>
                <td><input type="text" name="cf7_sender_email" value="<?php echo esc_attr( get_option('cf7_sender_email') ); ?>" placeholder="<?php echo get_bloginfo( 'admin_email' ); ?>" /></td>
            </tr>
        </table>

        <?php } else { ?>
        <hr>
        <h2><?php _e( 'Contact Form 7 settings', AIO_ME_TEXT );?></h2>
        <p><?php _e( 'To enable this function download, install and activate the plugin Contact Form 7.', AIO_ME_TEXT );?></p>
        <p><a href="<?php echo admin_url('plugins.php');?>" class="button button-secondary" follow="nofollow"><?php _e( 'Manage plugins', AIO_ME_TEXT );?></a> <a href="https://wordpress.org/plugins/contact-form-7/" class="button button-primary" target="_blank" follow="nofollow"><?php _e( 'Download from WordPress.org', AIO_ME_TEXT );?></a></p>
        <?php } ?>

        <hr>
        <h2><?php _e( 'Who can use the chat', AIO_ME_TEXT );?></h2>
        <p><?php _e( 'Choose which user role can use the chat.', AIO_ME_TEXT );?></p>
        <table class="form-table business-messenger">
            <tr valign="top">
                <th scope="row">> <?php _e( 'User roles', AIO_ME_TEXT );?></th>
                <td>
                    <?php

                    $enabled_roles = get_option('user_roles') ? get_option('user_roles') : array('administrator');
                    $all_roles = get_editable_roles();
                    foreach ($all_roles as $role => $details) {
                        echo '<label><input type="checkbox" name="user_roles[]" value="'.esc_attr($role).'"'.(in_array(esc_attr($role) , $enabled_roles ) || esc_attr($role) == 'administrator' ? ' checked' : '').(esc_attr($role) == 'administrator' ? ' disabled readonly' : '').'> '.translate_user_role($details['name']).'</label><br>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        <hr>
        <?php submit_button(); ?>

    </form>
</div>