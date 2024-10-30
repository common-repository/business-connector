<?php

$current_conversation_id = get_the_ID();

//echo get_post_meta($current_conversation_id, 'chat_last_mess')[0] . '<hr>';

$args = array(
    'posts_per_page'   => -1,
    'orderby'          => 'date',
    'order'            => 'ASC',
    'meta_key'         => 'chat_id',
    'meta_value'       => $current_conversation_id,
    'post_type'        => 'message',
);
$messages = get_posts( $args );

//echo get_post_meta( $current_conversation_id, 'chat_step', true) . "<hr>";
//echo get_post_meta( $current_conversation_id, 'chat_new', true) . "<hr>";

echo '<div class="row messages">';
$date_group = '';
foreach($messages as $message){
    $sender = get_post_meta( $message->ID, 'sender', true );
    $message_content = nl2br( htmlspecialchars_decode($message->post_content) );

    //$date = get_the_date( "d/m/Y H:i", $message->ID );

    $date = get_the_date( "d/m/Y", $message->ID );

    if( empty($date_group) ){
        $date_group = $date;
        if( date('Ymd') == get_the_date( "Ymd", $message->ID ) ){
            echo '<span class="date-separator">'.__( 'Today', AIO_ME_TEXT ).'</span>';
        } else {
            echo '<span class="date-separator">'.$date.'</span>';
        }
    }

    if($date_group != $date){
        $date_group = $date;
        if( date('Ymd') == get_the_date( "Ymd", $message->ID ) ){
            $date = __( 'Today', AIO_ME_TEXT );
        }
        echo '<span class="date-separator">'.$date.'</span>';
    }

    $time = get_the_date( "H:i", $message->ID );

    echo '<div class="col-md-12 message ' . $sender . '"><div class="message-content">' . $message_content . '</div><span class="date-time">'. $time .'</span></div>';
}
echo '</div>'; 

wp_nonce_field( 'global_notice_nonce', 'global_notice_nonce' );
?>
<div class="chat-reply">
    <label><?php _e( 'Reply', AIO_ME_TEXT );?>
        <textarea id="messaggio" name="messaggio" rows="8"></textarea>
    </label>
    <div id="publishing-action">
        <button class="button button-secondary button-large ajax-btn" data-action="close"><?php _e( 'End conversation', AIO_ME_TEXT );?></button>
        <button class="button button-primary button-large ajax-btn" data-action="send" disabled="disabled"><?php _e( 'Send message', AIO_ME_TEXT );?></button>
        <input type="hidden" id="count_messaggi" value="<?php 
                                                        $args = array(
                                                            'posts_per_page'   => -1,
                                                            'orderby'          => 'date',
                                                            'order'            => 'DESC',
                                                            'meta_query' => array(
                                                                'relation' => 'AND',
                                                                array(
                                                                    'key'   => 'chat_id',
                                                                    'value' => $current_conversation_id,
                                                                ),
                                                                array(
                                                                    'key'   => 'sender',
                                                                    'value' => 'user',
                                                                )
                                                            ),
                                                            'post_type'        => 'message',
                                                        );
                                                        $messages = get_posts( $args );
                                                        echo count($messages);
                                                        ?>">
    </div>
</div>
<script>
    window.onbeforeunload = function() {};
    jQuery(function($) {
        $(window).load(function() {
            $("html, body").animate({ scrollTop: $(document).height() }, 500);
        });
    });
</script>