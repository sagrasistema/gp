<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;
use RuntimeException;

class AuditoriaPruebasRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Obtiene las pruebas de un proyecto agrupadas por categoría y etapa 
     * utilizando el esquema exacto de tablas con prefijo audit_.
     *
     * @param int $idProyecto
     * @return array
     */
    public function obtenerPruebasPorProyecto(int $idProyecto): array
    {
        $sql = "SELECT p.id, p.nombre, p.orden, p.estado, c.nombre as categoria_nombre, e.nombre as etapa_nombre
                FROM audit_pruebas p
                INNER JOIN audit_categorias c ON p.id_categoria = c.id
                INNER JOIN audit_etapas e ON p.id_etapa = e.id
                WHERE p.id_proyecto = :id_proyecto
                ORDER BY p.orden ASC";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':id_proyecto', $idProyecto, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al recuperar pruebas del proyecto: " . $e->getMessage());
            throw new RuntimeException("No se pudieron cargar las pruebas de planificación en este momento.");
        }
    }
}