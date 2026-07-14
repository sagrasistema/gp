<?php 
// v/ac/index.php
$pageTitle = "Aceptación y Continuidad";
include '../main/h.php'; 
include '../main/config.php'; 
?>

<div class="system-layout" style="display: flex; min-height: 100vh; background-color: #f8fafc;">
    
    <aside class="main-sidebar" style="width: 240px; background: #34495e; color: #fff; flex-shrink: 0;">
        <div class="sidebar-brand" style="padding: 1.5rem; background: #2c3e50; text-align: center;">
            <img src="../main/logo.png" alt="SAGRA" style="max-height: 45px; cursor: pointer;" onclick="window.location.href='../index.php'">
        </div>
        <nav class="sidebar-menu" style="padding: 1rem 0;">
            <a href="../index.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; font-size: 0.95rem;">
                <i class="ri-home-4-line"></i> Inicio
            </a>
            <a href="index.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #fff; background: #1a252f; text-decoration: none; font-size: 0.95rem; font-weight: 600; border-left: 4px solid #3498db;">
                <i class="ri-shield-check-line"></i> Aceptación y Cont.
            </a>
            <a href="#" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; font-size: 0.95rem; opacity: 0.6;">
                <i class="ri-customer-service-2-line"></i> Soporte IT
            </a>
        </nav>
    </aside>

    <div class="content-wrapper" style="flex-grow: 1; display: flex; flex-direction: column; min-width: 0;">
        
        <header class="main-navbar" style="height: 60px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 1.5rem;">
            <div class="navbar-left" style="display: flex; align-items: center; gap: 1rem;">
                <button id="toggle-sidebar-btn" class="btn-toggle" style="background: none; border: none; font-size: 1.3rem; color: #475569; cursor: pointer;"><i class="ri-menu-line"></i></button>
                <span style="font-weight: 600; color: #334155; font-size: 1rem;">Módulo de Auditoría</span>
            </div>
            <div class="navbar-right" style="display: flex; align-items: center; gap: 1rem; color: #475569; font-size: 0.9rem;">
                <i class="ri-user-line" style="background: #f1f5f9; padding: 0.4rem; border-radius: 50%;"></i>
                <span class="user-name-text">Juan Manuel Godoy</span>
            </div>
        </header>

        <main style="padding: 1.5rem; flex-grow: 1;">
            <div class="container" style="max-width: 1200px; margin: 0 auto; width: 100%;">
                <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ri-shield-check-line" style="color: #0284c7;"></i> Aceptación y Continuidad
                    </h1>
                    <div class="header-actions" style="display: flex; gap: 0.75rem;">
                        <a href="../index.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem; font-weight: 500; text-decoration: none; border: 1px solid #cbd5e1; color: #475569; background: #fff;"><i class="ri-arrow-left-line"></i> Menú</a>
                        <a href="nuevo.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem; font-weight: 500; text-decoration: none; color: #fff; background: #0284c7; border: none;"><i class="ri-add-line"></i> Nueva Evaluación</a>
                    </div>
                </header>

                <div class="table-container" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow-x: auto; width: 100%; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <table class="custom-table" style="width: 100%; border-collapse: collapse; text-align: left; min-width: 600px;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="padding: 1rem; width: 12%; font-weight: 600; color: #64748b; font-size: 0.85rem;">ID AC</th>
                                <th style="padding: 1rem; width: 33%; font-weight: 600; color: #64748b; font-size: 0.85rem;">Cliente / Empresa</th>
                                <th style="padding: 1rem; width: 25%; font-weight: 600; color: #64748b; font-size: 0.85rem;">Tipo de Evaluación</th>
                                <th style="padding: 1rem; width: 15%; font-weight: 600; color: #64748b; font-size: 0.85rem;">Fecha Creación</th>
                                <th style="padding: 1rem; width: 15%; text-align: center; font-weight: 600; color: #64748b; font-size: 0.85rem;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $query = "SELECT a.acId, c.name AS clientName, t.typeName, a.created_at 
                                          FROM ac a
                                          INNER JOIN clientes c ON a.clientId = c.id
                                          INNER JOIN ac_types t ON a.typeId = t.typeId
                                          ORDER BY a.acId DESC";
                                $stmt = $pdo->query($query);
                                $evaluaciones = $stmt->fetchAll(PDO::FETCH_OBJ);
                                
                                if (!empty($evaluaciones)) {
                                    foreach ($evaluaciones as $ac) {
                                        $clientName = htmlspecialchars($ac->clientName, ENT_QUOTES, 'UTF-8');
                                        $typeName   = htmlspecialchars($ac->typeName, ENT_QUOTES, 'UTF-8');
                                        $fecha      = date('d/m/Y', strtotime($ac->created_at));

                                        echo "<tr style='border-bottom: 1px solid #e2e8f0; vertical-align: middle;'>";
                                        echo "<td style='padding: 1rem; font-weight: 600; color: #64748b;'>#{$ac->acId}</td>";
                                        echo "<td style='padding: 1rem; color: #0f172a;'><strong>{$clientName}</strong></td>";
                                        echo "<td style='padding: 1rem; color: #334155;'>{$typeName}</td>";
                                        echo "<td style='padding: 1rem; color: #334155;'>{$fecha}</td>";
                                        echo "<td style='padding: 1rem; text-align: center;'>
                                                <a href='responder.php?acId={$ac->acId}' class='btn' style='display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 4px; border: 1px solid #cbd5e1; color: #475569; background: #fff; text-decoration: none; font-weight: 500;'>
                                                    <i class='ri-file-list-3-line'></i> Responder
                                                </a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='text-align: center; color: #64748b; padding: 3rem;'>No se han encontrado evaluaciones.</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='5' style='text-align: center; color: red; padding: 2rem;'>Error de datos.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
    @media (max-width: 768px) {
        .main-sidebar { display: none !important; } /* Esconde sidebar en móviles */
        .user-name-text { display: none; } /* Oculta nombre del usuario para ahorrar espacio */
        header style { flex-direction: column; align-items: flex-start !important; }
        .header-actions { width: 100%; justify-content: space-between; }
    }
</style>

<script>
    // Colapsado básico opcional para móviles
    document.getElementById('toggle-sidebar-btn').addEventListener('click', function() {
        const sidebar = document.querySelector('.main-sidebar');
        if (sidebar.style.display === 'none' || sidebar.style.display === '') {
            sidebar.style.display = 'block';
            sidebar.style.position = 'absolute';
            sidebar.style.zIndex = '999';
            sidebar.style.height = '100vh';
        } else {
            sidebar.style.display = 'none';
        }
    });
</script>

<?php include '../main/footer.php'; ?>