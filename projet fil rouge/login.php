<?php

declare(strict_types=1);

require_once __DIR__ . "/auth.php";

start_session();

if (admin_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login    = trim($_POST["login"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (strcasecmp($login, ADMIN_LOGIN) === 0 && hash_equals(ADMIN_PASSWORD, $password)) {
        $_SESSION["admin_logged_in"] = true;
        header("Location: index.php");
        exit;
    }
    $error = "Identifiant ou mot de passe incorrect.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — Hôtel PMS</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="login-screen">
    <div class="login-brand">
        <div class="wordmark">Hôtel <span>PMS</span></div>
        <div class="brand-divider" aria-hidden="true"></div>
<blockquote>« Chaque séjour commence par une bonne réservation. »</blockquote>
        <div class="caption">Back-office de gestion hôtelière &middot; Maroc</div>
    </div>
    <div class="login-panel">
        <form method="POST" class="login-card">
            <h1>Connexion</h1>
            <p class="intro">Accédez au tableau de bord de gestion des réservations.</p>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="login">Identifiant</label>
                <input type="text" id="login" name="login" required autofocus>
            </div>
            <div class="form-group mt-3">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn primary block">Se connecter</button>
            </div>

            <p class="tip">Accès de démonstration : <strong>admin</strong> / <strong>admin123</strong></p>
        </form>
    </div>
</div>
</body>
</html>