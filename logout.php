<?php
session_start();
require 'includes/functions.php';

// Clear all session data
session_destroy();

// Redirect with a goodbye message
session_start();
redirect_with_message('index.php', 'You have been logged out successfully. Thank you for using BookNest!', 'success');
?>

<script>
    setTimeout(function() {
        location.reload();
    }, 30000); // Refresh every 30 seconds
</script>
