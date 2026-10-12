<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['admin', 'empresa']);
require_once __DIR__ . '/includes/header.php';

$u       = user();
$resultado = null; // ['tipo' => ok|error|warn, 'titulo' => ..., 'tiquete' => ?array]

/** Normaliza el texto leído (código suelto o contenido QR "TT|CODIGO"). */
function normalizar_codigo(string $raw): string
{
    $raw = trim($raw);
    if (str_contains($raw, '|')) {
        $raw = substr($raw, strrpos($raw, '|') + 1);
    }
    return strtoupper(trim($raw));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $codigo = normalizar_codigo((string) ($_POST['codigo'] ?? ''));

    if ($codigo === '') {
        $resultado = ['tipo' => 'error', 'titulo' => 'Ingresa o escanea un código de tiquete.', 'tiquete' => null];
    } else {
        $stmt = $pdo->prepare(
            'SELECT t.id, t.codigo, t.pasajero_nombre, t.pasajero_doc, t.asiento, t.estado,
                    t.validado_en, t.creado_en,
                    c.id AS compra_id, c.canal,
                    h.salida, h.llegada, r.origen, r.destino, e.nombre AS empresa,
                    uv.nombre AS validador
             FROM tiquetes t
             JOIN compras c   ON c.id = t.compra_id
             JOIN horarios h  ON h.id = c.horario_id
             JOIN rutas r     ON r.id = h.ruta_id
             JOIN empresas e  ON e.id = r.empresa_id
             LEFT JOIN usuarios uv ON uv.id = t.validado_por
             WHERE t.codigo = ?'
        );
        $stmt->execute([$codigo]);
        $t = $stmt->fetch();

        if (!$t) {
            $resultado = ['tipo' => 'error', 'titulo' => 'Tiquete no encontrado: ' . $codigo, 'tiquete' => null];
        } elseif ($t['estado'] === 'anulado') {
            $resultado = ['tipo' => 'error', 'titulo' => 'Tiquete ANULADO. No permitir el abordaje.', 'tiquete' => $t];
        } elseif ($t['estado'] === 'usado') {
            $resultado = ['tipo' => 'warn', 'titulo' => 'Este tiquete YA fue validado anteriormente.', 'tiquete' => $t];
        } else {
            $upd = $pdo->prepare(
                "UPDATE tiquetes SET estado = 'usado', validado_en = NOW(), validado_por = ?
                 WHERE id = ? AND estado = 'valido'"
            );
            $upd->execute([(int) $u['id'], (int) $t['id']]);

            if ($upd->rowCount() === 1) {
                $t['estado']       = 'usado';
                $t['validado_en']  = date('Y-m-d H:i:s');
                $t['validador']    = $u['nombre'];
                $resultado = ['tipo' => 'ok', 'titulo' => 'Acceso permitido. Tiquete validado correctamente.', 'tiquete' => $t];
            } else {
                $resultado = ['tipo' => 'warn', 'titulo' => 'El tiquete ya había sido validado.', 'tiquete' => $t];
            }
        }
    }
}

$recientes = $pdo->query(
    "SELECT t.codigo, t.asiento, t.validado_en, r.origen, r.destino, u.nombre AS validador
     FROM tiquetes t
     JOIN compras c  ON c.id = t.compra_id
     JOIN horarios h ON h.id = c.horario_id
     JOIN rutas r    ON r.id = h.ruta_id
     LEFT JOIN usuarios u ON u.id = t.validado_por
     WHERE t.estado = 'usado'
     ORDER BY t.validado_en DESC
     LIMIT 8"
)->fetchAll();
?>
<div class="page-head">
  <div>
    <h2>Validación en puerta</h2>
    <p class="muted">Escanea el código QR o ingresa el código del tiquete para permitir el abordaje.</p>
  </div>
</div>

<form method="post" class="form grid" id="validarForm">
  <?= csrf_field() ?>
  <label class="form-full">Código del tiquete
    <input name="codigo" id="codigoInput" placeholder="TT-XXXXXXXXXX" autocomplete="off" autofocus required>
  </label>
  <div class="form-full actions" style="margin-top:0">
    <button class="btn btn-primary" type="submit">Validar tiquete</button>
    <button class="btn btn-outline" type="button" id="scanBtn">Escanear con cámara</button>
  </div>
</form>

<div id="reader" style="display:none;max-width:340px;margin:16px 0;border-radius:14px;overflow:hidden"></div>

<?php if ($resultado): ?>
  <?php $t = $resultado['tiquete']; ?>
  <div class="msg <?= e($resultado['tipo']) ?>" style="font-size:1rem">
    <strong><?= e($resultado['titulo']) ?></strong>
    <?php if ($t): ?>
      <div class="ticket-meta" style="margin-top:10px">
        <span><strong>Código:</strong> <span class="ticket-code"><?= e($t['codigo']) ?></span></span>
        <span><strong>Pasajero:</strong> <?= e($t['pasajero_nombre'] ?: '—') ?> (<?= e($t['pasajero_doc'] ?: '—') ?>)</span>
        <span><strong>Ruta:</strong> <?= e($t['origen']) ?> → <?= e($t['destino']) ?> · <?= e($t['empresa']) ?></span>
        <span><strong>Salida:</strong> <?= e($t['salida']) ?> · <strong>Asiento:</strong> <?= (int) $t['asiento'] ?></span>
        <?php if (!empty($t['validado_en'])): ?>
          <span><strong>Validado:</strong> <?= e(date('d/m/Y H:i', strtotime($t['validado_en']))) ?><?= !empty($t['validador']) ? ' por ' . e($t['validador']) : '' ?></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<h3>Últimas validaciones</h3>
<?php if (!$recientes): ?>
  <p class="msg info">Todavía no se han validado tiquetes.</p>
<?php else: ?>
  <table class="tbl">
    <thead><tr><th>Código</th><th>Ruta</th><th>Asiento</th><th>Validado</th><th>Por</th></tr></thead>
    <tbody>
      <?php foreach ($recientes as $r): ?>
        <tr>
          <td><?= e($r['codigo']) ?></td>
          <td><?= e($r['origen']) ?> → <?= e($r['destino']) ?></td>
          <td><?= (int) $r['asiento'] ?></td>
          <td><?= e(date('d/m/Y H:i', strtotime($r['validado_en']))) ?></td>
          <td><?= e($r['validador'] ?: '—') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<script src="https://unpkg.com/html5-qrcode" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('scanBtn');
    const reader = document.getElementById('reader');
    const input = document.getElementById('codigoInput');
    const form = document.getElementById('validarForm');
    if (!btn || typeof Html5Qrcode === 'undefined') return;

    let scanner = null;
    btn.addEventListener('click', async function () {
      if (scanner) { await scanner.stop(); scanner = null; reader.style.display = 'none'; return; }
      reader.style.display = 'block';
      scanner = new Html5Qrcode('reader');
      try {
        await scanner.start(
          { facingMode: 'environment' },
          { fps: 10, qrbox: 220 },
          function (texto) {
            input.value = texto;
            scanner.stop().then(function () {
              scanner = null;
              reader.style.display = 'none';
              form.submit();
            });
          }
        );
      } catch (e) {
        reader.style.display = 'none';
        scanner = null;
        alert('No se pudo acceder a la cámara. Ingresa el código manualmente.');
      }
    });
  });
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
