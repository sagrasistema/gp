<?php 
$pageTitle = "Control de Clientes Alberto";
include 'header.php'; 
?>

<div class="container">
    <header>
        <h1><i class="ri-team-line"></i> Control de Clientes Alberto</h1>
        <div class="header-actions">
            <button id="btn-export" class="btn btn-success"><i class="ri-file-excel-line"></i> Exportar (CSV)</button>
            <a href="nuevo.php" class="btn btn-primary"><i class="ri-user-add-line"></i> Nuevo Cliente</a>
        </div>
    </header>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Cliente / Empresa</th>
                    <th style="width: 20%;">Correo Electrónico</th>
                    <th style="width: 15%;">Teléfono</th>
                    <th style="width: 15%;">Sector</th>
                    <th style="width: 13%;">Estado</th>
                    <th style="width: 12%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody id="table-body"></tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>