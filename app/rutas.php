<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$puedeEditar = is_role('admin', 'empresa');

if ($puedeEditar && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM rutas WHERE id = ?')->execute([$id]);
            flash('ok', 'Ruta eliminada.');
        }
        header('Location: rutas.php');
        exit;
    }

    $origen     = trim($_POST['origen'] ?? '');
    $destino    = trim($_POST['destino'] ?? '');
    $distancia  = (float) ($_POST['distancia'] ?? 0);
    $costo      = (float) ($_POST['costo'] ?? 0);
    $empresaId  = (int) ($_POST['empresa_id'] ?? 0);

    if ($origen !== '' && $destino !== '' && $empresaId > 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO rutas (empresa_id, origen, destino, distancia_km, costo_base) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$empresaId, $origen, $destino, $distancia, $costo]);
        flash('ok', 'Ruta creada exitosamente.');
    } else {
        flash('error', 'Completa todos los campos correctamente.');
    }
    header('Location: rutas.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$rutas = $pdo->query(
    'SELECT r.id, e.nombre AS empresa, r.origen, r.destino, r.distancia_km, r.costo_base
     FROM rutas r
     INNER JOIN empresas e ON e.id = r.empresa_id
     ORDER BY r.id DESC'
)->fetchAll();

$empresas = $pdo->query('SELECT id, nombre FROM empresas ORDER BY nombre')->fetchAll();
?>
<div class="page-head">
  <div>
    <h2>Rutas registradas</h2>
    <p class="muted"><?= count($rutas) ?> rutas disponibles en el terminal.</p>
  </div>
</div>

<table class="tbl">
  <thead>
    <tr><th>#</th><th>Empresa</th><th>Origen</th><th>Destino</th><th>Km</th><th>Costo base</th>
      <?php if ($puedeEditar): ?><th></th><?php endif; ?></tr>
  </thead>
  <tbody>
  <?php foreach ($rutas as $r): ?>
    <tr>
      <td><?= (int) $r['id'] ?></td>
      <td><?= e($r['empresa']) ?></td>
      <td><?= e($r['origen']) ?></td>
      <td><?= e($r['destino']) ?></td>
      <td><?= e($r['distancia_km']) ?></td>
      <td><?= money($r['costo_base']) ?></td>
      <?php if ($puedeEditar): ?>
        <td>
          <form method="post" onsubmit="return confirm('¿Eliminar esta ruta?');" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
          </form>
        </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php if ($puedeEditar): ?>
<h3>Registrar nueva ruta</h3>
<form method="post" class="form grid">
  <?= csrf_field() ?>
  <input type="hidden" name="accion" value="crear">
  <label>Empresa
    <select name="empresa_id" required>
      <option value="">Seleccione...</option>
      <?php foreach ($empresas as $emp): ?>
        <option value="<?= (int) $emp['id'] ?>"><?= e($emp['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Origen <input name="origen" required></label>
  <label>Destino <input name="destino" required></label>
  <label>Distancia (km) <input type="number" step="0.1" name="distancia" required></label>
  <label>Costo base <input type="number" step="0.01" name="costo" required></label>
  <div class="form-full"><button class="btn btn-primary">Guardar ruta</button></div>
</form>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
