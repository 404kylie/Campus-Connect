<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to main login page
header("Location: index.php");
exit();
?>