<?php
// Script temporaire pour générer un mot de passe haché
$mot_de_passe = '12345678'; // Remplace par le mot de passe de ton choix
$hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
echo $hashed_password;