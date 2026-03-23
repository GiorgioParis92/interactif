<?php
/* =====================================================
   MIA — CONNEXION BASE DE DONNÉES
   includes/connexion.php

   Copiez ce fichier en connexion.php et remplissez
   vos identifiants. connexion.php est gitignored.
===================================================== */

$_host = $_SERVER['SERVER_NAME'] ?? 'localhost';
$is_local = in_array($_host, ['localhost', '127.0.0.1', '::1'])
         || str_ends_with($_host, '.local');

if ($is_local) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', '');
    define('DB_USER', '');
    define('DB_PASS', '');
} else {
    define('DB_HOST', '');
    define('DB_NAME', '');
    define('DB_USER', '');
    define('DB_PASS', '');
}

define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('<pre style="font-family:monospace;padding:2rem;color:#E8492A">
<strong>Erreur de connexion à la base de données</strong>

' . htmlspecialchars($e->getMessage()) . '

Environnement détecté : ' . ($is_local ? 'LOCAL' : 'PRODUCTION') . '
Hôte tenté : ' . DB_HOST . '
</pre>');
}
