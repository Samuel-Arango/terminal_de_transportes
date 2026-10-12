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
            $pdo->prepare('DELETE FROM empresas WHERE id = ?')->execute([$id]);
            flash('ok', 'Empresa eliminada correctamente.');
        }
        header('Location: empresas.php');
        exit;
    }

    $nombre   = trim($_POST['nombre'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');

    if ($nombre !== '') {
        $pdo->prepare('INSERT INTO empresas (terminal_id, nombre, contacto) VALUES (?, ?, ?)')
            ->execute([1, $nombre, $contacto]);
        flash('ok', 'Empresa registrada correctamente.');
    } else {
        flash('error', 'El nombre de la empresa es obligatorio.');
    }
    header('Location: empresas.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

$empresas = $pdo->query(
    'SELECT e.id, e.nombre, e.contacto, t.nombre AS terminal
     FROM empresas e
     JOIN terminal t ON e.terminal_id = t.id
     ORDER BY e.id DESC'
)->fetchAll();
?>
<div class="page-head">
  <div>
    <h2>Empresas vinculadas</h2>
    <p class="muted"><?= count($empresas) ?> empresas operando en el terminal.</p>
  </div>
</div>

<table class="tbl">
  <thead>
    <tr><th>#</th><th>Nombre</th><th>Contacto</th><th>Terminal</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($empresas as $emp): ?>
      <tr>
        <td><?= (int) $emp['id'] ?></td>
        <td><?= e($emp['nombre']) ?></td>
        <td><?= e($emp['contacto']) ?></td>
        <td><?= e($emp['terminal']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('¿Eliminar esta empresa?');" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id" value="<?= (int) $emp['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h3>Registrar nueva empresa</h3>
<form method="post" class="form grid">
  <?= csrf_field() ?>
  <input type="hidden" name="accion" value="crear">
  <label>Nombre <input name="nombre" required></label>
  <label>Contacto <input name="contacto" placeholder="correo o teléfono"></label>
  <div class="form-full"><button class="btn btn-primary">Guardar empresa</button></div>
</form>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
