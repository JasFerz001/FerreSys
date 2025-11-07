<?php
require_once '../../conexion/conexion.php';

session_start();

$conexion = new Conexion();
$db = $conexion->getConnection();

// Obtener información del empleado que genera el reporte
$empleado_generador = "Sistema";
if (isset($_SESSION['id_Empleado'])) {
    $id_empleado = $_SESSION['id_Empleado'];
    $sql_empleado = "SELECT nombre, apellido FROM empleados WHERE id_Empleado = :id_empleado";
    $stmt_empleado = $db->prepare($sql_empleado);
    $stmt_empleado->bindParam(':id_empleado', $id_empleado);
    $stmt_empleado->execute();
    $empleado = $stmt_empleado->fetch(PDO::FETCH_ASSOC);
    if ($empleado) {
        $empleado_generador = $empleado['nombre'] . ' ' . $empleado['apellido'];
    }
}

// Consulta para productos MÁS vendidos por categoría
$sql_mas_vendidos = "
    SELECT 
        c.nombre AS categoria,
        p.nombre AS producto,
        um.nombre AS unidad_medida,
        SUM(dv.cantidad) AS total_vendido,
        SUM(dv.total) AS total_ingresos
    FROM detalle_venta dv
    INNER JOIN detalle_compra dc ON dv.id_Detallecompra = dc.id_Detallecompra
    INNER JOIN producto p ON dc.id_Producto = p.id_Producto
    INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
    INNER JOIN unidad_medida um ON p.id_Medida = um.id_Medida
    INNER JOIN ventas v ON dv.id_Venta = v.id_Venta
    WHERE p.estado = 1
    GROUP BY c.id_Categoria, p.id_Producto
    HAVING total_vendido > 0
    ORDER BY c.nombre, total_vendido DESC
";

// Consulta para estadísticas generales
$sql_estadisticas = "
    SELECT 
        COUNT(DISTINCT c.id_Categoria) as total_categorias,
        SUM(dv.total) as total_ingresos,
        SUM(dv.cantidad) as total_vendido,
        COUNT(DISTINCT p.id_Producto) as total_productos
    FROM detalle_venta dv
    INNER JOIN detalle_compra dc ON dv.id_Detallecompra = dc.id_Detallecompra
    INNER JOIN producto p ON dc.id_Producto = p.id_Producto
    INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
    INNER JOIN ventas v ON dv.id_Venta = v.id_Venta
    WHERE p.estado = 1
";

$stmt_mas_vendidos = $db->prepare($sql_mas_vendidos);
$stmt_mas_vendidos->execute();
$mas_vendidos = $stmt_mas_vendidos->fetchAll(PDO::FETCH_ASSOC);

$stmt_estadisticas = $db->prepare($sql_estadisticas);
$stmt_estadisticas->execute();
$estadisticas = $stmt_estadisticas->fetch(PDO::FETCH_ASSOC);

// Organizar datos por categoría - Top 5 productos por categoría
$categorias_mas_vendidos = [];
$producto_mas_vendido_general = null;
$max_vendido = 0;

foreach ($mas_vendidos as $producto) {
    $categoria = $producto['categoria'];
    if (!isset($categorias_mas_vendidos[$categoria])) {
        $categorias_mas_vendidos[$categoria] = [];
    }
    
    // Limitar a 5 productos por categoría
    if (count($categorias_mas_vendidos[$categoria]) < 5) {
        $categorias_mas_vendidos[$categoria][] = $producto;
    }
    
    // Encontrar el producto más vendido general
    if ($producto['total_vendido'] > $max_vendido) {
        $max_vendido = $producto['total_vendido'];
        $producto_mas_vendido_general = $producto;
    }
}

// Función para formato de fecha en español
function formatoFechaEspanol($fecha)
{
    $meses = [
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];

    $fechaObj = new DateTime($fecha);
    $dia = $fechaObj->format('d');
    $mes = $meses[$fechaObj->format('F')];
    $anio = $fechaObj->format('Y');

    return "$dia de $mes de $anio";
}

// Función para formatear números con comas en miles
function formatoMoneda($numero)
{
    return number_format($numero, 2, '.', ',');
}

function formatoCantidad($numero)
{
    return number_format($numero, 0, '.', ',');
}

$fecha_actual = date('d/m/Y');
$fecha_larga = formatoFechaEspanol(date('Y-m-d'));
$hora_actual = date('H:i:s'); // Formato 24 horas
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Productos Más Vendidos | Ferretería Michapa Cuscatlán</title>

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #2C3E50;
            --primary-dark: #1a252f;
            --primary-light: #34495e;
            --secondary: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --success: #10b981;
        }

        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            min-height: 100vh;
        }

        .header-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-top: 25px;
            border-left: 5px solid var(--primary);
        }

        .company-info h1 {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-dark);
            margin: 0;
        }

        .company-info p {
            color: var(--gray);
            margin: 0;
            font-size: 0.9rem;
        }

        .report-title {
            text-align: center;
            margin: 25px 0;
        }

        .report-title h2 {
            font-weight: 600;
            color: var(--dark);
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .report-title p {
            color: var(--gray);
            margin: 0;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            background: var(--light);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.8rem;
            color: var(--gray);
            font-weight: 500;
        }

        .meta-value {
            font-weight: 600;
            color: var(--dark);
            text-align: center;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            margin-top: 25px;
        }

        .btn-pdf {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-pdf:hover {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(44, 62, 80, 0.4);
            filter: brightness(0.9);
            color: white !important;
        }

        .categoria-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0 15px 0;
        }

        .badge-success {
            background-color: var(--success);
        }

        .table-custom thead {
            background: var(--primary-light);
            color: white;
        }

        .footer-note {
            text-align: center;
            color: var(--gray);
            margin-top: 20px;
            font-size: 0.8rem;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .producto-destacado {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .report-meta {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid px-4">
        <!-- Encabezado -->
        <div class="header-container">
            <div class="company-info">
                <h1>FerreSys</h1>
                <p>Ferretería Michapa Cuscatlán</p>
            </div>

            <div class="report-title">
                <h2><i class="fas fa-trophy me-2"></i>Productos Más Vendidos por Categoría</h2>
                <p>Análisis de los productos con mejor desempeño en ventas</p>
            </div>

            <div class="report-meta">
                <div class="meta-item">
                    <span class="meta-label">Fecha de generación</span>
                    <span class="meta-value"><?= $fecha_larga ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Hora de generación</span>
                    <span class="meta-value"><?= $hora_actual ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Generado por</span>
                    <span class="meta-value"><?= htmlspecialchars($empleado_generador) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Total categorías</span>
                    <span class="meta-value"><?= $estadisticas['total_categorias'] ?></span>
                </div>
            </div>

            <div class="text-center mt-3">
                <button id="exportPDF" class="btn btn-pdf">
                    <i class="fas fa-file-pdf me-1"></i>Descargar Reporte PDF
                </button>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">$<?= formatoMoneda($estadisticas['total_ingresos']) ?></div>
                    <div class="stats-label">Total en Ingresos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= formatoCantidad($estadisticas['total_vendido']) ?></div>
                    <div class="stats-label">Total Productos Vendidos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $estadisticas['total_categorias'] ?></div>
                    <div class="stats-label">Categorías Activas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?= $estadisticas['total_productos'] ?></div>
                    <div class="stats-label">Productos Vendidos</div>
                </div>
            </div>
        </div>

        <!-- Producto Más Vendido Destacado -->
        <?php if ($producto_mas_vendido_general): ?>
        <div class="content-card">
            <div class="producto-destacado">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-2"><i class="fas fa-crown me-2"></i>Producto Más Vendido</h3>
                        <h4 class="mb-1"><?= htmlspecialchars($producto_mas_vendido_general['producto']) ?></h4>
                        <p class="mb-1">Categoría: <?= htmlspecialchars($producto_mas_vendido_general['categoria']) ?></p>
                        <p class="mb-0">Unidad: <?= htmlspecialchars($producto_mas_vendido_general['unidad_medida']) ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="stats-number"><?= formatoCantidad($producto_mas_vendido_general['total_vendido']) ?></div>
                        <div class="stats-label">Unidades Vendidas</div>
                        <div class="stats-number">$<?= formatoMoneda($producto_mas_vendido_general['total_ingresos']) ?></div>
                        <div class="stats-label">Total Generado</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Productos Más Vendidos por Categoría -->
        <div class="content-card" id="seccionMasVendidos">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0" style="color: var(--success);">
                    <i class="fas fa-arrow-up me-2"></i>Top 5 Productos Más Vendidos por Categoría
                </h3>
            </div>

            <?php if (!empty($categorias_mas_vendidos)): ?>
                <?php foreach ($categorias_mas_vendidos as $categoria => $productos): ?>
                    <div class="categoria-header">
                        <h4 class="mb-0"><i class="fas fa-folder me-2"></i><?= htmlspecialchars($categoria) ?></h4>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Unidad de Medida</th>
                                    <th>Cantidad Vendida</th>
                                    <th>Total Ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $index => $producto): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-success">
                                                #<?= $index + 1 ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($producto['producto']) ?></td>
                                        <td><?= htmlspecialchars($producto['unidad_medida']) ?></td>
                                        <td><?= formatoCantidad($producto['total_vendido']) ?></td>
                                        <td><strong>$<?= formatoMoneda($producto['total_ingresos']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>No hay datos de productos vendidos disponibles.
                </div>
            <?php endif; ?>
        </div>

        <!-- Pie -->
        <div class="footer-note">
            <p><i class="fas fa-info-circle me-2"></i>Reporte generado automáticamente por el sistema FerreSys</p>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jsPDF - Versión estable -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js"></script>

    <script>
        $(document).ready(function() {
            // Función para generar PDF
            document.getElementById('exportPDF').addEventListener('click', function() {
                generarPDF();
            });

            function generarPDF() {
                // Mostrar mensaje de carga
                const btn = document.getElementById('exportPDF');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generando PDF...';
                btn.disabled = true;

                try {
                    // Crear nuevo PDF
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const margin = 15;
                    let yPos = margin;
                    let currentPage = 1;

                    // Función para agregar nueva página si es necesario
                    function checkPageBreak(minHeight = 50) {
                        if (yPos > pageHeight - minHeight) {
                            pdf.addPage();
                            yPos = margin;
                            currentPage++;
                            // Agregar encabezado a la nueva página
                            agregarEncabezadoPagina();
                            return true;
                        }
                        return false;
                    }

                    // Función para agregar encabezado en cada página
                    function agregarEncabezadoPagina() {
                        pdf.setFillColor(44, 62, 80);
                        pdf.rect(0, 0, pageWidth, 15, 'F');
                        
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFontSize(10);
                        pdf.setFont('helvetica', 'bold');
                        pdf.text('REPORTE DE PRODUCTOS MÁS VENDIDOS - Página ' + currentPage, pageWidth / 2, 10, { align: 'center' });
                        
                        yPos = 25;
                    }

                    // Encabezado principal (solo primera página)
                    pdf.setFillColor(44, 62, 80);
                    pdf.rect(0, 0, pageWidth, 40, 'F');
                    
                    pdf.setTextColor(255, 255, 255);
                    pdf.setFontSize(20);
                    pdf.setFont('helvetica', 'bold');
                    pdf.text('REPORTE DE PRODUCTOS MÁS VENDIDOS', pageWidth / 2, 15, { align: 'center' });
                    
                    pdf.setFontSize(12);
                    pdf.text('Ferretería Michapa Cuscatlán', pageWidth / 2, 25, { align: 'center' });
                    
                    // Información del reporte
                    yPos = 50;
                    pdf.setTextColor(0, 0, 0);
                    pdf.setFontSize(10);
                    pdf.setFont('helvetica', 'normal');
                    
                    // Información fija desde PHP
                    pdf.text('Fecha: <?= $fecha_larga ?>', margin, yPos);
                    pdf.text('Hora: <?= $hora_actual ?>', pageWidth / 2, yPos);
                    pdf.text('Generado por: <?= htmlspecialchars($empleado_generador) ?>', pageWidth - margin, yPos, { align: 'right' });
                    
                    yPos += 15;
                    pdf.text('Total Ingresos: $<?= formatoMoneda($estadisticas['total_ingresos']) ?>', margin, yPos);
                    pdf.text('Productos Vendidos: <?= formatoCantidad($estadisticas['total_vendido']) ?>', pageWidth / 2, yPos);
                    pdf.text('Categorías: <?= $estadisticas['total_categorias'] ?>', pageWidth - margin, yPos, { align: 'right' });

                    yPos += 25;

                    // Producto Más Vendido Destacado
                    <?php if ($producto_mas_vendido_general): ?>
                    checkPageBreak(30);
                    pdf.setFontSize(12);
                    pdf.setFont('helvetica', 'bold');
                    pdf.setTextColor(16, 185, 129);
                    pdf.text('PRODUCTO MÁS VENDIDO DESTACADO:', margin, yPos);
                    yPos += 7;
                    
                    pdf.setFontSize(10);
                    pdf.setTextColor(0, 0, 0);
                    pdf.text('Producto: <?= htmlspecialchars($producto_mas_vendido_general['producto']) ?>', margin, yPos);
                    yPos += 5;
                    pdf.text('Categoría: <?= htmlspecialchars($producto_mas_vendido_general['categoria']) ?>', margin, yPos);
                    yPos += 5;
                    pdf.text('Unidades Vendidas: <?= formatoCantidad($producto_mas_vendido_general['total_vendido']) ?>', margin, yPos);
                    yPos += 5;
                    pdf.text('Total Generado: $<?= formatoMoneda($producto_mas_vendido_general['total_ingresos']) ?>', margin, yPos);
                    yPos += 15;
                    <?php endif; ?>

                    // Productos Más Vendidos por Categoría
                    checkPageBreak(100);
                    pdf.setFontSize(14);
                    pdf.setTextColor(16, 185, 129);
                    pdf.text('TOP 5 PRODUCTOS MÁS VENDIDOS POR CATEGORÍA', margin, yPos);
                    yPos += 10;

                    pdf.setTextColor(0, 0, 0);
                    pdf.setFontSize(10);

                    <?php if (!empty($categorias_mas_vendidos)): ?>
                        <?php foreach ($categorias_mas_vendidos as $categoria => $productos): ?>
                            checkPageBreak(50);
                            pdf.setFontSize(11);
                            pdf.setFont('helvetica', 'bold');
                            pdf.setTextColor(44, 62, 80);
                            pdf.text('<?= htmlspecialchars($categoria) ?>', margin, yPos);
                            yPos += 7;

                            pdf.setFontSize(9);
                            pdf.setFont('helvetica', 'normal');
                            pdf.setTextColor(0, 0, 0);
                            
                            // Encabezados de tabla
                            pdf.text('#', margin, yPos);
                            pdf.text('Producto', margin + 10, yPos);
                            pdf.text('Unidad', margin + 80, yPos);
                            pdf.text('Cantidad', margin + 110, yPos);
                            pdf.text('Ingresos', margin + 140, yPos);
                            yPos += 5;

                            // Línea separadora
                            pdf.setDrawColor(200, 200, 200);
                            pdf.line(margin, yPos, pageWidth - margin, yPos);
                            yPos += 5;

                            <?php foreach ($productos as $index => $producto): ?>
                                checkPageBreak(20);
                                pdf.text('<?= $index + 1 ?>', margin, yPos);
                                
                                pdf.text('<?= htmlspecialchars(mb_substr($producto['producto'], 0, 25)) ?>', margin + 10, yPos);
                                pdf.text('<?= htmlspecialchars($producto['unidad_medida']) ?>', margin + 80, yPos);
                                pdf.text('<?= formatoCantidad($producto['total_vendido']) ?>', margin + 110, yPos);
                                pdf.text('$<?= formatoMoneda($producto['total_ingresos']) ?>', margin + 140, yPos);
                                yPos += 6;
                            <?php endforeach; ?>
                            
                            yPos += 10;
                        <?php endforeach; ?>
                    <?php endif; ?>

                    // Pie de página en todas las páginas
                    const totalPages = pdf.internal.getNumberOfPages();
                    for (let i = 1; i <= totalPages; i++) {
                        pdf.setPage(i);
                        pdf.setFontSize(8);
                        pdf.setTextColor(100, 100, 100);
                        pdf.text(
                            'Página ' + i + ' de ' + totalPages + ' - Reporte generado por FerreSys', 
                            pageWidth / 2, 
                            pageHeight - 10, 
                            { align: 'center' }
                        );
                    }

                    // Descargar PDF
                    pdf.save('Reporte_Productos_Mas_Vendidos_<?= date('Y-m-d') ?>.pdf');

                    console.log('PDF generado exitosamente');

                } catch (error) {
                    console.error('Error al generar PDF:', error);
                    alert('Error al generar el PDF: ' + error.message);
                } finally {
                    // Restaurar botón
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }
        });
    </script>

</body>

</html>