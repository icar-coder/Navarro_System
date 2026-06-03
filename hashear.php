<?php
require_once 'config/db.php';
$db = (new Database())->getConnection();
$email = 'admin@admin.com'; // El email que usarás para login
$nuevaPass = password_hash('123456', PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE usuario SET contrasenia = ? WHERE email = ?");
$stmt->bind_param("ss", $nuevaPass, $email);
$stmt->execute();
echo "Contraseña actualizada. Elimina este archivo.";
?>