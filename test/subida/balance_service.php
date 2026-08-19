<?php

declare(strict_types=1);

/**
 * Obtiene el sumario de auditoría para una actividad específica.
 *
 * @param PDO $pdo Instancia de base de datos.
 * @param int $actividadId ID de la actividad.
 * @return array Lista de registros encontrados.
 */
function obtenerBalanceAuditado(PDO $pdo, int $actividadId): array
{
    try {
        $sql = "SELECT 
                    id, actividad_id, link_eeff_1, rubro_eeff_1, link_eeff_2, 
                    rubro_eeff_notas, link_centro_costo, tipo_partida, codigo, 
                    nombre, codigo_nombre, balance_cierre, debe, haber, 
                    balance_auditado, balance_final_ajustado, diferencia
                FROM actividad_balance_auditado 
                WHERE actividad_id = :actividad_id 
                ORDER BY id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':actividad_id' => $actividadId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al consultar balance (Actividad {$actividadId}): " . $e->getMessage());
        return [];
    }
}