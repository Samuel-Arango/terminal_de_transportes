<?php
/**
 * Lógica de venta de tiquetes, compartida por la compra en línea (comprar.php)
 * y la venta en taquilla (venta.php).
 */
declare(strict_types=1);

/** Genera un código de tiquete único, p. ej. TT-9F3A2C7B1D. */
function generar_codigo_tiquete(PDO $pdo): string
{
    do {
        $codigo = 'TT-' . strtoupper(bin2hex(random_bytes(5)));
        $stmt = $pdo->prepare('SELECT 1 FROM tiquetes WHERE codigo = ? LIMIT 1');
        $stmt->execute([$codigo]);
    } while ($stmt->fetchColumn());

    return $codigo;
}

/**
 * Registra una venta/compra y emite un tiquete por cada cupo, descontando
 * la disponibilidad dentro de una transacción.
 *
 * @param array $opts {
 *   horario_id:int, cantidad:int, usuario_id:?int, vendedor_id:?int,
 *   cotizacion_id:?int, metodo_pago:string, canal:string,
 *   pasajero_nombre:?string, pasajero_doc:?string
 * }
 * @return array{compra_id:int, codigos:list<string>, total:float, asientos:list<int>}
 * @throws RuntimeException cuando no hay cupos o el horario no existe.
 */
function vender_tiquetes(PDO $pdo, array $opts): array
{
    $horarioId = (int) ($opts['horario_id'] ?? 0);
    $cantidad  = max(1, (int) ($opts['cantidad'] ?? 1));

    if ($horarioId <= 0) {
        throw new RuntimeException('Horario no válido.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT d.cupos_disponibles, d.cupos_totales, r.costo_base
             FROM disponibilidades d
             JOIN horarios h ON h.id = d.horario_id
             JOIN rutas r    ON r.id = h.ruta_id
             WHERE d.horario_id = ?
             FOR UPDATE'
        );
        $stmt->execute([$horarioId]);
        $info = $stmt->fetch();

        if (!$info) {
            throw new RuntimeException('El horario seleccionado no tiene disponibilidad registrada.');
        }

        $cuposDisp  = (int) $info['cupos_disponibles'];
        $cuposTotal = (int) $info['cupos_totales'];
        $costoBase  = (float) $info['costo_base'];

        if ($cuposDisp < $cantidad) {
            throw new RuntimeException("No hay cupos suficientes. Disponibles: $cuposDisp.");
        }

        $total     = $costoBase * $cantidad;
        $usados    = $cuposTotal - $cuposDisp;
        $asientos  = range($usados + 1, $usados + $cantidad);

        // Descontar cupos.
        $pdo->prepare('UPDATE disponibilidades SET cupos_disponibles = cupos_disponibles - ? WHERE horario_id = ?')
            ->execute([$cantidad, $horarioId]);

        // Registrar la compra/venta.
        $pdo->prepare(
            'INSERT INTO compras
                (usuario_id, cotizacion_id, horario_id, vendedor_id, cantidad, total, metodo_pago, canal, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pagado")'
        )->execute([
            $opts['usuario_id'] ?? null,
            $opts['cotizacion_id'] ?? null,
            $horarioId,
            $opts['vendedor_id'] ?? null,
            $cantidad,
            $total,
            $opts['metodo_pago'] ?? 'efectivo',
            $opts['canal'] ?? 'web',
        ]);

        $compraId = (int) $pdo->lastInsertId();

        // Emitir un tiquete por cupo.
        $insert = $pdo->prepare(
            'INSERT INTO tiquetes (compra_id, codigo, pasajero_nombre, pasajero_doc, asiento)
             VALUES (?, ?, ?, ?, ?)'
        );
        $codigos = [];
        foreach ($asientos as $asiento) {
            $codigo = generar_codigo_tiquete($pdo);
            $insert->execute([
                $compraId,
                $codigo,
                $opts['pasajero_nombre'] ?? null,
                $opts['pasajero_doc'] ?? null,
                $asiento,
            ]);
            $codigos[] = $codigo;
        }

        $pdo->commit();

        return [
            'compra_id' => $compraId,
            'codigos'   => $codigos,
            'total'     => $total,
            'asientos'  => $asientos,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e instanceof RuntimeException ? $e : new RuntimeException('No se pudo completar la venta.', 0, $e);
    }
}
