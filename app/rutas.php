<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';


if (isLogged() && in_array(user()['rol'], ['admin', 'empresa']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $origen = trim($_POST['origen']);
    $destino = trim($_POST['destino']);
    $distancia = (float)$_POST['distancia'];
    $costo = (float)$_POST['costo'];
    $empresa_id = (int)$_POST['empresa_id'];

    if ($origen && $destino && $empresa_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO rutas (empresa_id, origen, destino, distancia_km, costo_base) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$empresa_id, $origen, $destino, $distancia, $costo]);
        echo '<p class="msg ok">✅ Ruta creada exitosamente.</p>';
    } else {
        echo '<p class="msg error">⚠️ Por favor completa todos los campos correctamente.</p>';
    }
}


$rutas = $pdo->query("
    SELECT r.id, e.nombre AS empresa, r.origen, r.destino, r.distancia_km, r.costo_base
    FROM rutas r
    INNER JOIN empresas e ON e.id = r.empresa_id
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);


$empresas = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Rutas registradas</h2>
<table class="tbl">
  <thead>
    <tr>
      <th>#</th>
      <th>Empresa</th>
      <th>Origen</th>
      <th>Destino</th>
      <th>Km</th>
      <th>Costo base</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rutas as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['empresa']) ?></td>
      <td><?= htmlspecialchars($r['origen']) ?></td>
      <td><?= htmlspecialchars($r['destino']) ?></td>
      <td><?= $r['distancia_km'] ?></td>
      <td>$<?= number_format($r['costo_base'], 0) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php if (isLogged() && in_array(user()['rol'], ['admin', 'empresa'])): ?>
<h3>Registrar nueva ruta</h3>
<form method="post" class="form grid">
  <label>Empresa:
    <select name="empresa_id" required>
      <option value="">Seleccione...</option>
      <?php foreach ($empresas as $e): ?>
        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Origen <input name="origen" required></label>
  <label>Destino <input name="destino" required></label>
  <label>Distancia (km) <input type="number" step="0.1" name="distancia" required></label>
  <label>Costo base <input type="number" step="0.01" name="costo" required></label>

  <button>Guardar</button>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
