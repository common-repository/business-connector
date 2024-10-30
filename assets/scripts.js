jQuery(document).ready(function($) {
    
    $("#messaggio").emojioneArea({
    searchPlaceholder: "Cerca...",
    filtersPosition: "bottom",
        
  events: {
    /**
     * @param {jQuery} editor EmojioneArea input
     * @param {Event} event jQuery Event object
     */
    keyup: function (editor, event) {
        
        var empty = false;
            if ($(".emojionearea-editor").html().length == 0) {
                empty = true;
            }

        if (empty) {
            $('.button-primary.ajax-btn').attr('disabled', 'disabled');
        } else {
            $('.button-primary.ajax-btn').removeAttr('disabled');
        }
        
      //console.log(editor);
    },
    emojibtn_click: function (editor, event) {
        
        var empty = false;
            if ($(".emojionearea-editor").html().length == 0) {
                empty = true;
            }

        if (empty) {
            $('.button-primary.ajax-btn').attr('disabled', 'disabled');
        } else {
            $('.button-primary.ajax-btn').removeAttr('disabled');
        }
        
      //console.log(editor);
    },
  }
        
    });

    function htmlspecialchars(str) {
        if (typeof(str) == "string") {
            str = str.replace(/&/g, "&amp;"); /* must do &amp; first */
            str = str.replace(/"/g, "&quot;");
            str = str.replace(/'/g, "&#039;");
            str = str.replace(/</g, "&lt;");
            str = str.replace(/>/g, "&gt;");
        }
        return str;
    }

    $('.ajax-btn').click(function(e) {
        e.preventDefault();

        var azione = $( this ).attr( 'data-action' );
        var messaggio = $("#messaggio").val();
		
		var chat_id_ref = aio_messenger.chat_id;
		
		if(chat_id_ref == 0){
		   chat_id_ref = $( this ).attr( 'data-id' );
		 }

        $( ".button-primary.ajax-btn" ).attr("disabled", true);

        // This does the ajax request
        $.ajax({
            type: 'POST',
            url: aio_messenger.ajaxurl, // or example_ajax_obj.ajaxurl if using on frontend
            data: {
                'action': 'init_ajax_messenger',
                'ajax_messenger': 1, 
                'chat_id': chat_id_ref,
                'azione': azione,
                'messaggio': messaggio
            },
            success:function(data) {


                if(azione == 'send' && messaggio){
                    messaggio = htmlspecialchars(messaggio).replace(/(?:\r\n|\r|\n)/g, '<br>');
                    $(".messages").append( '<div class="col-md-12 message bot"><div class="message-content">'+ messaggio +'</div></div>' );
                    $("#messaggio").val(null);
					$(".emojionearea-editor").html('');
                    //$(".button.ajax-btn").attr("disabled", false);
                }
                if(azione == 'close'){
                    location.href = 'edit.php?post_type=conversation';
                }

                // This outputs the result of the ajax request
                //console.log(data);
            },
            error: function(errorThrown){
                console.log(errorThrown);
            }
        });  




    });



    //setInterval(ajax_live_chat, 500);

    function ajax_live_chat() {
        var count_messaggi = $("#count_messaggi").val();

        $.ajax({
            type: 'POST',
            dataType : "json",
            url: aio_messenger.ajaxurl, // or example_ajax_obj.ajaxurl if using on frontend
            data: {
                'action': 'init_ajax_messenger',
                'ajax_messenger': 1, 
                'chat_id': aio_messenger.chat_id,
                'azione': 'aggiorna',
                'count': count_messaggi
            },
            success:function(data) {

                var new_count = parseInt(count_messaggi);
                $.each( data, function( key, value ) {
                    new_count += 1;
                    $(".messages").append( '<div class="col-md-12 message user"><div class="message-content">'+ value.message +'</div></div>' );
                });
                $("#count_messaggi").val(new_count);
                
                ajax_live_chat();
            },
            error: function(errorThrown){
                console.log(errorThrown);
            }
        });

    }
    
    ajax_live_chat();

});

/*
jQuery(document).ready(function($){

    if (!window.Notification) {
        console.log('Browser does not support notifications.');
    } else {
        // check if permission is already granted
        if (Notification.permission === 'granted') {
            // show notification here

            $(document).ready(function() {
                setInterval(ajax_new_chat, 2000);
            });

        } else {
            // request permission from user
            Notification.requestPermission().then(function(p) {
                if(p === 'granted') {
                    // show notification here

                    $(document).ready(function() {
                        setInterval(ajax_new_chat, 2000);
                    });

                } else {
                    console.log('User blocked notifications.');
                }
            }).catch(function(err) {
                console.error(err);
            });
        }
    }

    var new_chat_ids = new Array();

    function ajax_new_chat() {

        $.ajax({
            type: 'POST',
            dataType : "json",
            url: aio_messenger.ajaxurl, // or example_ajax_obj.ajaxurl if using on frontend
            data: {
                'action': 'init_ajax_messenger',
                'ajax_messenger': 1, 
                'azione': 'check_new'
            },
            success:function(data) {

                $.each( data, function( key, value ) { 

                    if( new_chat_ids.indexOf( value.id ) == -1 ){

                        var img = aio_messenger.plugin_assets + 'facebook-notif-icon.png';
                        var text = value.mittente + ' ha inviato un messaggio...';
                        var notify = new Notification('Nuovo messaggio!', { body: text, icon: img });

                        notify.onclick = function (event) {
                            event.preventDefault();
                            window.open(aio_messenger.wp_admin + "post.php?post="+ value.id +"&action=edit", '_self');
                        };
                        new_chat_ids.push(value.id);
                    }
                });

            },
            error: function(errorThrown){
                console.log(errorThrown);
            }
        });

    }

});
*/