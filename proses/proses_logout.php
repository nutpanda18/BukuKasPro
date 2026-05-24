<?php
// Expire cookies instantly by setting their timestamp to 1 hour ago
setcookie('user_id', '', time() - 3600, "/");
setcookie('nama', '', time() - 3600, "/");
setcookie('role', '', time() - 3600, "/");

// Redirect the user completely back to the index landing page
header("Location: ../index.html");
exit;
?>