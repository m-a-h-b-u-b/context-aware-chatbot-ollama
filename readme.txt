Context Aware Chatbot
====================

A simple WordPress plugin that adds a context-aware chatbot using PHP sessions.

Installation:
1. Upload the 'context-chatbot' folder to your /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Add the shortcode [context_chatbot] into any post or page to display the chat widget.

Notes:
- This plugin uses PHP sessions to store conversation context per visitor session.
- For production use consider using persistent storage (user_meta, custom table) and sanitization/escaping for outputs.
