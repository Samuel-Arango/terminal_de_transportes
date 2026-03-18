<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';


$rutas = $pdo->query("SELECT id, origen, destino, costo_base FROM rutas ORDER BY origen")->fetchAll();

$total = null;
$cotizacion_id = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruta_id = (int)($_POST['ruta_id'] ?? 0);
    $personas = max(1, (int)($_POST['personas'] ?? 1));

    
    $stmt = $pdo->prepare("SELECT costo_base, origen, destino FROM rutas WHERE id = ?");
    $stmt->execute([$ruta_id]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ruta) {
        $total = $ruta['costo_base'] * $personas;

     
        if (isLogged()) {
            $stmt = $pdo->prepare("
                INSERT INTO cotizaciones (usuario_id, ruta_id, personas, total)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([user()['id'], $ruta_id, $personas, $total]);
            $cotizacion_id = $pdo->lastInsertId();
        }

    } else {
        echo "<p class='msg error'>❌ La ruta seleccionada no existe.</p>";
    }
}
?>

<h2>Cotizar Transporte</h2>

<form method="post" class="form grid">
  <label>Ruta
    <select name="ruta_id" required>
      <option value="">Seleccione una ruta</option>
      <?php foreach ($rutas as $r): ?>
        <option value="<?= $r['id'] ?>">
          <?= htmlspecialchars($r['origen']) ?> → <?= htmlspecialchars($r['destino']) ?>
          (Base: $<?= number_format($r['costo_base'], 0, ',', '.') ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Personas / Cupos
    <input type="number" name="personas" min="1" value="1" required>
  </label>

  <button type="submit">Calcular</button>
</form>

<?php if (!is_null($total)): ?>
  <div class="msg ok">
    💰 <strong>Total estimado:</strong> $<?= number_format($total, 0, ',', '.') ?><br>
    🛣 <strong>Ruta:</strong> <?= htmlspecialchars($ruta['origen']) ?> → <?= htmlspecialchars($ruta['destino']) ?><br>
    👥 <strong>Personas:</strong> <?= $personas ?><br>
  </div>

  <?php if (isLogged()): ?>
    <form action="comprar.php" method="post">
      <input type="hidden" name="cotizacion_id" value="<?= $cotizacion_id ?>">
      <button type="submit">Comprar</button>
    </form>
  <?php else: ?>
    <p class="msg">⚠️ Para comprar, <a href="login.php">inicia sesión</a>.</p>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

