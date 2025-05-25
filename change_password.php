<?php
session_start();
require 'db.php';

if (!isset($_SESSION["username"]) || !isset($_SESSION["must_change_password"])) {
    header("Location: index.php");
    exit;
}

if (!$_SESSION["must_change_password"]) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["new_password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';

    if ($new_password === '' || $confirm_password === '') {
        $error = "Inserisci entrambe le password.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Le password non coincidono.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = FALSE WHERE id = ?");
        $stmt->execute([$hashed, $_SESSION["user_id"]]);
        $success = "Password cambiata con successo! Ora puoi fare il login.";
        // Aggiorna la sessione
        $_SESSION["must_change_password"] = false;
        // Opzionale: fai logout automatico per rifare il login
        session_destroy();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Cambia Password</title>
    <style>
        body { font-family: Arial; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px #ccc; width: 350px; text-align: center; }
        input { width: 100%; padding: 8px; margin: 10px 0; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<div class="box">
    <h2>Cambia Password</h2>

    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <?php if ($success) echo "<p class='success'>$success</p>"; ?>

    <?php if (!$success): ?>
    <form method="post">
        <input type="password" name="new_password" placeholder="Nuova password" required><br>
        <input type="password" name="confirm_password" placeholder="Conferma password" required><br>
        <input type="submit" value="Cambia password">
    </form>
    <?php else: ?>
        <a href="index.php">Vai al login</a>
    <?php endif; ?>
</div>
</body>
</html>
