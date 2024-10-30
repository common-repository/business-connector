<?php
// FUNCTIONS FILE BUSINESS MESSENGER

function aiom_get_chat($chat_id, $nome, $source){

    $args = array(
        'numberposts' => 1,
        'post_type'   => 'conversation',
        'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash') ,
        'meta_query' => array(
            array(
                'key'   => 'chat_id',
                'value' => $chat_id,
            )
        )
    );

    $messaggio = get_posts( $args );

    if($messaggio){
        wp_update_post(array( 'ID' => $messaggio[0]->ID, 'post_status' => 'publish' ));
        return $messaggio[0]->ID;
    } else {

        $args = array(
            'post_type' => 'conversation',
            'post_title' => $nome,
            'post_status'   => 'publish',
        );

        $new_chat = wp_insert_post( $args );

        add_post_meta($new_chat, 'chat_id', $chat_id, true);
        add_post_meta($new_chat, 'chat_step', 0, true);
        add_post_meta($new_chat, 'chat_source', $source, true);
        add_post_meta($new_chat, 'chat_info', __( 'New chat', AIO_ME_TEXT ), true);

        add_post_meta($new_chat, 'chat_new', 1, true);

        $date_timezone = new DateTime("now", new DateTimeZone(get_option('timezone_string')) );
        $date = $date_timezone->format('YmdHis');

        add_post_meta($new_chat, 'chat_last_mess', $date, true);


        bm_add_message($new_chat, 'user', __( '- THE CUSTOMER HAS STARTED A CONVERSATION -', AIO_ME_TEXT ) );

        return $new_chat;
    }
}

function aiom_add_message($chat, $sender, $message, $date = null){

    if(!$date){
        //$date = date('YmdHis', time());
        $date_timezone = new DateTime("now", new DateTimeZone(get_option('timezone_string')) );
        $date = $date_timezone->format('YmdHis');
    } else {
        $date = date('YmdHis', $date);
    }

    $args = array(
        'post_type' => 'message',
        'post_status'   => 'publish',
        'post_content'  => htmlspecialchars($message),
    );

    $new_message = wp_insert_post( $args );

    add_post_meta($new_message, 'chat_id', $chat, true);
    add_post_meta($new_message, 'sender', $sender, true);

    update_post_meta( $chat, 'chat_new', 1);

    update_post_meta( $chat, 'chat_last_mess', $date);
}