<?php
require_once '../../conexion/conexion.php';

$conexion = new Conexion();
$db = $conexion->getConnection();

// Consulta de productos con baja existencia
$sql = "SELECT 
            pr.id_Producto,
            pr.nombre AS producto,
            c.nombre AS categoria,
            u.simbolo AS unidad,
            dc.existencia,
            dc.precio_unitario,
            p.nombre AS proveedor,
            co.fecha
        FROM detalle_compra dc
        INNER JOIN producto pr ON dc.id_Producto = pr.id_Producto
        INNER JOIN categoria c ON pr.id_Categoria = c.id_Categoria
        INNER JOIN unidad_medida u ON pr.id_Medida = u.id_Medida
        INNER JOIN compra co ON dc.id_Compra = co.id_Compra
        INNER JOIN proveedores p ON co.id_Proveedor = p.id_Proveedor
        WHERE dc.existencia <= 5
        ORDER BY dc.existencia ASC";

$stmt = $db->prepare($sql);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Productos con Baja Existencia</title>

    <!-- Bootstrap + DataTables + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        .page-header {
            text-align: center;
            margin: 30px 0 10px 0;
        }
        .page-header h2 {
            color: #dc3545;
            font-weight: 700;
        }
        .subtitle {
            color: #6c757d;
        }
        .export-bar {
            display: flex;
            justify-content: flex-end;
            margin: 20px 0;
        }
        #exportPDF {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        #exportPDF:hover {
            background-color: #bb2d3b;
            transform: translateY(-2px);
        }
        .table-wrapper {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        table.dataTable thead {
            background-color: #dc3545;
            color: white;
        }
        table.dataTable tbody tr:hover {
            background-color: #f1f3f5;
        }
        .text-danger {
            color: #dc3545 !important;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4">

    <!-- Encabezado -->
    <div class="page-header">
        <h2><i class="fas fa-triangle-exclamation"></i> Productos con baja existencia</h2>
        <div class="subtitle"><i class="fas fa-store text-danger"></i> Ferretería Michapa Cuscatlán</div>
    </div>

    <!-- Botón PDF -->
    <div class="export-bar">
        <button id="exportPDF"><i class="fas fa-file-pdf me-2"></i>Exportar PDF</button>
    </div>

    <!-- Tabla -->
    <div class="table-wrapper">
        <div class="table-responsive">
            <table id="tablaBajaExistencia" class="table table-striped table-hover align-middle text-center">
                <thead>
                    <tr>
                        <th>ID Producto</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Unidad</th>
                        <th>Existencia</th>
                        <th>Precio Unitario</th>
                        <th>Proveedor</th>
                        <th>Fecha Compra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $row): ?>
                        <tr>
                            <td><?= $row['id_Producto'] ?></td>
                            <td><?= htmlspecialchars($row['producto']) ?></td>
                            <td><?= htmlspecialchars($row['categoria']) ?></td>
                            <td><?= htmlspecialchars($row['unidad']) ?></td>
                            <td class="<?= $row['existencia'] <= 3 ? 'text-danger fw-bold' : '' ?>">
                                <?= $row['existencia'] ?>
                            </td>
                            <td>$<?= number_format($row['precio_unitario'], 2) ?></td>
                            <td><?= htmlspecialchars($row['proveedor']) ?></td>
                            <td><?= $row['fecha'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#tablaBajaExistencia').DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });

    // Exportar a PDF
    document.getElementById('exportPDF').addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'pt', 'a4');

        doc.setFontSize(18);
        doc.setTextColor(220, 53, 69);
        doc.text("Reporte de Productos con Baja Existencia", doc.internal.pageSize.getWidth() / 2, 40, { align: 'center' });
        doc.setFontSize(12);
        doc.setTextColor(73, 80, 87);
        doc.text("Ferretería Michapa, Cuscatlán", doc.internal.pageSize.getWidth() / 2, 60, { align: 'center' });

        const headers = [["ID", "Producto", "Categoría", "Unidad", "Existencia", "Precio Unitario", "Proveedor", "Fecha Compra"]];
        const body = [];
        <?php foreach ($resultados as $row): ?>
        body.push([
            "<?= $row['id_Producto'] ?>",
            "<?= htmlspecialchars($row['producto']) ?>",
            "<?= htmlspecialchars($row['categoria']) ?>",
            "<?= htmlspecialchars($row['unidad']) ?>",
            "<?= $row['existencia'] ?>",
            "$<?= number_format($row['precio_unitario'], 2) ?>",
            "<?= htmlspecialchars($row['proveedor']) ?>",
            "<?= $row['fecha'] ?>"
        ]);
        <?php endforeach; ?>

        doc.autoTable({
            startY: 80,
            head: headers,
            body: body,
            theme: 'grid',
            headStyles: { fillColor: [220, 53, 69], textColor: 255 },
            styles: { fontSize: 9, halign: 'center' },
            margin: { top: 80, left: 20, right: 20 }
        });

        doc.save("Reporte_Baja_Existencia.pdf");
    });
});
</script>
</body>
</html>
