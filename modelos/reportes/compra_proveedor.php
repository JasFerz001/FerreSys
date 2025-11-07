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

// Consulta principal - ORDEN DESCENDENTE por id_Compra
$sql = "SELECT 
            p.id_Proveedor, p.nombre AS proveedor, 
            c.id_Compra, c.fecha, 
            pr.nombre AS producto, dc.cantidad, dc.precio_unitario, 
            (dc.cantidad * dc.precio_unitario) AS subtotal
        FROM compra c
        INNER JOIN proveedores p ON c.id_Proveedor = p.id_Proveedor
        INNER JOIN detalle_compra dc ON c.id_Compra = dc.id_Compra
        INNER JOIN producto pr ON dc.id_Producto = pr.id_Producto
        ORDER BY c.id_Compra DESC, pr.nombre"; // CAMBIO: DESC en lugar de ASC

$stmt = $db->prepare($sql);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total general y contar compras únicas
$total_general = 0;
$compras_unicas = [];

foreach ($resultados as $row) {
    $subtotal = $row['subtotal'];
    $total = $subtotal * 1.13;
    $total_general += $total;

    // Contar compras únicas
    if (!in_array($row['id_Compra'], $compras_unicas)) {
        $compras_unicas[] = $row['id_Compra'];
    }
}

$total_compras = count($compras_unicas); // Total de compras únicas

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
    <title>Reporte de Compras | Ferretería Michapa Cuscatlán</title>

    <!-- Bootstrap + DataTables + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #2C3E50;
            /* CAMBIO: de #2563eb a #2C3E50 */
            --primary-dark: #1a252f;
            /* CAMBIO: versión más oscura */
            --primary-light: #34495e;
            /* CAMBIO: versión más clara */
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
            /* #2C3E50 */
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
            /* CAMBIO: color shadow */
        }

        .company-info h1 {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-dark);
            /* #1a252f */
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
            /* CAMBIO: color shadow */
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
            /* FORZAR texto blanco */
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
            /* #2C3E50 */
        }

        .total-row {
            font-weight: 600;
            background-color: #eff6ff !important;
        }

        .compra-group {
            background-color: #f0f9ff !important;
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
            <div class="company-info">
                <h1>FerreSys</h1>
                <p>Ferretería Michapa Cuscatlán</p>
            </div>

            <div class="report-title">
                <h2><i class="fas fa-file-invoice-dollar me-2"></i>Reporte de Compras por Proveedor</h2>
                <p>Resumen detallado de todas las compras realizadas a proveedores</p>
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
                    <span class="meta-label">Total de compras</span>
                    <span class="meta-value"><?= $total_compras ?></span>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0" style="color: var(--primary-dark);">
                    <i class="fas fa-list-alt me-2"></i>Detalle de Compras
                </h3>
                <button id="exportPDF" class="btn btn-pdf">
                    <i class="fas fa-file-pdf me-1"></i>Descargar PDF
                </button>
            </div>

            <div class="table-responsive">
                <table id="tablaComprasProveedor" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Código Compra</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                            <th>Total con IVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_general_web = 0;
                        $compra_actual = null;

                        foreach ($resultados as $row):
                            $subtotal = $row['subtotal'];
                            $total = $subtotal * 1.13;
                            $total_general_web += $total;

                            // Determinar si es una nueva compra
                            $es_nueva_compra = ($compra_actual !== $row['id_Compra']);
                            $compra_actual = $row['id_Compra'];
                        ?>
                            <tr class="<?= $es_nueva_compra ? 'compra-group' : '' ?>">
                                <td>
                                    <?php if ($es_nueva_compra): ?>
                                        <span class="badge bg-primary">CMP-<?= $row['id_Compra'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted"></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $es_nueva_compra ? htmlspecialchars($row['proveedor']) : '' ?></td>
                                <td><?= $es_nueva_compra ? date('d/m/Y', strtotime($row['fecha'])) : '' ?></td>
                                <td><?= htmlspecialchars($row['producto']) ?></td>
                                <td><?= number_format($row['cantidad'], 0, '.', ',') ?></td>
                                <td>$<?= formatoMoneda($row['precio_unitario']) ?></td>
                                <td>$<?= formatoMoneda($subtotal) ?></td>
                                <td><strong>$<?= formatoMoneda($total) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="7" class="text-end"><strong>Total General:</strong></td>
                            <td class="text-center"><strong>$<?= formatoMoneda($total_general_web) ?></strong></td>
                        </tr>
                    </tfoot>
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
            const tabla = $('#tablaComprasProveedor').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
                    // ✅ SOBREESCRIBIR el texto de información
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 compras",
                    infoFiltered: "(filtrado de _MAX_ registros totales)"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                order: [],
                // ✅ AGREGAR ESTO para usar el total de compras únicas
                drawCallback: function(settings) {
                    const api = this.api();
                    const pageInfo = api.page.info();

                    // Calcular registros mostrados en página actual
                    let start = pageInfo.start + 1;
                    let end = pageInfo.end;
                    let total = <?= $total_compras ?>; // 25 compras únicas

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
                const {
                    jsPDF
                } = window.jspdf;

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
                    const registrosPorPagina = 20;
                    const totalPaginas = Math.ceil(datos.length / registrosPorPagina);
                    const fechaGeneracion = new Date();
                    const horaGeneracion = formatearHora(fechaGeneracion);

                    // Anchuras de columnas optimizadas para landscape
                    const columnWidths = {
                        codigo: 25,
                        proveedor: 40,
                        fecha: 25,
                        producto: 50,
                        cantidad: 20,
                        precio: 30,
                        subtotal: 30,
                        total: 35
                    };

                    // Función para agregar encabezado (SOLO en la primera página)
                    function agregarEncabezado(pdf, esPrimeraPagina) {
                        if (!esPrimeraPagina) return;

                        // Fondo con el mismo color de la tabla
                        pdf.setFillColor(30, 41, 59);
                        pdf.rect(0, 0, pageWidth, 45, 'F');

                        // Título del reporte centrado
                        pdf.setFontSize(20);
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'bold');
                        pdf.text('FERRE SYS - REPORTE DE COMPRAS POR PROVEEDOR', pageWidth / 2, 18, {
                            align: 'center'
                        });

                        pdf.setFontSize(12);
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'normal');
                        pdf.text('Ferretería Michapa Cuscatlán - Resumen detallado de todas las compras realizadas', pageWidth / 2, 25, {
                            align: 'center'
                        });

                        // Línea decorativa NARANJA
                        pdf.setDrawColor(255, 140, 0);
                        pdf.setLineWidth(1.5);
                        pdf.line(20, 32, pageWidth - 20, 32);

                        // Información de metadata
                        const metadataY = 38;
                        pdf.setFontSize(11);
                        pdf.setTextColor(255, 255, 255);

                        pdf.text(`Fecha: ${formatearFecha(fechaGeneracion)}`, 20, metadataY);
                        pdf.text(`Hora: ${horaGeneracion}`, 20, metadataY + 5);
                        pdf.text(`Generado por: ${'<?= htmlspecialchars($empleado_generador) ?>'}`, pageWidth / 2, metadataY);
                        pdf.text(`Compras: ${formatoCantidad(<?= $total_compras ?>)}`, pageWidth / 2, metadataY + 5);
                        pdf.text(`Páginas: ${totalPaginas}`, pageWidth - 20, metadataY, {
                            align: 'right'
                        });
                        pdf.text(`Total General: $${formatoMoneda(<?= $total_general ?>)}`, pageWidth - 20, metadataY + 5, {
                            align: 'right'
                        });
                    }

                    // Función para agregar tabla
                    function agregarTabla(pdf, datosPagina, paginaActual, esPrimeraPagina) {
                        const startY = esPrimeraPagina ? 55 : 20;

                        // Encabezado de tabla - MANTENER ESTO (fondo azul):
                        pdf.setFillColor(30, 41, 59);
                        pdf.roundedRect(15, startY, pageWidth - 30, 8, 2, 2, 'F');

                        pdf.setFontSize(11);
                        pdf.setTextColor(255, 255, 255); // Texto blanco - MANTENER
                        pdf.setFont('helvetica', 'bold');

                        // Encabezados de columnas con anchuras específicas
                        let xPos = 17;
                        pdf.text('CÓDIGO', xPos, startY + 5.5);
                        xPos += columnWidths.codigo;

                        pdf.text('PROVEEDOR', xPos, startY + 5.5);
                        xPos += columnWidths.proveedor;

                        pdf.text('FECHA', xPos, startY + 5.5);
                        xPos += columnWidths.fecha;

                        pdf.text('PRODUCTO', xPos, startY + 5.5);
                        xPos += columnWidths.producto;

                        pdf.text('CANT', xPos, startY + 5.5);
                        xPos += columnWidths.cantidad;

                        pdf.text('P. UNITARIO', xPos, startY + 5.5);
                        xPos += columnWidths.precio;

                        pdf.text('SUBTOTAL', xPos, startY + 5.5);
                        xPos += columnWidths.subtotal;

                        pdf.text('TOTAL + IVA', xPos, startY + 5.5);

                        // Datos de la tabla
                        let yPos = startY + 16;
                        pdf.setFontSize(10);
                        pdf.setTextColor(30, 41, 59);
                        pdf.setFont('helvetica', 'normal');

                        let compraActual = null;

                        datosPagina.forEach((row, index) => {
                            // ✅ CORREGIDO: Verificar si necesitamos nueva página CON PIE EN TODAS
                            if (yPos > pageHeight - 30) {
                                agregarPiePagina(pdf, paginaActual, totalPaginas); // Pie en página actual
                                pdf.addPage();
                                paginaActual++;
                                yPos = 20;

                                // Redibujar encabezado de tabla en nueva página - SOLO BORDE
                                pdf.setDrawColor(30, 41, 59);
                                pdf.setLineWidth(0.5);
                                pdf.roundedRect(15, yPos - 8, pageWidth - 30, 8, 2, 2, 'S');
                                pdf.setTextColor(30, 41, 59);
                                pdf.setFont('helvetica', 'bold');
                                pdf.setFontSize(11);

                                xPos = 17;
                                pdf.text('CÓDIGO', xPos, yPos - 2.5);
                                xPos += columnWidths.codigo;
                                pdf.text('PROVEEDOR', xPos, yPos - 2.5);
                                xPos += columnWidths.proveedor;
                                pdf.text('FECHA', xPos, yPos - 2.5);
                                xPos += columnWidths.fecha;
                                pdf.text('PRODUCTO', xPos, yPos - 2.5);
                                xPos += columnWidths.producto;
                                pdf.text('CANT', xPos, yPos - 2.5);
                                xPos += columnWidths.cantidad;
                                pdf.text('P. UNITARIO', xPos, yPos - 2.5);
                                xPos += columnWidths.precio;
                                pdf.text('SUBTOTAL', xPos, yPos - 2.5);
                                xPos += columnWidths.subtotal;
                                pdf.text('TOTAL + IVA', xPos, yPos - 2.5);

                                yPos += 8;
                                pdf.setTextColor(30, 41, 59);
                                pdf.setFont('helvetica', 'normal');
                                pdf.setFontSize(10);
                            }

                            // Determinar si es una nueva compra
                            const esNuevaCompra = (compraActual !== row.id_Compra);
                            compraActual = row.id_Compra;
                            if (index % 2 === 0) {
                                pdf.setFillColor(250, 250, 250); // Gris clarito para filas pares
                                pdf.roundedRect(15, yPos - 4, pageWidth - 30, 5, 1, 1, 'F');
                            }
                            // Las filas impares quedan con fondo blanco (sin fill)
                            const subtotal = parseFloat(row.cantidad) * parseFloat(row.precio_unitario);
                            const total = subtotal * 1.13;

                            xPos = 17;

                            // Mostrar código solo en primera fila de cada compra
                            if (esNuevaCompra) {
                                pdf.text(`CMP-${row.id_Compra}`, xPos, yPos);
                            } else {
                                pdf.text('', xPos, yPos);
                            }
                            xPos += columnWidths.codigo;

                            // Mostrar proveedor solo en primera fila de cada compra
                            if (esNuevaCompra) {
                                const proveedor = row.proveedor.length > 20 ? row.proveedor.substring(0, 20) + '...' : row.proveedor;
                                pdf.text(proveedor, xPos, yPos);
                            }
                            xPos += columnWidths.proveedor;

                            // Mostrar fecha solo en primera fila de cada compra
                            if (esNuevaCompra) {
                                pdf.text(new Date(row.fecha).toLocaleDateString('es-SV'), xPos, yPos);
                            }
                            xPos += columnWidths.fecha;

                            const producto = row.producto.length > 25 ? row.producto.substring(0, 25) + '...' : row.producto;
                            pdf.text(producto, xPos, yPos);
                            xPos += columnWidths.producto;

                            // Formatear cantidad con comas
                            pdf.text(formatoCantidad(parseInt(row.cantidad)), xPos, yPos);
                            xPos += columnWidths.cantidad;

                            // Formatear precios con comas
                            pdf.text(`$${formatoMoneda(parseFloat(row.precio_unitario))}`, xPos, yPos);
                            xPos += columnWidths.precio;

                            pdf.text(`$${formatoMoneda(subtotal)}`, xPos, yPos);
                            xPos += columnWidths.subtotal;

                            pdf.setFont('helvetica', 'bold');
                            pdf.text(`$${formatoMoneda(total)}`, xPos, yPos);
                            pdf.setFont('helvetica', 'normal');

                            yPos += 5;
                        });

                        return {
                            yPos,
                            paginaActual
                        };
                    }

                    // Función para agregar total general (SOLO en la última página)
                    function agregarTotalGeneral(pdf, yPos, esUltimaPagina) {
                        if (!esUltimaPagina) return;

                        // Línea separadora
                        pdf.setDrawColor(30, 41, 59);
                        pdf.setLineWidth(1);
                        pdf.line(15, yPos + 5, pageWidth - 15, yPos + 5);

                        // Total general
                        pdf.setFontSize(15);
                        pdf.setFont('helvetica', 'bold');
                        pdf.setTextColor(30, 41, 59);
                        pdf.text(`TOTAL GENERAL: $${formatoMoneda(<?= $total_general ?>)}`, pageWidth - 20, yPos + 15, {
                            align: 'right'
                        });

                        return yPos + 20;
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

                    // ✅ CORREGIDO: Generar páginas CON PIE EN TODAS LAS PÁGINAS
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

                        // Agregar total general SOLO en la última página
                        const yPosDespuesTabla = agregarTotalGeneral(pdf, resultadoTabla.yPos, esUltimaPagina);

                        // ✅ CORREGIDO: Agregar pie de página en TODAS las páginas
                        agregarPiePagina(pdf, currentPage, totalPaginas);
                    }

                    // Descargar PDF con nombre específico
                    pdf.save('Reporte de compras por proveedor.pdf');

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