<?php
/**
 * Copie ce fichier en contact_smtp_config.php (non versionné) et remplis les champs.
 * Boîte mail : créée dans l’espace client IONOS (même adresse que l’expéditeur).
 *
 * IONOS (exemples) :
 *   Hôte : smtp.ionos.fr  ou  smtp.ionos.com
 *   Port 465 + SSL  →  'encryption' => 'ssl'
 *   Port 587 + TLS  →  'encryption' => 'tls', 'port' => 587
 */

return [
    'enabled'     => true,
    'host'        => 'smtp.ionos.fr',
    'port'        => 465,
    'encryption'  => 'ssl', // 'ssl' (465) ou 'tls' (587)
    'user'        => 'contact@votredomaine.fr',
    'pass'        => 'mot_de_passe_de_la_boite_mail',
    'from'        => 'contact@votredomaine.fr',
];
