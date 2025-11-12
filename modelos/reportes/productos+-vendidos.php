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

// Consulta para productos más vendidos por categoría
$sql_mas_vendidos = "
    SELECT 
        c.nombre AS categoria,
        p.nombre AS producto,
        um.nombre AS unidad_medida,
        SUM(dv.cantidad) AS total_vendido,
        SUM(dv.total) AS total_ingresos
    FROM detalle_venta dv
    INNER JOIN ventas v ON dv.id_Venta = v.id_Venta
    INNER JOIN detalle_compra dc ON dv.id_Detallecompra = dc.id_Detallecompra
    INNER JOIN producto p ON dc.id_Producto = p.id_Producto
    INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
    INNER JOIN unidad_medida um ON p.id_Medida = um.id_Medida
    WHERE p.estado = 1
    GROUP BY c.id_Categoria, p.id_Producto, um.nombre
    HAVING total_vendido > 0
    ORDER BY c.nombre, total_vendido DESC
";

$stmt_mas_vendidos = $db->prepare($sql_mas_vendidos);
$stmt_mas_vendidos->execute();
$mas_vendidos = $stmt_mas_vendidos->fetchAll(PDO::FETCH_ASSOC);

// Organizar datos por categoría
$categorias_mas_vendidos = [];
$producto_mas_vendido_general = null;
$max_vendido = 0;

foreach ($mas_vendidos as $producto) {
    $categoria = $producto['categoria'];
    if (!isset($categorias_mas_vendidos[$categoria])) {
        $categorias_mas_vendidos[$categoria] = [];
    }
    
    // Agregar producto a la categoría
    $categorias_mas_vendidos[$categoria][] = $producto;
    
    // Encontrar el producto más vendido general
    if ($producto['total_vendido'] > $max_vendido) {
        $max_vendido = $producto['total_vendido'];
        $producto_mas_vendido_general = $producto;
    }
}

// Contar categorías con productos vendidos
$total_categorias = count($categorias_mas_vendidos);

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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Productos Más Vendidos | Ferretería Michapa Cuscatlán</title>

    <!-- Bootstrap + DataTables + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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

        .company-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .logo-icon {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(44, 62, 80, 0.3);
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

        table.dataTable {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            width: 100% !important;
        }

        table.dataTable thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        table.dataTable thead th {
            border: none;
            padding: 15px 10px;
            font-weight: 500;
            text-align: center;
        }

        table.dataTable tbody tr {
            transition: all 0.2s ease;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #f8fafc;
        }

        table.dataTable tbody tr:hover {
            background-color: #e0f2fe;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table.dataTable tbody td {
            padding: 12px 10px;
            border-color: #e2e8f0;
            text-align: center;
            vertical-align: middle;
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

        .badge-primary {
            background-color: var(--primary);
        }

        .producto-destacado {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .report-meta {
                flex-direction: column;
            }

            .company-logo {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid px-4">
        <!-- Encabezado -->
        <div class="header-container">
            <div class="company-logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="company-info">
                    <h1>FerreSys</h1>
                    <p>Ferretería Michapa Cuscatlán</p>
                </div>
            </div>

            <div class="report-title">
                <h2><i class="fas fa-trophy me-2"></i>Productos Más Vendidos por Categoría</h2>
                <p>Análisis de los productos con mejor desempeño en ventas</p>
            </div>

            <div class="report-meta">
                <div class="meta-item">
                    <span class="meta-label">Fecha de generación</span>
                    <span class="meta-value" id="fechaGeneracion"><?= $fecha_larga ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Hora de generación</span>
                    <span class="meta-value" id="horaActual">Cargando...</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Generado por</span>
                    <span class="meta-value"><?= htmlspecialchars($empleado_generador) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Total categorías</span>
                    <span class="meta-value"><?= $total_categorias ?></span>
                </div>
            </div>

            <div class="text-center mt-3">
                <button id="exportPDF" class="btn btn-pdf">
                    <i class="fas fa-file-pdf me-1"></i>Descargar Reporte PDF
                </button>
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
                        <div class="stats-number" style="font-size: 2rem; font-weight: bold;"><?= formatoCantidad($producto_mas_vendido_general['total_vendido']) ?></div>
                        <div class="stats-label">Unidades Vendidas</div>
                        <div class="stats-number" style="font-size: 1.5rem; font-weight: bold;">$<?= formatoMoneda($producto_mas_vendido_general['total_ingresos']) ?></div>
                        <div class="stats-label">Total Generado</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Productos Más Vendidos por Categoría -->
        <div class="content-card" id="seccionMasVendidos">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0" style="color: var(--primary-dark);">
                    <i class="fas fa-chart-bar me-2"></i>Productos Más Vendidos por Categoría
                </h3>
            </div>

            <?php if (!empty($categorias_mas_vendidos)): ?>
                <?php foreach ($categorias_mas_vendidos as $categoria => $productos): ?>
                    <div class="categoria-header">
                        <h4 class="mb-0"><i class="fas fa-folder me-2"></i><?= htmlspecialchars($categoria) ?></h4>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
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
                                            <span class="badge bg-primary">
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        $(document).ready(function() {
            // Función para formatear fecha en español
            function formatearFecha(fecha) {
                const opciones = {
                    year: 'numeric',
                    month: 'long',
                    day: '2-digit',
                    timeZone: 'America/El_Salvador'
                };
                return fecha.toLocaleDateString('es-ES', opciones);
            }

            // Función para formatear hora
            function formatearHora(fecha) {
                const opciones = {
                    hour12: true,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZone: 'America/El_Salvador'
                };
                return fecha.toLocaleTimeString('es-SV', opciones);
            }

            // Función para formatear números con comas en miles
            function formatoMoneda(numero) {
                return new Intl.NumberFormat('es-SV', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(numero);
            }

            // Función para formatear cantidades con comas en miles
            function formatoCantidad(numero) {
                return new Intl.NumberFormat('es-SV').format(numero);
            }

            // Actualizar hora actual en la web
            function actualizarHora() {
                const ahora = new Date();
                const fechaLarga = formatearFecha(ahora);
                const horaLarga = formatearHora(ahora);

                $('#horaActual').text(horaLarga);
                $('#fechaGeneracion').text(fechaLarga);
            }

            // Actualizar inmediatamente y cada segundo
            actualizarHora();
            setInterval(actualizarHora, 1000);

            // Inicializar DataTable en las tablas de categorías
            $('table').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                order: [],
                searching: false,
                info: false,
                paging: false
            });

            // Función para generar PDF 
            document.getElementById('exportPDF').addEventListener('click', function() {
                generarPDF();
            });

            function generarPDF() {
                const {
                    jsPDF
                } = window.jspdf;

                // Mostrar mensaje de carga
                const btn = document.getElementById('exportPDF');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generando PDF...';
                btn.disabled = true;

                try {
                    // Crear nuevo PDF en modo horizontal para mejor visualización
                    const pdf = new jsPDF('l', 'mm', 'a4');
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();
                    const margin = 15;
                    let yPos = margin;
                    let currentPage = 1;

                    // Datos para el PDF
                    const categoriasData = <?= json_encode($categorias_mas_vendidos) ?>;
                    const productoDestacado = <?= json_encode($producto_mas_vendido_general) ?>;
                    const fechaGeneracion = new Date();
                    const horaGeneracion = formatearHora(fechaGeneracion);

                    // Función para agregar encabezado
                    function agregarEncabezado(pdf, esPrimeraPagina) {
                        if (!esPrimeraPagina) return;

                        // Fondo con el color principal
                        pdf.setFillColor(44, 62, 80);
                        pdf.rect(0, 0, pageWidth, 45, 'F');

                        // Título del reporte con letras más grandes
                        pdf.setFontSize(22);
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'bold');
                        pdf.text('REPORTE DE PRODUCTOS MÁS VENDIDOS', pageWidth / 2, 18, {
                            align: 'center'
                        });

                        pdf.setFontSize(14);
                        pdf.text('Ferretería Michapa Cuscatlán', pageWidth / 2, 26, {
                            align: 'center'
                        });

                        // Información del reporte - ORGANIZADA EN 2 LÍNEAS CON LETRAS MÁS GRANDES
                        pdf.setFontSize(11);
                        
                        // Primera línea
                        pdf.text(`Fecha: ${formatearFecha(fechaGeneracion)}`, margin, 37);
                        pdf.text(`Generado por: ${'<?= htmlspecialchars($empleado_generador) ?>'}`, pageWidth / 2, 37);
                        pdf.text(`Categorías: ${<?= $total_categorias ?>}`, pageWidth - margin, 37, { align: 'right' });
                        
                        // Segunda línea
                        pdf.text(`Hora: ${horaGeneracion}`, margin, 42);

                        yPos = 50;
                    }

                    // Función para agregar producto destacado
                    function agregarProductoDestacado(pdf, yPos) {
                        if (!productoDestacado) return yPos;

                        // Verificar espacio en página
                        if (yPos > pageHeight - 40) {
                            pdf.addPage();
                            yPos = margin;
                            currentPage++;
                        }

                        // Fondo azul para producto destacado - ALTURA AUMENTADA
                        pdf.setFillColor(44, 62, 80);
                        pdf.roundedRect(margin, yPos, pageWidth - (2 * margin), 25, 3, 3, 'F');
                        
                        pdf.setFontSize(13);
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'bold');
                        
                        // Título en la primera línea
                        pdf.text('PRODUCTO MÁS VENDIDO:', margin + 5, yPos + 8);

                        // Información en líneas separadas
                        pdf.setFontSize(11);
                        pdf.setFont('helvetica', 'normal');
                        
                        // Primera línea de información
                        pdf.text(`Producto: ${productoDestacado.producto}`, margin + 5, yPos + 14);
                        pdf.text(`Categoría: ${productoDestacado.categoria}`, pageWidth / 2, yPos + 14);
                        
                        // Segunda línea de información
                        pdf.text(`Unidades: ${formatoCantidad(productoDestacado.total_vendido)}`, margin + 5, yPos + 20);
                        pdf.text(`Ingresos: $${formatoMoneda(productoDestacado.total_ingresos)}`, pageWidth / 2, yPos + 20);

                        return yPos + 30;
                    }

                    // Función para agregar tabla de categorías
                    function agregarTablaCategorias(pdf, yPos) {
                        // Título de la sección con letras más grandes
                        pdf.setFontSize(16);
                        pdf.setTextColor(44, 62, 80);
                        pdf.setFont('helvetica', 'bold');
                        pdf.text('PRODUCTOS MÁS VENDIDOS POR CATEGORÍA', pageWidth / 2, yPos, { align: 'center' });
                        yPos += 12;

                        pdf.setFontSize(11);
                        pdf.setTextColor(0, 0, 0);

                        // Definir posiciones de columnas CON RANGOS COMPLETOS
                        const colPositions = {
                            numero: { x: margin + 10, width: 15 },
                            producto: { x: margin + 25, width: 75 },
                            unidad: { x: margin + 100, width: 40 },
                            cantidad: { x: margin + 140, width: 30 },
                            ingresos: { x: margin + 170, width: 50 }
                        };

                        // Calcular CENTRO de cada columna
                        const centroNumero = colPositions.numero.x + (colPositions.numero.width / 2);
                        const centroProducto = colPositions.producto.x + (colPositions.producto.width / 2);
                        const centroUnidad = colPositions.unidad.x + (colPositions.unidad.width / 2);
                        const centroCantidad = colPositions.cantidad.x + (colPositions.cantidad.width / 2);
                        const centroIngresos = colPositions.ingresos.x + (colPositions.ingresos.width / 2);

                        Object.keys(categoriasData).forEach((categoria, catIndex) => {
                            const productos = categoriasData[categoria];

                            // Verificar si necesitamos nueva página antes de agregar categoría
                            if (yPos > pageHeight - 60) {
                                pdf.addPage();
                                yPos = margin;
                                currentPage++;
                            }

                            // Encabezado de categoría con letras más grandes
                            pdf.setFillColor(200, 200, 200);
                            pdf.roundedRect(margin, yPos, pageWidth - (2 * margin), 8, 2, 2, 'F');
                            
                            pdf.setTextColor(0, 0, 0);
                            pdf.setFont('helvetica', 'bold');
                            pdf.setFontSize(12);
                            pdf.text(categoria.toUpperCase(), margin + 5, yPos + 5.5);
                            yPos += 10;

                            // Encabezados de tabla con letras más grandes
                            pdf.setFontSize(11);
                            pdf.setFillColor(240, 240, 240);
                            pdf.roundedRect(margin, yPos, pageWidth - (2 * margin), 8, 1, 1, 'F');
                            
                            pdf.text('#', centroNumero, yPos + 5, { align: 'center' });
                            pdf.text('PRODUCTO', centroProducto, yPos + 5, { align: 'center' });
                            pdf.text('UNIDAD', centroUnidad, yPos + 5, { align: 'center' });
                            pdf.text('CANTIDAD', centroCantidad, yPos + 5, { align: 'center' });
                            pdf.text('INGRESOS', centroIngresos, yPos + 5, { align: 'center' });
                            yPos += 10;

                            // Productos
                            pdf.setFont('helvetica', 'normal');
                            pdf.setFontSize(10);
                            productos.forEach((producto, index) => {
                                // Verificar si necesitamos nueva página para el siguiente producto
                                if (yPos > pageHeight - 20) {
                                    pdf.addPage();
                                    yPos = margin;
                                    currentPage++;
                                    
                                    // Redibujar encabezados si es nueva página
                                    pdf.setFontSize(11);
                                    pdf.setFillColor(240, 240, 240);
                                    pdf.roundedRect(margin, yPos, pageWidth - (2 * margin), 8, 1, 1, 'F');
                                    
                                    pdf.text('#', centroNumero, yPos + 5, { align: 'center' });
                                    pdf.text('PRODUCTO', centroProducto, yPos + 5, { align: 'center' });
                                    pdf.text('UNIDAD', centroUnidad, yPos + 5, { align: 'center' });
                                    pdf.text('CANTIDAD', centroCantidad, yPos + 5, { align: 'center' });
                                    pdf.text('INGRESOS', centroIngresos, yPos + 5, { align: 'center' });
                                    yPos += 10;
                                    pdf.setFontSize(10);
                                }

                                // Alternar colores de fila
                                if (index % 2 === 0) {
                                    pdf.setFillColor(250, 250, 250);
                                    pdf.roundedRect(margin, yPos - 2, pageWidth - (2 * margin), 6, 1, 1, 'F');
                                }

                                pdf.setTextColor(0, 0, 0);
                                
                                // Número CENTRADO
                                pdf.text((index + 1).toString(), centroNumero, yPos + 3, { align: 'center' });
                                
                                // Producto (truncado si es muy largo) CENTRADO
                                const nombreProducto = producto.producto.length > 30 ? 
                                    producto.producto.substring(0, 30) + '...' : producto.producto;
                                pdf.text(nombreProducto, centroProducto, yPos + 3, { align: 'center' });
                                
                                // Unidad CENTRADA
                                pdf.text(producto.unidad_medida, centroUnidad, yPos + 3, { align: 'center' });
                                
                                // Cantidad CENTRADA
                                pdf.text(formatoCantidad(producto.total_vendido), centroCantidad, yPos + 3, { align: 'center' });
                                
                                // Ingresos CENTRADOS
                                pdf.text(`$${formatoMoneda(producto.total_ingresos)}`, centroIngresos, yPos + 3, { align: 'center' });
                                
                                yPos += 6;
                            });

                            yPos += 10;
                        });

                        return yPos;
                    }

                    // Función para agregar pie de página
                    function agregarPiePagina(pdf, paginaActual) {
                        const totalPaginas = pdf.internal.getNumberOfPages();
                        pdf.setFontSize(10);
                        pdf.setTextColor(100, 100, 100);
                        pdf.setFont('helvetica', 'normal');

                        pdf.text('Reporte generado automáticamente por el sistema FerreSys', pageWidth / 2, pageHeight - 10, {
                            align: 'center'
                        });
                        pdf.text(`Página ${paginaActual} de ${totalPaginas}`, pageWidth - margin, pageHeight - 10, {
                            align: 'right'
                        });
                    }

                    // Generar contenido del PDF
                    agregarEncabezado(pdf, true);
                    yPos = agregarProductoDestacado(pdf, yPos);
                    yPos = agregarTablaCategorias(pdf, yPos);

                    // Agregar pie de página en todas las páginas
                    const totalPages = pdf.internal.getNumberOfPages();
                    for (let i = 1; i <= totalPages; i++) {
                        pdf.setPage(i);
                        agregarPiePagina(pdf, i);
                    }

                    // Descargar PDF
                    pdf.save('Reporte_Productos_Mas_Vendidos_<?= date('Y-m-d') ?>.pdf');

                } catch (error) {
                    console.error('Error al generar PDF:', error);
                    alert('Error al generar el PDF. Intente nuevamente.');
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