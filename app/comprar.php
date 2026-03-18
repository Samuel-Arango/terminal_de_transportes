<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['cliente']); // Solo clientes pueden comprar
require_once __DIR__ . '/includes/header.php';

$usuario_id = $_SESSION['user']['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cotizacion_id = (int)($_POST['cotizacion_id'] ?? 0);

    if ($cotizacion_id > 0) {
       
        $stmt = $pdo->prepare("
            SELECT c.id, r.origen, r.destino, c.personas, c.total
            FROM cotizaciones c
            JOIN rutas r ON c.ruta_id = r.id
            WHERE c.id = ? AND c.usuario_id = ?
        ");
        $stmt->execute([$cotizacion_id, $usuario_id]);
        $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cotizacion) {
            // Registrar la compra
            $stmt = $pdo->prepare("
                INSERT INTO compras (usuario_id, cotizacion_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$usuario_id, $cotizacion_id]);

            echo "<div class='msg ok'>
                    ✅ Compra registrada correctamente.<br>
                    🛣 Ruta: " . htmlspecialchars($cotizacion['origen']) . " → " . htmlspecialchars($cotizacion['destino']) . "<br>
                    👥 Personas: " . $cotizacion['personas'] . "<br>
                    💰 Total: $" . number_format($cotizacion['total'], 0, ',', '.') . "<br><br>
                    <a href='dashboard.php'>Volver al panel</a>
                  </div>";
        } else {
            echo "<p class='msg error'>❌ La cotización no existe o no pertenece a este usuario.</p>";
        }
    } else {
        echo "<p class='msg error'>⚠️ No se recibió una cotización válida.</p>";
    }
} else {
    echo "<p class='msg'>Debe acceder desde el módulo de cotización.</p>";
}

require_once __DIR__ . '/includes/footer.php';
?>



