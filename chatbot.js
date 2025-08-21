// Wait until the DOM is fully loaded
jQuery(document).ready(function($) {

    // Function to add a message to the chat window
    function addMessage(sender, text) {
        // Determine CSS class based on sender type (user or bot)
        var messageClass = sender === 'user' ? 'user-message' : 'bot-message'; // user-message for user, bot-message for bot
        // Append message div to chat window
        $('#cca-chat-window').append('<div class="' + messageClass + '">' + text + '</div>'); // Add message to chat
        // Scroll chat window to bottom
        $('#cca-chat-window').scrollTop($('#cca-chat-window')[0].scrollHeight); // Auto-scroll to latest message
    }

    // Handle click on Send button
    $('#cca-send-btn').click(function() {
        // Get user input and trim whitespace
        var message = $('#cca-user-input').val().trim(); // Read user input
        if (!message) return; // Do nothing if input is empty
        addMessage('user', message); // Display user message in chat
        $('#cca-user-input').val(''); // Clear input box

        // Send message to server via AJAX
        $.post(cca_ajax.ajax_url, {
            action: 'cca_send_message', // WordPress AJAX action
            message: message,            // User's message
            nonce: cca_ajax.nonce        // Security nonce
        }, function(response) {
            // Handle successful AJAX response
            if (response.success) {
                addMessage('bot', response.data.response); // Display bot response
            } else {
                addMessage('bot', 'Sorry, there was an error.'); // Error message
            }
        }).fail(function() {
            // Handle AJAX failure (e.g., network/server error)
            addMessage('bot', 'Server error. Please try later.'); // Server error fallback
        });
    });

    // Send message when Enter key is pressed in input
    $('#cca-user-input').keypress(function(e) {
        if (e.which == 13) { // Enter key code
            $('#cca-send-btn').click(); // Trigger click event
            return false; // Prevent default Enter behavior (newline)
        }
    });
});
