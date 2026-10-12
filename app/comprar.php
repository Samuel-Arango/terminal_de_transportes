<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tickets.php';
requireRole(['cliente']); // Solo clientes compran en línea.
require_once __DIR__ . '/includes/header.php';

$usuarioId = (int) user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<p class="msg info">Debes iniciar la compra desde el módulo de <a href="cotizar.php">cotización</a>.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

require_csrf();

$cotizacionId = (int) ($_POST['cotizacion_id'] ?? 0);

try {
    if ($cotizacionId <= 0) {
        throw new RuntimeException('No se recibió una cotización válida.');
    }

    $stmt = $pdo->prepare(
        'SELECT c.id, c.horario_id, c.personas, r.origen, r.destino
         FROM cotizaciones c
         JOIN rutas r ON r.id = c.ruta_id
         WHERE c.id = ? AND c.usuario_id = ?'
    );
    $stmt->execute([$cotizacionId, $usuarioId]);
    $cotizacion = $stmt->fetch();

    if (!$cotizacion) {
        throw new RuntimeException('La cotización no existe o no pertenece a tu cuenta.');
    }

    // Evita comprar dos veces la misma cotización.
    $stmt = $pdo->prepare('SELECT id FROM compras WHERE cotizacion_id = ? AND usuario_id = ? LIMIT 1');
    $stmt->execute([$cotizacionId, $usuarioId]);
    if ($compraExistente = $stmt->fetchColumn()) {
        header('Location: ver_tiquete.php?compra=' . (int) $compraExistente);
        exit;
    }

    if (empty($cotizacion['horario_id'])) {
        throw new RuntimeException('Esta cotización no tiene horario asignado. Vuelve a cotizar seleccionando un horario.');
    }

    $venta = vender_tiquetes($pdo, [
        'horario_id'      => (int) $cotizacion['horario_id'],
        'cantidad'        => (int) $cotizacion['personas'],
        'usuario_id'      => $usuarioId,
        'cotizacion_id'   => $cotizacionId,
        'metodo_pago'     => 'tarjeta',
        'canal'           => 'web',
        'pasajero_nombre' => user()['nombre'],
    ]);

    flash('ok', 'Compra registrada. Se generaron ' . count($venta['codigos']) . ' tiquete(s).');
    header('Location: ver_tiquete.php?compra=' . $venta['compra_id']);
    exit;
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: cotizar.php');
    exit;
}
