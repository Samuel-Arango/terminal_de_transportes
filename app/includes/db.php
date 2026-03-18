<?php

$host = getenv('DB_HOST') ?: 'sql202.infinityfree.com';
$user = getenv('DB_USER') ?: 'if0_40460125';
$pass = getenv('DB_PASS') ?: '1017929314';
$db   = getenv('DB_NAME') ?: 'if0_40460125_terminal_de_transportes';




try {
  $pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
     
    ]
    

  );

} catch (PDOException $e) {
  error_log('Database connection failed: ' . $e->getMessage());
  http_response_code(500);
  die('Error del servidor.');
}
?>