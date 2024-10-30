<?php
/*
Plugin Name: All-in-One Messenger
Plugin URI: https://www.andreadegiovine.it/download/all-in-one-messenger/?utm_source=wordpress_org&utm_medium=plugin_link&utm_campaign=all_in_one_messenger
Description: All-in-one solution for chatting and messaging with customers from the WordPress dashboard. Compatible with social network chat and many contact plugins.
Version: 1.0
Author: Andrea De Giovine
Author URI: https://www.andreadegiovine.it/?utm_source=wordpress_org&utm_medium=plugin_details&utm_campaign=all_in_one_messenger
Text Domain: messenger
Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'OH YEAH!' );
}

// Define global constants
if ( !defined( 'AIO_ME_DIR' ) ) {
    define( 'AIO_ME_DIR', dirname( plugin_basename( __FILE__ ) ) );
}
if ( !defined( 'AIO_ME_BASE' ) ) {
    define( 'AIO_ME_BASE', plugin_basename( __FILE__ ) );
}
if ( !defined( 'AIO_ME_URL' ) ) {
    define( 'AIO_ME_URL', plugin_dir_url( __FILE__ ) );
}
if ( !defined( 'AIO_ME_PATH' ) ) {
    define( 'AIO_ME_PATH', plugin_dir_path( __FILE__ ) );
}
if ( !defined( 'AIO_ME_ASSETS' ) ) {
    define( 'AIO_ME_ASSETS', plugin_dir_url( __FILE__ ) . 'assets/' );
}
if ( !defined( 'AIO_ME_PART' ) ) {
    define( 'AIO_ME_PART', plugin_dir_path( __FILE__ ) . 'part/' );
}
if ( !defined( 'AIO_ME_SLUG' ) ) {
    define( 'AIO_ME_SLUG', dirname( plugin_basename( __FILE__ ) ) );
}
if ( !defined( 'AIO_ME_NAME' ) ) {
    define( 'AIO_ME_NAME', 'All-in-one Messenger' );
}
if ( !defined( 'AIO_ME_VERSION' ) ) {
    define( 'AIO_ME_VERSION', '1.0' );
}
if ( !defined( 'AIO_ME_TEXT' ) ) {
    define( 'AIO_ME_TEXT', 'aio-messenger' );
}
if ( !defined( 'AIO_ME_PREFIX' ) ) {
    define( 'AIO_ME_PREFIX', 'bu_me' );
}
if ( !defined( 'AIO_ME_SETTINGS' ) ) {
    define( 'AIO_ME_SETTINGS', 'businessmessenger' );
}

//date_default_timezone_set(get_option('timezone_string'));

foreach ( glob( AIO_ME_PATH . "inc/*.php" ) as $file ) {
    include_once $file;
}

if ( ! class_exists( 'Aio_Messenger' ) ) {
    class Aio_Messenger {

        public function __construct(){
            add_action( 'init', array( $this, 'init_capabilities' ), 0 );

            // Actions hook
            add_action( 'init', array( $this, 'init_post_types' ) );
            add_action( 'admin_menu', array( $this, 'init_options_page'), PHP_INT_MAX );

            add_action( 'add_meta_boxes', array( $this, 'init_chat_metabox' ) );
            add_action( 'wp_ajax_init_ajax_messenger', array( $this, 'init_ajax_messenger' ) );
            add_action( 'init', array( $this, 'init_chat_callbacks' ), PHP_INT_MAX );
            add_action( 'admin_enqueue_scripts', array( $this, 'init_admin_style_script') );
            add_action( 'admin_notices', array( $this, 'init_admin_notices') );
            add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widgets') );

            add_action( 'pre_get_posts', array( $this, 'order_chat_by_last_mess') ); 

            add_action( 'manage_conversation_posts_custom_column' , array( $this, 'init_columns'), 10, 2 );
            add_filter( 'manage_conversation_posts_columns' , array( $this, 'add_columns') );

            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'add_action_links') );
        }

        public function wpcf7_save_message($contact_form, $result){
            $submission = WPCF7_Submission :: get_instance() ;
            $posted_data = $submission->get_posted_data() ;

            $form_to_save = get_option('cf7_form');
            $current_form = $posted_data['_wpcf7'];

            if($form_to_save == $current_form && $result["status"] == "mail_sent"){

                $senders = $posted_data['email'];
                $message = "";

                foreach($posted_data as $key => $value){
                    if (strpos($key, '_wpcf7') === false && strpos($key, '_WPCF7') === false) {
                        $message .= strtoupper($key).": ".$value."\n";
                    }
                    //$message .= "\n\n".__( 'Conversations', AIO_ME_TEXT );
                }

                //$message = print_r($posted_data, true); //$posted_data['message'];

                $chat = aiom_get_chat($senders, $senders, 'CF7');
                aiom_add_message( $chat, 'user', $message );
            }

        }

        public function order_chat_by_last_mess($query){

            global $pagenow, $typenow;  

            if( 'edit.php' == $pagenow && $typenow == 'conversation') {
                $query->set('meta_key', 'chat_last_mess');
                $query->set('orderby', 'meta_value_num');
            }

        }

        public function dashboard_widgets() {

            wp_add_dashboard_widget('conversations-widget', __( 'Conversations', AIO_ME_TEXT ), array($this, 'custom_widget_conversations'));

        }

        public function custom_widget_conversations() {
            $output = __( 'No conversations to show.', AIO_ME_TEXT );
            $conversations =  get_posts(array(
                'post_type' => 'conversation', 
                'order' => 'DESC', // ASC is the other option    
                'posts_per_page' => '-1', // Let's show them all.   
            ));

            if(!empty($conversations)){
                $output = '<table class="elements-list">';
                foreach($conversations as $conversation){
                    /*
$args = array(
    'posts_per_page'   => 1,
    'orderby'          => 'date',
    'order'            => 'DESC',
    'meta_key'         => 'chat_id',
    'meta_value'       => $conversation->ID,
    'post_type'        => 'message',
);
$messages = get_posts( $args );
*/

                    $last_message_date = date('H:i d/m/Y', strtotime(get_post_meta($conversation->ID, 'chat_last_mess')[0]));
                    //get_the_date( "H:i d/m/Y", $messages[0]->ID );

                    $output .= '<tr'.(get_post_meta($conversation->ID,'chat_new',true) ? ' class="alternative"' : '').'><td><a href="'.get_edit_post_link($conversation->ID).'" title="'.$conversation->post_title.'">'.$conversation->post_title.'</a></td><td>'.$last_message_date.'</td><td>'.get_post_meta($conversation->ID, 'chat_info', true).'</td></tr>';
                }
                $output .= '</table>';
            }
            echo $output;
        }

        /* Display custom column */
        public function init_columns( $column, $post_id ) {
            if ($column == 'source'){
                echo get_post_meta($post_id, 'chat_source', true);
            }
            if ($column == 'new_messages'){
                if(get_post_meta($post_id, 'chat_new', true) == 1){
                    echo '<span class="message-badge new"></span>';
                } else {
                    echo '<span class="message-badge"></span>';
                }
            }
            if ($column == 'reason'){
                echo get_post_meta($post_id, 'chat_info', true);
            }
            if ($column == 'last_message'){

                /*
$args = array(
    'posts_per_page'   => 1,
    'orderby'          => 'date',
    'order'            => 'DESC',
    'meta_key'         => 'chat_id',
    'meta_value'       => $post_id,
    'post_type'        => 'message',
);
$messages = get_posts( $args );
		if($messages){
        echo get_the_date( "H:i d/m/Y", $messages[0]->ID );
		}
		*/
                echo date('H:i d/m/Y', strtotime(get_post_meta($post_id, 'chat_last_mess')[0]));

            }
            if ($column == 'close_chat'){
                echo '<button class="button button-primary ajax-btn" data-action="close" data-id="'.$post_id.'">' . __( 'End conversation', AIO_ME_TEXT ) . '</button>';
            }
        }

        /* Add custom column to post list */
        public function add_columns( $columns ) {
            unset($columns['date']);

            return array_merge( $columns, 
                               array( 'new_messages' => __( 'New messages', AIO_ME_TEXT ) ),
                               array( 'last_message' => __( 'Last message', AIO_ME_TEXT ) ),
                               array( 'reason' => __( 'Reason', AIO_ME_TEXT ) ),
                               array( 'source' => __( 'Source', AIO_ME_TEXT ) ),
                               array( 'close_chat' => __( 'End conversation', AIO_ME_TEXT ) ) );
        }

        public function init_capabilities(){
            $enabled_roles = ( get_option('user_roles') ? get_option('user_roles') : array('administrator') );

            if(!in_array('administrator', $enabled_roles)){
                $enabled_roles[] = 'administrator';
            }

            foreach($enabled_roles as $ruolo){
                $admins = get_role( $ruolo );

                if($admins){
                    $admins->add_cap( 'delete_conversations' ); 
                    $admins->add_cap( 'delete_others_conversations' ); 
                    $admins->add_cap( 'delete_private_conversations' ); 
                    $admins->add_cap( 'delete_published_conversations' ); 
                    $admins->add_cap( 'edit_conversations' ); 
                    $admins->add_cap( 'edit_others_conversations' ); 
                    $admins->add_cap( 'edit_private_conversations' ); 
                    $admins->add_cap( 'edit_published_conversations' ); 
                    $admins->add_cap( 'read_private_conversations' ); 
                    //$admins->remove_cap( 'create_conversations' ); 

                    //$admins->add_cap( 'publish_conversations' ); 

                    /*
                    $admins->add_cap( 'delete_messages' ); 
                    $admins->add_cap( 'delete_others_messages' ); 
                    $admins->add_cap( 'delete_private_messages' ); 
                    $admins->add_cap( 'delete_published_messages' ); 
                    $admins->add_cap( 'edit_messages' ); 
                    $admins->add_cap( 'edit_others_messages' ); 
                    $admins->add_cap( 'edit_private_messages' ); 
                    $admins->add_cap( 'edit_published_messages' ); 
                    $admins->add_cap( 'read_private_messages' ); 

                    $admins->add_cap( 'publish_messages' ); 
                    */
                }
            }

        }

        public function init_post_types(){

            $labels = array(
                'name'               => _x( 'Conversations', 'post type general name', AIO_ME_TEXT ),
                'singular_name'      => _x( 'Conversation', 'post type singular name', AIO_ME_TEXT ),
                'menu_name'          => _x( 'Conversations', 'post type general name', AIO_ME_TEXT ),
                'name_admin_bar'     => _x( 'Conversation', 'add new on admin bar', AIO_ME_TEXT ),
                'add_new'            => _x( 'New Conversation', 'conversation', AIO_ME_TEXT ),
                'add_new_item'       => __( 'Add New Message', AIO_ME_TEXT ),
                'new_item'           => __( 'View Conversation', AIO_ME_TEXT ),
                'edit_item'          => __( 'View Conversation', AIO_ME_TEXT ),
                'view_item'          => __( 'View Conversation', AIO_ME_TEXT ),
                'all_items'          => __( 'All Conversations', AIO_ME_TEXT ),
                'search_items'       => __( 'Search Conversations', AIO_ME_TEXT ),
                'parent_item_colon'  => __( 'Parent Conversation:', AIO_ME_TEXT ),
                'not_found'          => __( 'No Conversation.', AIO_ME_TEXT ),
                'not_found_in_trash' => __( 'No Conversation.', AIO_ME_TEXT )
            );

            $args = array(
                'labels'             => $labels,
                'description'        => __( 'Description.', AIO_ME_TEXT ),
                'public'             => false,
                'publicly_queryable' => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => false,
                'capability_type'    => array('conversation', 'conversations'),
                'map_meta_cap'       => true,
                'has_archive'        => false,
                'hierarchical'       => false,
                'menu_position'      => null,
                'menu_icon'	         => 'dashicons-format-chat',
                'supports'           => array( 'title' )
            );

            register_post_type( 'conversation', $args );

            $args = array(
                'public'             => false,
                'publicly_queryable' => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => false,
                'capability_type'    => array('message', 'messages'),
                'map_meta_cap'       => true,
                'has_archive'        => false,
                'hierarchical'       => false,
                'menu_position'      => null,
                'supports'           => array( 'title', 'editor' )
            );

            register_post_type( 'message', $args );

        }

        public function init_options_page() {
            //remove_submenu_page('edit.php?post_type=conversation', 'post-new.php?post_type=conversation' );

            add_submenu_page( 'edit.php?post_type=conversation', __( 'All-in-one Messenger settings', AIO_ME_TEXT ), __( 'Settings', AIO_ME_TEXT ), 'administrator', 'aio-messenger', array( $this, 'render_options_page') );
            add_action( 'admin_init', array( $this, 'init_options_settings') );
        }

        public function init_options_settings() {
            register_setting( 'aio-messenger', 'facebook_callback_token' );
            register_setting( 'aio-messenger', 'facebook_token' );
            register_setting( 'aio-messenger', 'user_roles' );
            register_setting( 'aio-messenger', 'cf7_form' );
            register_setting( 'aio-messenger', 'cf7_page' );
            register_setting( 'aio-messenger', 'cf7_sender_name' );
            register_setting( 'aio-messenger', 'cf7_sender_email' );
        }

        public function render_options_page() {
            include_once( AIO_ME_PART . 'options_page.php' );
        }

        public function init_chat_metabox( $post_type ) {			
            $post_types = array( 'conversation' );

            if ( in_array( $post_type, $post_types ) ) {

                remove_meta_box( 'submitdiv', $post_type, 'side' );

                add_meta_box(
                    'conversation_metabox',
                    __( 'Messagges', AIO_ME_TEXT ),
                    array( $this, 'render_chat_metabox' ),
                    $post_type,
                    'normal',
                    'high'
                );

                add_meta_box(
                    'conversation_metabox_side',
                    __( 'Informations', AIO_ME_TEXT ),
                    array( $this, 'render_chat_metabox_2' ),
                    $post_type,
                    'side',
                    'low'
                );

            }
        }

        public function render_chat_metabox( $post ) {
            include_once( AIO_ME_PART . 'chat_metabox.php' );
        }

        public function render_chat_metabox_2( $post ) {
            $chat_source = get_post_meta( $post->ID, 'chat_source', true );
            $first_message_date = get_the_date(get_option('date_format'), $post->ID);
            $reason = get_post_meta( $post->ID, 'chat_info', true );

            if($chat_source == 'CF7'){ ?>
<div class="chat-info">
    <span class="user-info"><a href="mailto:<?php echo get_post_meta( $post->ID, 'chat_id', true );?>"><?php echo get_post_meta( $post->ID, 'chat_id', true );?></a><br><br><strong><?php _e( 'Source', AIO_ME_TEXT );?>:</strong> <?php echo $chat_source;?><br><strong><?php _e( '1st message', AIO_ME_TEXT );?>:</strong> <?php echo $first_message_date;?><br><strong><?php _e( 'Reason', AIO_ME_TEXT );?>:</strong> <?php echo $reason;?></span>
</div>
<?php }  elseif($chat_source == 'Facebook'){

                $facebook_id = get_post_meta( $post->ID, 'chat_id', true );

                if($facebook_id){

                    $apiKey = get_option('facebook_token');

                    $user = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://graph.facebook.com/v2.6/'.$facebook_id.'?fields=name,profile_pic&access_token='.$apiKey ) ), true);
                    //$user = json_decode(file_get_contents('https://graph.facebook.com/v4.0/'.$facebook_id), true);


                    $user_name = $user['name'];
                    $user_image = $user['profile_pic'];
                    $user_url = '#';//$user['link'];//"https://www.facebook.com/profile.php?id=" . $facebook_id;
                    //print_r($user);
?>
<div class="chat-info">
    <div class="user-image" style="background-image: url(<?php echo $user_image;?>);"></div>
    <span class="user-info"><?php echo $user_name;?><br><br><strong><?php _e( 'Source', AIO_ME_TEXT );?>:</strong> <?php echo $chat_source;?><br><strong><?php _e( '1st message', AIO_ME_TEXT );?>:</strong> <?php echo $first_message_date;?><br><strong><?php _e( 'Reason', AIO_ME_TEXT );?>:</strong> <?php echo $reason;?></span>
</div>

<?php  
                    //include_once( AIO_ME_PART . 'chat_metabox_2.php' );
                }

            }

        }

        public function init_chat_callbacks(){
            include_once( AIO_ME_PART . 'callback_facebook.php' );
            include_once( AIO_ME_PART . 'callback_cf7.php' );
        }

        public function init_ajax_messenger(){

            if( !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && isset($_REQUEST['ajax_messenger']) ){

                $azione = isset($_REQUEST['azione']) ? sanitize_text_field($_REQUEST['azione']) : false;
                $chat_id = isset($_REQUEST['chat_id']) ? sanitize_text_field($_REQUEST['chat_id']) : false;
                $messaggio = isset($_REQUEST['messaggio']) ? stripslashes($_REQUEST['messaggio']) : false;
                $count_messaggi = isset($_REQUEST['count']) ? sanitize_text_field($_REQUEST['count']) : false;

                if( !$azione ){
                    die();
                }


                if($azione == 'send' && $messaggio){
                    $source = get_post_meta( $chat_id, 'chat_source', true );
                    $mittente = get_post_meta( $chat_id, 'chat_id', true );

                    if($source == 'CF7'){

                        $email_from = get_option('cf7_sender_email') ? get_option('cf7_sender_email') : get_bloginfo( 'admin_email' );
                        $name_from = get_option('cf7_sender_name') ? get_option('cf7_sender_name') : get_bloginfo( 'name' );
                        $subject = __( 'New response to your request', AIO_ME_TEXT ).' - '.get_bloginfo( 'name' );

                        $email_messaggio = nl2br($messaggio) . "<br><br><hr>" . __( 'Do not reply directly to this email, reply with your email address using the form in this link', AIO_ME_TEXT ) . ":<br><a href='".(get_option('cf7_page') ? get_permalink(get_option('cf7_page')) : site_url() )."' target='_blank'>" . __( 'Reply to this message', AIO_ME_TEXT ) . "</a>";

                        $headers[] = 'Content-Type: text/html; charset=UTF-8';
                        $headers[] = 'From: '.$name_from.' <'.$email_from.'>';
                        //

                        wp_mail( $mittente, $subject, $email_messaggio, $headers );	

                        aiom_add_message( $chat_id, 'bot',htmlspecialchars($messaggio) );

                    } elseif($source == 'Facebook'){

                        $apiKey = get_option('facebook_token');
                        $facebook = new bm_Messenger($apiKey);

                        aiom_add_message( $chat_id, 'bot',htmlspecialchars($messaggio) );

                        //$result = $facebook->sendMessage($mittente, stripslashes($messaggio));

                        $result = $facebook->sendMessage($mittente, 'text', $messaggio);

                        update_post_meta( $chat_id, 'chat_step', 9 );

                    }



                    //return true;
                }


                if($azione == 'close'){
                    update_post_meta( $chat_id, 'chat_step', 0 );
                    wp_trash_post($chat_id);

                    return true;
                }

                if($azione == 'aggiorna'){

                    update_post_meta($chat_id, 'chat_new', 0);

                    $args = array(
                        'posts_per_page'   => -1,
                        'orderby'          => 'date',
                        'order'            => 'ASC',
                        'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                'key'   => 'chat_id',
                                'value' => $chat_id,
                            ),
                            array(
                                'key'   => 'sender',
                                'value' => 'user',
                            )
                        ),
                        'post_type'        => 'message',
                    );
                    $messages = get_posts( $args );


                    if(count($messages) > $count_messaggi){
                        $conteggio_messaggi_nuovi = count($messages) - $count_messaggi;
                        $nuovi_messaggi = array();
                        for($i=$conteggio_messaggi_nuovi; $i > 0 ; --$i ){
                            $messaggio_nuovo = count($messages) - $i;
                            $nuovi_messaggi[] = array( 'sender' => 'user', 'message' => nl2br( htmlspecialchars_decode($messages[$messaggio_nuovo]->post_content)) );
                        }
                        echo json_encode($nuovi_messaggi);
                    }

                }

                if($azione == 'check_new'){

                    //$now = isset($_REQUEST['now']) ? $_REQUEST['now'] : false;

                    $args = array(
                        'posts_per_page'   => -1,
                        'post_type'        => 'conversation',
                        'meta_key'		=> 'chat_new',
                        'meta_value'	=> 1
                    );

                    $messaggi = get_posts( $args );
                    if($messaggi){

                        $nuovi_messaggi = array();
                        foreach($messaggi as $messaggio){

                            $nuovi_messaggi[] = array( 'id' => $messaggio->ID, 'mittente' => $messaggio->post_title );

                        }
                        echo json_encode($nuovi_messaggi);

                    }

                }

            }
            die();

        }

        public function init_admin_notices(){

            $args = array(
                'posts_per_page'   => -1,
                'post_type'        => 'conversation',
                'meta_key'		=> 'chat_new',
                'meta_value'	=> 1
            );

            $messaggi = get_posts( $args );
            if($messaggi){
                $nuovi_messaggi = array();

                foreach($messaggi as $messaggio){

                    $nuovi_messaggi[] = '<a href="'.get_edit_post_link($messaggio->ID).'">'.$messaggio->post_title.'</a> ('.date('H:i', strtotime(get_post_meta($messaggio->ID, 'chat_last_mess')[0] )).')'; //array( 'id' => $messaggio->ID, 'mittente' => $messaggio->post_title );

                }

?>
<div class="notice notice-success is-dismissible">
    <p><?php _e("New messages in conversations with:", AIO_ME_TEXT); ?><br><span class="new-messages-notification"><?php echo implode(', ',$nuovi_messaggi);?></span></p>
</div>
<?php
                //echo sprintf(__("New messages in conversations with:<br>%s", AIO_ME_TEXT), implode(', ',$nuovi_messaggi));

            }








            $apiKey = ( get_option('facebook_token') ? get_option('facebook_token') : null );

            // Instances the Facebook class
            $facebook = new bm_Messenger($apiKey);
            if(empty(get_option('facebook_token')) || empty(get_option('facebook_callback_token')) || !$facebook){
?>
<div class="notice notice-error">
    <p><?php _e( '<strong>ALL-IN-ONE MESSENGER:</strong><br>The plugin is active but the chat does not work. Visit the settings page and enter / verify integration information.', AIO_ME_TEXT ); ?> - <a href="<?php menu_page_url('aio-messenger');?>"><?php _e( 'Settings', AIO_ME_TEXT ); ?></a></p>
</div>
<?php
                                                                                                                 }
        }

        public function add_action_links ( $links ) {
            $mylinks = array(
                '<a href="' . menu_page_url('aio-messenger', false) . '">'.__( 'Settings', AIO_ME_TEXT ).'</a>',
            );
            return array_merge( $links, $mylinks );
        }

        public function init_admin_style_script($hook){
            global $current_screen;

            wp_register_style( 'aio-messenger-emoji', AIO_ME_ASSETS . 'emoji/emojionearea.min.css', false, '1.0.0' );
            wp_enqueue_style( 'aio-messenger-emoji' );

            wp_register_style( 'aio-messenger', AIO_ME_ASSETS . 'style.css', false, '1.0.0' );
            wp_enqueue_style( 'aio-messenger' );

            wp_enqueue_script( 'aio-messenger-emoji', AIO_ME_ASSETS . 'emoji/emojionearea.min.js' );

            wp_enqueue_script( 'aio-messenger', AIO_ME_ASSETS . 'scripts.js' );

            wp_localize_script(
                'aio-messenger',
                'aio_messenger',
                array( 
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'chat_id' => ( ( 'post.php' == $hook || 'post-new.php' == $hook ) && 'conversation' == $current_screen->post_type ? get_the_ID() : 0 ),
                    'plugin_assets' => AIO_ME_ASSETS,
                    'wp_admin' => admin_url(),
                )
            );
        }


    }
    new Aio_Messenger();
}