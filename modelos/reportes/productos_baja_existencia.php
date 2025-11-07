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

// Consulta de productos con baja existencia
$sql = "SELECT 
            pr.nombre AS producto,
            c.nombre AS categoria,
            u.simbolo AS unidad,
            dc.existencia,
            dc.precio_unitario,
            p.nombre AS proveedor
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

// Calcular estadísticas
$total_productos = count($resultados);
$productos_criticos = 0;
$productos_bajos = 0;

foreach ($resultados as $row) {
    if ($row['existencia'] <= 3) {
        $productos_criticos++;
    } else {
        $productos_bajos++;
    }
}

// Función para formato de fecha en español
function formatoFechaEspanol($fecha)
{
    $meses = [
        'January' => 'Enero',
        'February' => 'Febrero',
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre'
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

$fecha_actual = date('d/m/Y');
$fecha_larga = formatoFechaEspanol(date('Y-m-d'));
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Productos con Baja Existencia | Ferretería Michapa Cuscatlán</title>

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
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #dc3545;
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

        .stats-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 4px solid var(--primary);
        }

        .stat-card.critical {
            border-top-color: var(--danger);
        }

        .stat-card.warning {
            border-top-color: var(--warning);
        }

        .stat-card.total {
            border-top-color: var(--success);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
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

        .existencia-critica {
            background-color: #f8d7da !important;
            color: var(--danger);
            font-weight: 700;
        }

        .existencia-baja {
            background-color: #fff3cd !important;
            color: var(--warning);
            font-weight: 600;
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

        .badge-critical {
            background-color: var(--danger);
        }

        .badge-warning {
            background-color: var(--warning);
        }

        @media (max-width: 768px) {
            .report-meta, .stats-container {
                flex-direction: column;
            }
            .company-logo {
                flex-direction: column;
                text-align: center;
            }
            .stat-card {
                min-width: 100%;
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
                <h2><i class="fas fa-triangle-exclamation me-2"></i>Reporte de Productos con Baja Existencia</h2>
                <p>Alerta de productos que requieren reabastecimiento</p>
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
                    <span class="meta-label">Total de productos</span>
                    <span class="meta-value"><?= $total_productos ?></span>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-container">
                <div class="stat-card critical">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    </div>
                    <div class="stat-value text-danger"><?= $productos_criticos ?></div>
                    <div class="stat-label">Productos Críticos (≤ 3 unidades)</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle fa-2x text-warning"></i>
                    </div>
                    <div class="stat-value text-warning"><?= $productos_bajos ?></div>
                    <div class="stat-label">Productos Bajos (4-5 unidades)</div>
                </div>
                <div class="stat-card total">
                    <div class="stat-icon">
                        <i class="fas fa-boxes fa-2x text-success"></i>
                    </div>
                    <div class="stat-value text-success"><?= $total_productos ?></div>
                    <div class="stat-label">Total Productos con Baja Existencia</div>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0" style="color: var(--primary-dark);">
                    <i class="fas fa-list-alt me-2"></i>Detalle de Productos
                </h3>
                <button id="exportPDF" class="btn btn-pdf">
                    <i class="fas fa-file-pdf me-1"></i>Descargar PDF
                </button>
            </div>

            <div class="table-responsive">
                <table id="tablaBajaExistencia" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Unidad</th>
                            <th>Existencia</th>
                            <th>Precio Unitario</th>
                            <th>Proveedor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $row):
                            $clase_existencia = $row['existencia'] <= 3 ? 'existencia-critica' : 'existencia-baja';
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['producto']) ?></td>
                                <td><?= htmlspecialchars($row['categoria']) ?></td>
                                <td><?= htmlspecialchars($row['unidad']) ?></td>
                                <td class="<?= $clase_existencia ?>">
                                    <?= $row['existencia'] ?>
                                    <?php if ($row['existencia'] <= 3): ?>
                                        <span class="badge bg-danger ms-1">Crítico</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning ms-1">Bajo</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= formatoMoneda($row['precio_unitario']) ?></td>
                                <td><?= htmlspecialchars($row['proveedor']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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

            // Inicializar DataTable
            const tabla = $('#tablaBajaExistencia').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 productos",
                    infoFiltered: "(filtrado de _MAX_ registros totales)"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                order: [],
                drawCallback: function(settings) {
                    const api = this.api();
                    const pageInfo = api.page.info();

                    // Calcular registros mostrados en página actual
                    let start = pageInfo.start + 1;
                    let end = pageInfo.end;
                    let total = <?= $total_productos ?>;

                    // Actualizar el texto de información
                    $(this).closest('.dataTables_wrapper').find('.dataTables_info').html(
                        `Mostrando ${start} a ${end} de ${total} registros`
                    );
                }
            });

            // Función para generar PDF 
            document.getElementById('exportPDF').addEventListener('click', function() {
                generarPDF();
            });

            function generarPDF() {
                const { jsPDF } = window.jspdf;

                // Mostrar mensaje de carga
                const btn = document.getElementById('exportPDF');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generando PDF...';
                btn.disabled = true;

                try {
                    // Crear nuevo PDF en modo horizontal (landscape)
                    const pdf = new jsPDF('l', 'mm', 'letter');
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();

                    // Datos para el PDF
                    const datos = <?= json_encode($resultados) ?>;
                    const registrosPorPagina = 15;
                    const totalPaginas = Math.ceil(datos.length / registrosPorPagina);
                    const fechaGeneracion = new Date();
                    const horaGeneracion = formatearHora(fechaGeneracion);

                    // Anchuras de columnas optimizadas para landscape
                    const columnWidths = {
                        producto: 55,
                        categoria: 40,
                        unidad: 25,
                        existencia: 30,
                        precio: 35,
                        proveedor: 55
                    };

                    // Función para agregar encabezado (SOLO en la primera página)
                    function agregarEncabezado(pdf, esPrimeraPagina) {
                        if (!esPrimeraPagina) return;

                        // Fondo con el color azul del reporte de compras
                        pdf.setFillColor(44, 62, 80); // #2C3E50
                        pdf.rect(0, 0, pageWidth, 45, 'F');

                        // Título del reporte centrado
                        pdf.setFontSize(20);
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'bold');
                        pdf.text('FERRE SYS - REPORTE DE PRODUCTOS CON BAJA EXISTENCIA', pageWidth / 2, 18, {
                            align: 'center'
                        });

                        pdf.setFontSize(12);
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'normal');
                        pdf.text('Ferretería Michapa Cuscatlán - Alerta de productos que requieren reabastecimiento urgente', pageWidth / 2, 25, {
                            align: 'center'
                        });

                        // Línea decorativa NARANJA (como en compras)
                        pdf.setDrawColor(245, 158, 11); // #f59e0b
                        pdf.setLineWidth(1.5);
                        pdf.line(20, 32, pageWidth - 20, 32);

                        // Información de metadata
                        const metadataY = 38;
                        pdf.setFontSize(11);
                        pdf.setTextColor(255, 255, 255);

                        pdf.text(`Fecha: ${formatearFecha(fechaGeneracion)}`, 20, metadataY);
                        pdf.text(`Hora: ${horaGeneracion}`, 20, metadataY + 5);
                        pdf.text(`Generado por: ${'<?= htmlspecialchars($empleado_generador) ?>'}`, pageWidth / 2, metadataY);
                        pdf.text(`Productos críticos: ${formatoCantidad(<?= $productos_criticos ?>)}`, pageWidth / 2, metadataY + 5);
                        pdf.text(`Páginas: ${totalPaginas}`, pageWidth - 20, metadataY, { align: 'right' });
                        pdf.text(`Total productos: ${formatoCantidad(<?= $total_productos ?>)}`, pageWidth - 20, metadataY + 5, { align: 'right' });
                    }

                    // Función para agregar tabla
                    function agregarTabla(pdf, datosPagina, paginaActual, esPrimeraPagina) {
                        const startY = esPrimeraPagina ? 55 : 20;

                        // Encabezado de tabla - Mismo color azul
                        pdf.setFillColor(44, 62, 80); // #2C3E50
                        pdf.roundedRect(15, startY, pageWidth - 30, 8, 2, 2, 'F');

                        pdf.setFontSize(11);
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'bold');

                        // Encabezados de columnas con anchuras específicas
                        let xPos = 17;
                        pdf.text('PRODUCTO', xPos, startY + 5.5);
                        xPos += columnWidths.producto;

                        pdf.text('CATEGORÍA', xPos, startY + 5.5);
                        xPos += columnWidths.categoria;

                        pdf.text('UNIDAD', xPos, startY + 5.5);
                        xPos += columnWidths.unidad;

                        pdf.text('EXISTENCIA', xPos, startY + 5.5);
                        xPos += columnWidths.existencia;

                        pdf.text('PRECIO UNIT.', xPos, startY + 5.5);
                        xPos += columnWidths.precio;

                        pdf.text('PROVEEDOR', xPos, startY + 5.5);

                        // Datos de la tabla
                        let yPos = startY + 16;
                        pdf.setFontSize(10);
                        pdf.setFont('helvetica', 'normal');

                        datosPagina.forEach((row, index) => {
                            // Verificar si necesitamos nueva página
                            if (yPos > pageHeight - 30) {
                                agregarPiePagina(pdf, paginaActual, totalPaginas);
                                pdf.addPage();
                                paginaActual++;
                                yPos = 20;

                                // Redibujar encabezado de tabla en nueva página
                                pdf.setFillColor(44, 62, 80);
                                pdf.roundedRect(15, yPos - 8, pageWidth - 30, 8, 2, 2, 'F');
                                pdf.setTextColor(255, 255, 255);
                                pdf.setFont('helvetica', 'bold');
                                pdf.setFontSize(11);

                                xPos = 17;
                                pdf.text('PRODUCTO', xPos, yPos - 2.5);
                                xPos += columnWidths.producto;
                                pdf.text('CATEGORÍA', xPos, yPos - 2.5);
                                xPos += columnWidths.categoria;
                                pdf.text('UNIDAD', xPos, yPos - 2.5);
                                xPos += columnWidths.unidad;
                                pdf.text('EXISTENCIA', xPos, yPos - 2.5);
                                xPos += columnWidths.existencia;
                                pdf.text('PRECIO UNIT.', xPos, yPos - 2.5);
                                xPos += columnWidths.precio;
                                pdf.text('PROVEEDOR', xPos, yPos - 2.5);

                                yPos += 8;
                                pdf.setTextColor(30, 41, 59); // Color oscuro para texto normal
                                pdf.setFont('helvetica', 'normal');
                                pdf.setFontSize(10);
                            }

                            // Fondo alternado: una fila blanca, una gris clarito (como en compras)
                            if (index % 2 === 0) {
                                pdf.setFillColor(255, 255, 255); // Blanco
                            } else {
                                pdf.setFillColor(248, 250, 252); // Gris muy clarito
                            }
                            pdf.roundedRect(15, yPos - 4, pageWidth - 30, 5, 1, 1, 'F');

                            xPos = 17;

                            // Datos de la fila - TEXTO NEGRO SIEMPRE como en compras
                            pdf.setTextColor(30, 41, 59); // Color oscuro para todo el texto

                            const producto = row.producto.length > 30 ? row.producto.substring(0, 30) + '...' : row.producto;
                            pdf.text(producto, xPos, yPos);
                            xPos += columnWidths.producto;

                            const categoria = row.categoria.length > 15 ? row.categoria.substring(0, 15) + '...' : row.categoria;
                            pdf.text(categoria, xPos, yPos);
                            xPos += columnWidths.categoria;

                            pdf.text(row.unidad, xPos, yPos);
                            xPos += columnWidths.unidad;

                            // Existencia - mantener negro pero con estilo bold para destacar
                            pdf.setFont('helvetica', 'bold');
                            pdf.text(row.existencia.toString(), xPos, yPos);
                            pdf.setFont('helvetica', 'normal');
                            xPos += columnWidths.existencia;

                            pdf.text(`$${formatoMoneda(parseFloat(row.precio_unitario))}`, xPos, yPos);
                            xPos += columnWidths.precio;

                            const proveedor = row.proveedor.length > 25 ? row.proveedor.substring(0, 25) + '...' : row.proveedor;
                            pdf.text(proveedor, xPos, yPos);

                            yPos += 5;
                        });

                        return {
                            yPos,
                            paginaActual
                        };
                    }

                    // Función para agregar estadísticas (SOLO en la última página)
                    function agregarEstadisticas(pdf, yPos, esUltimaPagina) {
                        if (!esUltimaPagina) return;

                        // Línea separadora
                        pdf.setDrawColor(44, 62, 80);
                        pdf.setLineWidth(1);
                        pdf.line(15, yPos + 5, pageWidth - 15, yPos + 5);

                        // Estadísticas
                        pdf.setFontSize(12);
                        pdf.setFont('helvetica', 'bold');
                        pdf.setTextColor(30, 41, 59);
                        
                        pdf.text(`Productos Críticos (<= 3 unidades): ${formatoCantidad(<?= $productos_criticos ?>)}`, 20, yPos + 15);
                        pdf.text(`Productos Bajos (4-5 unidades): ${formatoCantidad(<?= $productos_bajos ?>)}`, 20, yPos + 25);
                        pdf.text(`Total Productos con Baja Existencia: ${formatoCantidad(<?= $total_productos ?>)}`, pageWidth - 20, yPos + 15, {
                            align: 'right'
                        });

                        return yPos + 30;
                    }

                    // Función para agregar pie de página
                    function agregarPiePagina(pdf, paginaActual, totalPaginas) {
                        pdf.setFontSize(10);
                        pdf.setTextColor(100, 116, 139);
                        pdf.setFont('helvetica', 'normal');

                        // Línea superior del pie
                        pdf.setDrawColor(226, 232, 240);
                        pdf.setLineWidth(0.5);
                        pdf.line(15, pageHeight - 15, pageWidth - 15, pageHeight - 15);

                        pdf.text('Reporte generado automáticamente por el sistema FerreSys', pageWidth / 2, pageHeight - 10, {
                            align: 'center'
                        });
                        pdf.text(`Página ${paginaActual} de ${totalPaginas}`, pageWidth - 20, pageHeight - 10, {
                            align: 'right'
                        });
                    }

                    // Generar páginas
                    let currentPage = 1;
                    for (let pagina = 0; pagina < totalPaginas; pagina++) {
                        if (pagina > 0) {
                            pdf.addPage();
                            currentPage++;
                        }

                        const inicio = pagina * registrosPorPagina;
                        const fin = inicio + registrosPorPagina;
                        const datosPagina = datos.slice(inicio, fin);
                        const esPrimeraPagina = (pagina === 0);
                        const esUltimaPagina = (pagina === totalPaginas - 1);

                        // Agregar elementos a la página
                        agregarEncabezado(pdf, esPrimeraPagina);

                        const resultadoTabla = agregarTabla(pdf, datosPagina, currentPage, esPrimeraPagina);

                        // Agregar estadísticas SOLO en la última página
                        const yPosDespuesTabla = agregarEstadisticas(pdf, resultadoTabla.yPos, esUltimaPagina);

                        // Agregar pie de página en TODAS las páginas
                        agregarPiePagina(pdf, currentPage, totalPaginas);
                    }

                    // Descargar PDF con nombre específico
                    pdf.save('Reporte de productos con baja existencia.pdf');

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