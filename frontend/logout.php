<?php
session_start();
session_unset();
session_destroy();
header("Location: index.php" . (isset($_GET['timeout']) ? '?timeout=1' : ''));
exit();
