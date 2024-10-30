<?php

if(isset($_GET['chat_bot']) && $_GET['chat_bot'] == 'facebook'){

    $apiKey = get_option('facebook_token');
    $hubVerifyToken = get_option('facebook_callback_token');

    if (isset($_REQUEST['hub_verify_token']) && $_REQUEST['hub_verify_token'] === $hubVerifyToken) {
        echo $_REQUEST['hub_challenge'];
        exit;
    }

    $facebook = new bm_Messenger($apiKey);

    //$facebook->respondSuccess();

    $text = $facebook->Text() ? $facebook->Text() : '';
    $attachments = $facebook->Attachments() ? $facebook->Attachments() : array();

    $payload = $facebook->getPayload();
    $chat_id = $facebook->ChatID();

    $message = "";
    $result = "";

    $utente = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://graph.facebook.com/v2.6/'.$chat_id.'?fields=name&access_token='.$apiKey ) ), true);

    $chat = aiom_get_chat($chat_id, $utente['name'], 'Facebook');
    $chat_step = get_post_meta( $chat, 'chat_step', true );

    if( !empty($attachments) && !is_null($chat_id)) {
        foreach($attachments as $attachment){
            $path      = parse_url($attachment["url"], PHP_URL_PATH);       // get path from url
            $extension = pathinfo($path, PATHINFO_EXTENSION); // get ext from path
            $extensions = array(
                'txt' => __('text file',AIO_ME_TEXT) . ' <span class="dashicons dashicons-media-text"></span>',
            );
            $message = '<a href="'.$attachment["url"].'" target="blank">'.sprintf(__('View %s',AIO_ME_TEXT),($extensions[$extension] ? $extensions[$extension] : $extension ) ).'</a>';
            if(is_array(getimagesize($attachment["url"]))){
                $message = '<a href="'.$attachment["url"].'" target="blank"><img src="'.$attachment["url"].'"></a>';
            }
            aiom_add_message( $chat, 'user', $message );
        }
    }

    if( (!empty($text) || !empty($payload)) && !is_null($chat_id)) {
        if(!empty($text)){

            $text = htmlspecialchars( $facebook->Text() );

            $text = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($m) {
                $char = current($m);
                $utf = iconv('UTF-8', 'UCS-4', $char);
                return sprintf("&#x%s;", ltrim(strtoupper(bin2hex($utf)), "0"));
            }, $text);

        }

        if (has_action('chat_bot_action')) {
            do_action( 'chat_bot_action', $chat, $text, $payload );
        } else {

            if( empty( $facebook->getPayload() ) ){
                aiom_add_message( $chat, 'user', $text );
            }

            $result = $facebook->sendChatAction($chat_id, 'mark_seen');

        } 

    }

    $facebook->respondSuccess();

    exit;
}

