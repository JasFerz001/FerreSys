<?php
require_once '../../conexion/conexion.php';

$conexion = new Conexion();
$db = $conexion->getConnection();

// Query con subtotal
$sql = "SELECT 
            p.id_Proveedor, p.nombre AS proveedor, 
            c.id_Compra, c.fecha, 
            e.nombre AS empleado, e.apellido AS apellido_empleado, 
            pr.nombre AS producto, dc.cantidad, dc.precio_unitario, 
            (dc.cantidad * dc.precio_unitario) AS subtotal
        FROM compra c
        INNER JOIN proveedores p ON c.id_Proveedor = p.id_Proveedor
        INNER JOIN empleados e ON c.id_Empleado = e.id_Empleado
        INNER JOIN detalle_compra dc ON c.id_Compra = dc.id_Compra
        INNER JOIN producto pr ON dc.id_Producto = pr.id_Producto
        ORDER BY c.id_Compra ASC, pr.nombre";

$stmt = $db->prepare($sql);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Compras por Proveedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #ffffff;
            font-family: 'Segoe UI', sans-serif;
        }

        h2 {
            font-weight: 700;
            margin-bottom: 5px;
            text-align: center;
            color: #212529;
        }

        .subtitle {
            font-size: 1rem;
            color: #495057;
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            width: 100%;
            max-width: 100%;
            border: 12px;
        }

        table.dataTable thead {
            background-color: #4dabf7;
            color: white;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #f8f9fa;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: #e9ecef;
        }

        table.dataTable tbody tr:hover {
            background-color: #cfe2ff;
        }

        /* Botón PDF verde premium alineado a la derecha */
        .table-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
        }

        #exportPDF {
            background-color: #0d6efd;
            /* azul tipo bootstrap */
            color: white;
            border: none;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }

        #exportPDF:hover {
            background-color: #0b5ed7;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">

        <!-- Título principal centrado -->
        <h2><i class="fas fa-file-invoice"></i> Reporte de compras por proveedor</h2>
        <div class="subtitle"><i class="fas fa-map-marker-alt text-success"></i> Ferretería Michapa, Cuscatlán</div>

        <!-- Botón PDF a la derecha -->
        <div class="table-actions">
            <button id="exportPDF" class="btn"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
        </div>

        <!-- Tabla detallada en caja blanca extendida -->
        <div class="table-container">
            <div class="table-responsive">
                <table id="tablaComprasProveedor" class="table table-striped table-hover table-borderless table-sm align-middle text-center">
                    <thead class="table-primary">
                        <tr>
                            <th>ID Compra</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Empleado</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                            <th>Total con IVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $row) :
                            $subtotal = $row['subtotal'];
                            $total = $subtotal * 1.13;
                        ?>
                            <tr>
                                <td><?= $row['id_Compra'] ?></td>
                                <td><?= htmlspecialchars($row['proveedor']) ?></td>
                                <td><?= $row['fecha'] ?></td>
                                <td><?= htmlspecialchars($row['empleado'] . ' ' . $row['apellido_empleado']) ?></td>
                                <td><?= htmlspecialchars($row['producto']) ?></td>
                                <td><?= $row['cantidad'] ?></td>
                                <td>$<?= number_format($row['precio_unitario'], 2) ?></td>
                                <td>$<?= number_format($subtotal, 2) ?></td>
                                <td>$<?= number_format($total, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- jsPDF + AutoTable -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaComprasProveedor').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                }
            });

            document.getElementById('exportPDF').addEventListener('click', () => {
                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF('l', 'pt', 'a4');

                // Título y subtítulo
                doc.setFontSize(18);
                doc.setTextColor(33, 37, 41);
                doc.text("Reporte de compras por proveedor", doc.internal.pageSize.getWidth() / 2, 40, {
                    align: 'center'
                });
                doc.setFontSize(12);
                doc.setTextColor(73, 80, 87);
                doc.text("Ferretería Michapa, Cuscatlán", doc.internal.pageSize.getWidth() / 2, 60, {
                    align: 'center'
                });

                // Encabezado y datos
                const headers = [
                    ["ID Compra", "Proveedor", "Fecha", "Empleado", "Producto", "Cantidad", "Precio Unitario", "Subtotal", "Total con IVA"]
                ];
                const body = [];
                <?php foreach ($resultados as $row):
                    $subtotal = $row['subtotal'];
                    $total = $subtotal * 1.13;
                ?>
                    body.push([
                        "<?= $row['id_Compra'] ?>",
                        "<?= htmlspecialchars($row['proveedor']) ?>",
                        "<?= $row['fecha'] ?>",
                        "<?= htmlspecialchars($row['empleado'] . ' ' . $row['apellido_empleado']) ?>",
                        "<?= htmlspecialchars($row['producto']) ?>",
                        "<?= $row['cantidad'] ?>",
                        "$<?= number_format($row['precio_unitario'], 2) ?>",
                        "$<?= number_format($subtotal, 2) ?>",
                        "$<?= number_format($total, 2) ?>"
                    ]);
                <?php endforeach; ?>

                doc.autoTable({
                    startY: 80,
                    head: headers,
                    body: body,
                    styles: {
                        fontSize: 10,
                        halign: 'center',
                        valign: 'middle'
                    },
                    headStyles: {
                        fillColor: [13, 110, 253],
                        textColor: 255
                    }, // azul bootstrap
                    theme: 'grid',
                    margin: {
                        top: 80,
                        left: 20,
                        right: 20
                    }
                });

                doc.save("Reporte de Compras por Proveedor.pdf");
            });
        });
    </script>
</body>

</html>