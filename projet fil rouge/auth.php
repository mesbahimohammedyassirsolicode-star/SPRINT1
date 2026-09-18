<?php

declare(strict_types=1);

const ADMIN_LOGIN    = "admin";
const ADMIN_PASSWORD = "admin123";

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function admin_logged_in(): bool
{
    start_session();
    return !empty($_SESSION["admin_logged_in"]);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function flash_set(string $message, string $type = "success"): void
{
    start_session();
    $_SESSION["flash"] = ["message" => $message, "type" => $type];
}

function flash_get(): ?array
{
    start_session();
    if (!empty($_SESSION["flash"])) {
        $flash = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        return $flash;
    }
    return null;
}