<?php
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';
requireRole(['admin', 'empresa']);
require_once __DIR__.'/includes/header.php';


$terminal_id = 1;


if (isset($_GET['del'])) {
  $stmt = $pdo->prepare("DELETE FROM empresas WHERE id=?");
  $stmt->execute([(int)$_GET['del']]);
  echo '<p class="msg ok">Empresa eliminada correctamente.</p>';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre   = trim($_POST['nombre'] ?? '');
  $contacto = trim($_POST['contacto'] ?? '');

  if ($nombre !== '') {
    $stmt = $pdo->prepare("
      INSERT INTO empresas (terminal_id, nombre, contacto) 
      VALUES (?, ?, ?)
    ");
    $stmt->execute([$terminal_id, $nombre, $contacto]);
    echo '<p class="msg ok">Empresa registrada correctamente.</p>';
  } else {
    echo '<p class="msg error">El nombre de la empresa es obligatorio.</p>';
  }
}


$empresas = $pdo->query("
  SELECT e.id, e.nombre, e.contacto, t.nombre AS terminal
  FROM empresas e
  JOIN terminal t ON e.terminal_id = t.id
  ORDER BY e.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Empresas vinculadas</h2>

<table class="tbl">
  <thead>
    <tr>
      <th>#</th>
      <th>Nombre</th>
      <th>Contacto</th>
      <th>Terminal</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($empresas as $e): ?>
      <tr>
        <td><?= (int)$e['id'] ?></td>
        <td><?= htmlspecialchars($e['nombre']) ?></td>
        <td><?= htmlspecialchars($e['contacto']) ?></td>
        <td><?= htmlspecialchars($e['terminal']) ?></td>
        <td><a href="?del=<?= $e['id'] ?>" onclick="return confirm('¿Eliminar esta empresa?')">🗑️ Eliminar</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h3>Registrar nueva empresa</h3>
<form method="post" class="form grid">
  <label>Nombre <input name="nombre" required></label>
  <label>Contacto <input name="contacto"></label>
  <button type="submit">Guardar</button>
</form>

<?php require_once __DIR__.'/includes/footer.php'; ?>
