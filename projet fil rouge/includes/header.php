<?php

declare(strict_types=1);

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../auth.php";
require_admin();

$pdo->exec("SET NAMES utf8mb4");
$current_page = basename($_SERVER["PHP_SELF"]);
$page_title = $page_title ?? "Back-office";
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1a1512">
<title><?= htmlspecialchars($page_title) ?> — Hôtel PMS</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="app">
    <?php require __DIR__ . "/sidebar.php"; ?>
    <div class="main">
        <header class="topbar">
            <div>
                <div class="crumb">Back-office &middot; <?= htmlspecialchars($page_title) ?></div>
                <h2><?= htmlspecialchars($page_title) ?></h2>
            </div>
            <div class="top-actions">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="color:var(--ink-faint)"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span class="mono" id="liveClock" style="color:var(--ink-faint)"></span>
            </div>
        </header>
        <main class="content">
            <?php if ($flash): ?>
                <div class="alert <?= $flash["type"] === "error" ? "error" : "success" ?>">
                    <?= htmlspecialchars($flash["message"]) ?>
                </div>
            <?php endif; ?>