<?php
// File: includes/header.php
// Header HTML comune per tutte le pagine

$page_title = $page_title ?? 'WordPress Checklist';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlEscape($page_title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" type="image/png" href="/img/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/img/favicon.svg" />
<link rel="shortcut icon" href="/img/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/img/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="WPChecklist" />
<link rel="manifest" href="/img/site.webmanifest" />
</head>
<body class="bg-light">