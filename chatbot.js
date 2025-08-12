jQuery(document).ready(function($) {
    function addMessage(sender, text) {
        var messageClass = sender === 'user' ? 'user-message' : 'bot-message';
        $('#cca-chat-window').append('<div class="' + messageClass + '">' + text + '</div>');
        $('#cca-chat-window').scrollTop($('#cca-chat-window')[0].scrollHeight);
    }

    $('#cca-send-btn').click(function() {
        var message = $('#cca-user-input').val().trim();
        if (!message) return;
        addMessage('user', message);
        $('#cca-user-input').val('');

        $.post(cca_ajax.ajax_url, {
            action: 'cca_send_message',
            message: message,
            nonce: cca_ajax.nonce
        }, function(response) {
            if (response.success) {
                addMessage('bot', response.data.response);
            } else {
                addMessage('bot', 'Sorry, there was an error.');
            }
        }).fail(function() {
            addMessage('bot', 'Server error. Please try later.');
        });
    });

    // Send message on Enter key press
    $('#cca-user-input').keypress(function(e) {
        if (e.which == 13) {
            $('#cca-send-btn').click();
            return false;
        }
    });
});
