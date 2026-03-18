<?php

require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/auth.php';
requireRole(['admin','vendedor']);
require_once __DIR__.'/includes/header.php';


if (isset($_GET['del'])) {
  $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id=?");
  $stmt->execute([(int)$_GET['del']]);
  echo '<p class="msg ok">Vehículo eliminado.</p>';
}


$empresas = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre")->fetchAll();

// Alta
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $empresa_id = (int)($_POST['empresa_id'] ?? 0);
  $tipo       = trim($_POST['tipo'] ?? '');
  $capacidad  = (int)($_POST['capacidad'] ?? 0);
  $placa      = trim($_POST['placa'] ?? '');

  if ($empresa_id && $tipo !== '' && $capacidad > 0) {
    $stmt = $pdo->prepare("INSERT INTO vehiculos (empresa_id,tipo,capacidad,placa) VALUES (?,?,?,?)");
    $stmt->execute([$empresa_id,$tipo,$capacidad,$placa]);
    echo '<p class="msg ok">Vehículo creado.</p>';
  } else {
    echo '<p class="msg error">Datos incompletos.</p>';
  }
}


$vehiculos = $pdo->query("
  SELECT v.id, v.tipo, v.capacidad, v.placa, e.nombre AS empresa
  FROM vehiculos v JOIN empresas e ON e.id=v.empresa_id
  ORDER BY v.id DESC")->fetchAll();
?>
<h2>Vehículos</h2>

<table class="tbl">
  <thead><tr><th>#</th><th>Empresa</th><th>Tipo</th><th>Capacidad</th><th>Placa</th><th></th></tr></thead>
  <tbody>
    <?php foreach($vehiculos as $v): ?>
      <tr>
        <td><?=$v['id']?></td>
        <td><?=htmlspecialchars($v['empresa'])?></td>
        <td><?=htmlspecialchars($v['tipo'])?></td>
        <td><?=$v['capacidad']?></td>
        <td><?=htmlspecialchars($v['placa'])?></td>
        <td><a href="?del=<?=$v['id']?>" onclick="return confirm('¿Eliminar vehículo?')">Eliminar</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h3>Nuevo vehículo</h3>
<form method="post" class="form grid">
  <label>Empresa
    <select name="empresa_id" required>
      <option value="">Seleccione</option>
      <?php foreach($empresas as $e): ?>
        <option value="<?=$e['id']?>"><?=htmlspecialchars($e['nombre'])?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Tipo <input name="tipo" placeholder="camión, buseta…" required></label>
  <label>Capacidad <input type="number" name="capacidad" min="1" required></label>
  <label>Placa <input name="placa" placeholder="Opcional"></label>
  <button>Guardar</button>
</form>

<?php require_once __DIR__.'/includes/footer.php'; ?>