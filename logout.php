<?php
require_once 'config.php';

// Distruggi la sessione
session_unset();
session_destroy();

// Reindirizza al login
header('Location: login.php');
exit();
?>