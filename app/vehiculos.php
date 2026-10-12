<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['admin', 'empresa']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM vehiculos WHERE id = ?')->execute([$id]);
            flash('ok', 'Vehículo eliminado.');
        }
        header('Location: vehiculos.php');
        exit;
    }

    $empresaId = (int) ($_POST['empresa_id'] ?? 0);
    $tipo      = trim($_POST['tipo'] ?? '');
    $capacidad = (int) ($_POST['capacidad'] ?? 0);
    $placa     = trim($_POST['placa'] ?? '');

    if ($empresaId && $tipo !== '' && $capacidad > 0) {
        $pdo->prepare('INSERT INTO vehiculos (empresa_id, tipo, capacidad, placa) VALUES (?, ?, ?, ?)')
            ->execute([$empresaId, $tipo, $capacidad, $placa !== '' ? $placa : null]);
        flash('ok', 'Vehículo creado.');
    } else {
        flash('error', 'Datos incompletos.');
    }
    header('Location: vehiculos.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$empresas = $pdo->query('SELECT id, nombre FROM empresas ORDER BY nombre')->fetchAll();
$vehiculos = $pdo->query(
    'SELECT v.id, v.tipo, v.capacidad, v.placa, e.nombre AS empresa
     FROM vehiculos v JOIN empresas e ON e.id = v.empresa_id
     ORDER BY v.id DESC'
)->fetchAll();
?>
<div class="page-head">
  <div>
    <h2>Vehículos</h2>
    <p class="muted"><?= count($vehiculos) ?> vehículos registrados.</p>
  </div>
</div>

<table class="tbl">
  <thead><tr><th>#</th><th>Empresa</th><th>Tipo</th><th>Capacidad</th><th>Placa</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($vehiculos as $v): ?>
      <tr>
        <td><?= (int) $v['id'] ?></td>
        <td><?= e($v['empresa']) ?></td>
        <td><?= e($v['tipo']) ?></td>
        <td><?= (int) $v['capacidad'] ?></td>
        <td><?= e($v['placa']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('¿Eliminar vehículo?');" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h3>Nuevo vehículo</h3>
<form method="post" class="form grid">
  <?= csrf_field() ?>
  <input type="hidden" name="accion" value="crear">
  <label>Empresa
    <select name="empresa_id" required>
      <option value="">Seleccione</option>
      <?php foreach ($empresas as $emp): ?>
        <option value="<?= (int) $emp['id'] ?>"><?= e($emp['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Tipo <input name="tipo" placeholder="camión, buseta…" required></label>
  <label>Capacidad <input type="number" name="capacidad" min="1" required></label>
  <label>Placa <input name="placa" placeholder="Opcional"></label>
  <div class="form-full"><button class="btn btn-primary">Guardar vehículo</button></div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
