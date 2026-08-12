<?php

declare(strict_types=1);

include '../config/config.php'; 

use PDO;
use PDOException;
use RuntimeException;
use InvalidArgumentException;

class MetodologiaController
{
    public function __construct(private readonly PDO $pdo) {}

    // ==========================================
    // 1. SERVICIOS
    // ==========================================
    public function saveService(string $name, ?int $id = null): int
    {
        $name = trim(strip_tags($name));
        if (empty($name)) {
            throw new InvalidArgumentException("El nombre del servicio es obligatorio.");
        }

        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare("UPDATE ac_services SET serviceName = :name WHERE serviceId = :id");
            $stmt->execute([':name' => $name, ':id' => $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare("INSERT INTO ac_services (serviceName) VALUES (:name)");
        $stmt->execute([':name' => $name]);
        return (int) $this->pdo->lastInsertId();
    }

    // ==========================================
    // 2. ETAPAS Y CATEGORÍAS
    // ==========================================
    public function saveEtapa(string $nombre, string $descripcion, int $orden, int $serviceId, int $version, ?int $id = null): int
    {
        $nombre = trim(strip_tags($nombre));
        $descripcion = trim(strip_tags($descripcion));

        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare("UPDATE audit_etapas SET nombre = :n, descripcion = :d, orden = :o WHERE id = :id");
            $stmt->execute([':n' => $nombre, ':d' => $descripcion, ':o' => $orden, ':id' => $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare("INSERT INTO audit_etapas (nombre, descripcion, orden, serviceId, version) VALUES (:n, :d, :o, :s, :v)");
        $stmt->execute([':n' => $nombre, ':d' => $descripcion, ':o' => $orden, ':s' => $serviceId, ':v' => $version]);
        return (int) $this->pdo->lastInsertId();
    }

    public function saveCategoria(string $nombre, string $descripcion, int $etapaId, int $serviceId, int $version, ?int $id = null): int
    {
        $nombre = trim(strip_tags($nombre));
        $descripcion = trim(strip_tags($descripcion));

        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare("UPDATE audit_categorias SET nombre = :n, descripcion = :d, etapa_id = :e WHERE id = :id");
            $stmt->execute([':n' => $nombre, ':d' => $descripcion, ':e' => $etapaId, ':id' => $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare("INSERT INTO audit_categorias (nombre, descripcion, etapa_id, serviceId, version) VALUES (:n, :d, :e, :s, :v)");
        $stmt->execute([':n' => $nombre, ':d' => $descripcion, ':e' => $etapaId, ':s' => $serviceId, ':v' => $version]);
        return (int) $this->pdo->lastInsertId();
    }

    // ==========================================
    // 3. PRUEBAS
    // ==========================================
    public function savePrueba(string $nombre, string $descripcion, int $categoriaId, int $serviceId, int $version, ?int $id = null): int
    {
        $nombre = trim(strip_tags($nombre));
        $descripcion = trim(strip_tags($descripcion));

        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare("UPDATE audit_pruebas SET nombre = :n, descripcion = :d, categoria_id = :c WHERE id = :id");
            $stmt->execute([':n' => $nombre, ':d' => $descripcion, ':c' => $categoriaId, ':id' => $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare("INSERT INTO audit_pruebas (nombre, descripcion, categoria_id, serviceId, version) VALUES (:n, :d, :c, :s, :v)");
        $stmt->execute([':n' => $nombre, ':d' => $descripcion, ':c' => $categoriaId, ':s' => $serviceId, ':v' => $version]);
        return (int) $this->pdo->lastInsertId();
    }

    // ==========================================
    // 4. ACTIVIDADES
    // ==========================================
    public function saveActividad(string $descripcion, int $pruebaId, int $serviceId, int $version, ?int $id = null): int
    {
        // Sanitizar manteniendo HTML seguro si se usa editor enriquecido
        $descripcion = trim($descripcion);

        if ($id && $id > 0) {
            $stmt = $this->pdo->prepare("UPDATE audit_actividades SET descripcion = :d, prueba_id = :p WHERE id = :id");
            $stmt->execute([':d' => $descripcion, ':p' => $pruebaId, ':id' => $id]);
            return $id;
        }

        $stmt = $this->pdo->prepare("INSERT INTO audit_actividades (descripcion, prueba_id, serviceId, version) VALUES (:d, :p, :s, :v)");
        $stmt->execute([':d' => $descripcion, ':p' => $pruebaId, ':s' => $serviceId, ':v' => $version]);
        return (int) $this->pdo->lastInsertId();
    }

    // ==========================================
    // CREAR NUEVA VERSIÓN (Mapeo Relacional Profundo)
    // ==========================================
    public function createNextVersion(int $serviceId): int
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Obtener última versión
            $stmt = $this->pdo->prepare("SELECT MAX(version) as current_v FROM audit_etapas WHERE serviceId = :s");
            $stmt->execute([':s' => $serviceId]);
            $currentV = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['current_v'] ?? 1);
            $newV = $currentV + 1;

            // 2. Clonar Etapas y guardar mapeo de IDs [Old_ID => New_ID]
            $etapaMap = [];
            $stmt = $this->pdo->prepare("SELECT * FROM audit_etapas WHERE serviceId = :s AND version = :v");
            $stmt->execute([':s' => $serviceId, ':v' => $currentV]);
            $etapas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($etapas as $e) {
                $newId = $this->saveEtapa($e['nombre'], $e['descripcion'] ?? '', (int)$e['orden'], $serviceId, $newV);
                $etapaMap[$e['id']] = $newId;
            }

            // 3. Clonar Categorías con nuevos etapa_id
            $catMap = [];
            $stmt = $this->pdo->prepare("SELECT * FROM audit_categorias WHERE serviceId = :s AND version = :v");
            $stmt->execute([':s' => $serviceId, ':v' => $currentV]);
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($categorias as $c) {
                $newEtapaId = $etapaMap[$c['etapa_id']] ?? $c['etapa_id'];
                $newId = $this->saveCategoria($c['nombre'], $c['descripcion'] ?? '', $newEtapaId, $serviceId, $newV);
                $catMap[$c['id']] = $newId;
            }

            // 4. Clonar Pruebas con nuevos categoria_id
            $pruebaMap = [];
            $stmt = $this->pdo->prepare("SELECT * FROM audit_pruebas WHERE serviceId = :s AND version = :v");
            $stmt->execute([':s' => $serviceId, ':v' => $currentV]);
            $pruebas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pruebas as $p) {
                $newCatId = $catMap[$p['categoria_id']] ?? $p['categoria_id'];
                $newId = $this->savePrueba($p['nombre'], $p['descripcion'] ?? '', $newCatId, $serviceId, $newV);
                $pruebaMap[$p['id']] = $newId;
            }

            // 5. Clonar Actividades con nuevos prueba_id
            $stmt = $this->pdo->prepare("SELECT * FROM audit_actividades WHERE serviceId = :s AND version = :v");
            $stmt->execute([':s' => $serviceId, ':v' => $currentV]);
            $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($actividades as $a) {
                $newPruebaId = $pruebaMap[$a['prueba_id']] ?? $a['prueba_id'];
                $this->saveActividad($a['descripcion'], $newPruebaId, $serviceId, $newV);
            }

            $this->pdo->commit();
            return $newV;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error en versionado: " . $e->getMessage());
            throw new RuntimeException("No se pudo generar la nueva versión.");
        }
    }
}