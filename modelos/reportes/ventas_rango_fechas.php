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

// ==========================
// Parámetros de filtro
// ==========================
$rango = $_GET['rango'] ?? 'diario';
$trimestre = $_GET['trimestre'] ?? 1;
$semestre  = $_GET['semestre'] ?? 1;
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin    = $_GET['fecha_fin'] ?? '';
$id_empleado_filtro = $_GET['id_empleado'] ?? 'todos';

// ==========================
// Obtener lista de empleados para el filtro
// ==========================
$sql_empleados = "SELECT id_Empleado, nombre, apellido FROM empleados ORDER BY nombre, apellido";
$stmt_empleados = $db->prepare($sql_empleados);
$stmt_empleados->execute();
$empleados = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// Fecha actual CORREGIDA - Usar fecha del servidor
// ==========================
date_default_timezone_set('America/El_Salvador');
$today = date('Y-m-d');
$current_year = date('Y');

// ==========================
// Cálculo automático de fechas según rango - CORREGIDO
// ==========================
if(!$fecha_inicio || !$fecha_fin){
    switch($rango){
        case 'diario':
            $fecha_inicio = $today;
            $fecha_fin = $today;
            break;
        case 'mes':
            $fecha_inicio = date('Y-m-01');
            $fecha_fin = date('Y-m-t');
            break;
        case 'trimestre':
            $mes_inicio = ($trimestre-1)*3 + 1;
            $mes_fin = $mes_inicio + 2;
            $fecha_inicio = date('Y-m-01', strtotime($current_year . '-' . str_pad($mes_inicio, 2, '0', STR_PAD_LEFT) . '-01'));
            $fecha_fin = date('Y-m-t', strtotime($current_year . '-' . str_pad($mes_fin, 2, '0', STR_PAD_LEFT) . '-01'));
            break;
        case 'semestre':
            $mes_inicio = ($semestre-1)*6 + 1;
            $mes_fin = $mes_inicio + 5;
            $fecha_inicio = date('Y-m-01', strtotime($current_year . '-' . str_pad($mes_inicio, 2, '0', STR_PAD_LEFT) . '-01'));
            $fecha_fin = date('Y-m-t', strtotime($current_year . '-' . str_pad($mes_fin, 2, '0', STR_PAD_LEFT) . '-01'));
            break;
        case 'anual':
            $fecha_inicio = date('Y-01-01');
            $fecha_fin = date('Y-12-31');
            break;
    }
}

// ==========================
// Consulta CORREGIDA - Usar DATE() para comparar solo la fecha
// ==========================
$sql = "SELECT 
            CONCAT('VTA-', v.id_Venta) AS codigo_venta,
            v.fecha,
            CONCAT(e.nombre,' ',e.apellido) AS empleado,
            e.id_Empleado,
            CONCAT(c.nombre,' ',c.apellido) AS cliente,
            SUM(dv.cantidad * dv.precio_venta) AS total_venta
        FROM ventas v
        INNER JOIN detalle_venta dv ON v.id_Venta = dv.id_Venta
        INNER JOIN empleados e ON v.id_Empleado = e.id_Empleado
        LEFT JOIN clientes c ON v.id_Cliente = c.id_Cliente
        WHERE DATE(v.fecha) BETWEEN :fecha_inicio AND :fecha_fin";

// Agregar filtro por empleado si no es "todos"
if ($id_empleado_filtro !== 'todos') {
    $sql .= " AND v.id_Empleado = :id_empleado_filtro";
}

$sql .= " GROUP BY v.id_Venta, v.fecha, empleado, cliente, e.id_Empleado
          ORDER BY v.fecha ASC, v.id_Venta ASC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);
if ($id_empleado_filtro !== 'todos') {
    $stmt->bindParam(':id_empleado_filtro', $id_empleado_filtro);
}
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total general
$total_general = 0;
$total_ventas = count($resultados);
foreach ($resultados as $row) {
    $total_general += $row['total_venta'];
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
    <title>Reporte de Ventas | Ferretería Michapa Cuscatlán</title>

    <!-- Bootstrap + DataTables + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Tus estilos CSS aquí (se mantienen igual) */
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

        .total-row {
            font-weight: 600;
            background-color: #eff6ff !important;
        }

        .filters-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            margin-top: 25px;
            border-left: 5px solid var(--secondary);
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-group label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #e2e8f0;
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
                <h2><i class="fas fa-chart-line me-2"></i>Reporte de Ventas por Rango</h2>
                <p>Resumen detallado de todas las ventas realizadas en el período seleccionado</p>
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
                    <span class="meta-label">Total de ventas</span>
                    <span class="meta-value"><?= $total_ventas ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Período</span>
                    <span class="meta-value"><?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Total General</span>
                    <span class="meta-value">$<?= formatoMoneda($total_general) ?></span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-container">
            <h4 class="mb-4" style="color: var(--primary-dark);">
                <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
            </h4>
            <form id="filtrosForm" method="GET">
                <div class="row">
                    <div class="col-md-2 filter-group">
                        <label>Rango:</label>
                        <select id="rango" name="rango" class="form-select">
                            <option value="diario" <?= $rango=='diario'?'selected':'' ?>>Diario</option>
                            <option value="mes" <?= $rango=='mes'?'selected':'' ?>>Mensual</option>
                            <option value="trimestre" <?= $rango=='trimestre'?'selected':'' ?>>Trimestral</option>
                            <option value="semestre" <?= $rango=='semestre'?'selected':'' ?>>Semestral</option>
                            <option value="anual" <?= $rango=='anual'?'selected':'' ?>>Anual</option>
                        </select>
                    </div>
                    <div class="col-md-2 filter-group" id="selectorTrimestre" style="<?= $rango=='trimestre'?'':'display:none;' ?>">
                        <label>Trimestre:</label>
                        <select id="trimestre" name="trimestre" class="form-select">
                            <?php for($i=1;$i<=4;$i++): ?>
                                <option value="<?= $i ?>" <?= $trimestre==$i?'selected':'' ?>>Trimestre <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 filter-group" id="selectorSemestre" style="<?= $rango=='semestre'?'':'display:none;' ?>">
                        <label>Semestre:</label>
                        <select id="semestre" name="semestre" class="form-select">
                            <option value="1" <?= $semestre==1?'selected':'' ?>>Semestre 1</option>
                            <option value="2" <?= $semestre==2?'selected':'' ?>>Semestre 2</option>
                        </select>
                    </div>
                    <div class="col-md-2 filter-group">
                        <label>Empleado:</label>
                        <select id="id_empleado" name="id_empleado" class="form-select">
                            <option value="todos" <?= $id_empleado_filtro=='todos'?'selected':'' ?>>Todos los empleados</option>
                            <?php foreach($empleados as $emp): ?>
                                <option value="<?= $emp['id_Empleado'] ?>" <?= $id_empleado_filtro==$emp['id_Empleado']?'selected':'' ?>>
                                    <?= htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 filter-group">
                        <label>Fecha Inicio:</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                    </div>
                    <div class="col-md-2 filter-group">
                        <label>Fecha Fin:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end filter-group">
                        <button type="submit" id="btnFiltrar" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Contenido -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0" style="color: var(--primary-dark);">
                    <i class="fas fa-list-alt me-2"></i>Detalle de Ventas
                </h3>
                <?php if($total_ventas > 0): ?>
                <button id="exportPDF" class="btn btn-pdf">
                    <i class="fas fa-file-pdf me-1"></i>Descargar PDF
                </button>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <?php if($total_ventas > 0): ?>
                <table id="tablaVentas" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Código Venta</th>
                            <th>Fecha</th>
                            <th>Empleado</th>
                            <th>Cliente</th>
                            <th>Total Venta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($resultados as $row): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary"><?= htmlspecialchars($row['codigo_venta']) ?></span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($row['fecha'])) ?></td>
                            <td><?= htmlspecialchars($row['empleado']) ?></td>
                            <td><?= htmlspecialchars($row['cliente'] ?? 'N/A') ?></td>
                            <td><strong>$<?= formatoMoneda($row['total_venta']) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4" class="text-end"><strong>Total General:</strong></td>
                            <td class="text-center"><strong>$<?= formatoMoneda($total_general) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-search"></i>
                    <h4>No se encontraron ventas</h4>
                    <p>No hay ventas registradas en el período seleccionado (<?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?>)</p>
                </div>
                <?php endif; ?>
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

            // Inicializar DataTable si hay datos
            <?php if($total_ventas > 0): ?>
            const tabla = $('#tablaVentas').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                order: []
            });
            <?php endif; ?>

            // Mostrar/ocultar selectores de trimestre y semestre
            $('#rango').change(function(){
                const val = $(this).val();
                $('#selectorTrimestre').toggle(val=='trimestre');
                $('#selectorSemestre').toggle(val=='semestre');
                
                // Auto-calcular fechas cuando cambia el rango - CORREGIDO
                autoCalcularFechas(val);
            });

            // Auto-calcular fechas cuando cambian trimestre/semestre
            $('#trimestre, #semestre').change(function(){
                const rango = $('#rango').val();
                autoCalcularFechas(rango);
            });

            function autoCalcularFechas(rango) {
                const today = new Date();
                
                // Ajustar a la zona horaria de El Salvador
                const offset = -6; // UTC-6 para El Salvador
                today.setHours(today.getHours() + offset);
                
                let fechaInicio = '';
                let fechaFin = '';

                switch(rango) {
                    case 'diario':
                        fechaInicio = today.toISOString().split('T')[0];
                        fechaFin = today.toISOString().split('T')[0];
                        break;
                    case 'mes':
                        const primerDiaMes = new Date(today.getFullYear(), today.getMonth(), 1);
                        const ultimoDiaMes = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        fechaInicio = primerDiaMes.toISOString().split('T')[0];
                        fechaFin = ultimoDiaMes.toISOString().split('T')[0];
                        break;
                    case 'trimestre':
                        const trimestre = $('#trimestre').val();
                        const mesInicioTrim = (trimestre - 1) * 3;
                        const mesFinTrim = mesInicioTrim + 2;
                        fechaInicio = new Date(today.getFullYear(), mesInicioTrim, 1).toISOString().split('T')[0];
                        fechaFin = new Date(today.getFullYear(), mesFinTrim + 1, 0).toISOString().split('T')[0];
                        break;
                    case 'semestre':
                        const semestre = $('#semestre').val();
                        const mesInicioSem = (semestre - 1) * 6;
                        const mesFinSem = mesInicioSem + 5;
                        fechaInicio = new Date(today.getFullYear(), mesInicioSem, 1).toISOString().split('T')[0];
                        fechaFin = new Date(today.getFullYear(), mesFinSem + 1, 0).toISOString().split('T')[0];
                        break;
                    case 'anual':
                        fechaInicio = today.getFullYear() + '-01-01';
                        fechaFin = today.getFullYear() + '-12-31';
                        break;
                }

                $('#fecha_inicio').val(fechaInicio);
                $('#fecha_fin').val(fechaFin);
            }

            // Función para generar PDF - SIN DIVISIÓN DE NOMBRES
            <?php if($total_ventas > 0): ?>
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
                    // Crear nuevo PDF en modo horizontal (landscape) para más espacio
                    const pdf = new jsPDF('l', 'mm', 'letter');
                    const pageWidth = pdf.internal.pageSize.getWidth();
                    const pageHeight = pdf.internal.pageSize.getHeight();

                    // Datos para el PDF
                    const datos = <?= json_encode($resultados) ?>;
                    const registrosPorPagina = 12; // Aumentado ya que no dividimos nombres
                    const totalPaginas = Math.ceil(datos.length / registrosPorPagina);
                    const fechaGeneracion = new Date();
                    const horaGeneracion = formatearHora(fechaGeneracion);

                    // Obtener nombre del empleado filtrado
                    const empleadoFiltro = document.getElementById('id_empleado');
                    const empleadoSeleccionado = empleadoFiltro.options[empleadoFiltro.selectedIndex].text;

                    // Función para formatear fecha en el PDF (CORREGIDA)
                    function formatearFechaPDF(fechaStr) {
                        const fecha = new Date(fechaStr);
                        // Ajustar por diferencia de zona horaria si es necesario
                        const offset = fecha.getTimezoneOffset();
                        fecha.setMinutes(fecha.getMinutes() + offset);
                        return fecha.toLocaleDateString('es-SV');
                    }

                    // Función para agregar encabezado - CON FUENTES MÁS GRANDES
                    function agregarEncabezado(pdf, esPrimeraPagina) {
                        if (!esPrimeraPagina) return;

                        // Logo y título - encabezado como antes
                        pdf.setFillColor(44, 62, 80);
                        pdf.rect(0, 0, pageWidth, 40, 'F'); // Aumentado para fuentes más grandes

                        // Título principal - FUENTE MÁS GRANDE
                        pdf.setFontSize(18); // Aumentado de 16 a 18
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'bold');
                        pdf.text('FERRE SYS - REPORTE DE VENTAS', pageWidth / 2, 15, {
                            align: 'center'
                        });

                        pdf.setFontSize(12); // Aumentado de 10 a 12
                        pdf.text('Ferretería Michapa Cuscatlán', pageWidth / 2, 22, {
                            align: 'center'
                        });

                        // Línea decorativa
                        pdf.setDrawColor(245, 158, 11);
                        pdf.setLineWidth(1);
                        pdf.line(20, 27, pageWidth - 20, 27);

                        // Información del reporte - FUENTES MÁS GRANDES
                        const infoY = 34;
                        pdf.setFontSize(10); // Aumentado de 9 a 10
                        pdf.setTextColor(255, 255, 255);

                        // Izquierda
                        pdf.text(`Fecha: ${formatearFecha(fechaGeneracion)}`, 20, infoY);
                        pdf.text(`Hora: ${horaGeneracion}`, 20, infoY + 5);
                        
                        // Centro
                        pdf.text(`Generado por: ${'<?= htmlspecialchars($empleado_generador) ?>'}`, pageWidth / 2, infoY);
                        pdf.text(`Empleado: ${empleadoSeleccionado}`, pageWidth / 2, infoY + 5);
                        
                        // Derecha
                        pdf.text(`Período: <?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?>`, pageWidth - 20, infoY, {
                            align: 'right'
                        });
                        pdf.text(`Ventas: ${datos.length} | Total: $${formatoMoneda(<?= $total_general ?>)}`, pageWidth - 20, infoY + 5, {
                            align: 'right'
                        });
                    }

                    // Función para agregar tabla - SIN DIVISIÓN DE NOMBRES
                    function agregarTabla(pdf, datosPagina, paginaActual, esPrimeraPagina) {
                        const startY = esPrimeraPagina ? 50 : 20; // Aumentado por encabezado más grande

                        // Encabezado de tabla
                        pdf.setFillColor(30, 41, 59);
                        pdf.rect(15, startY, pageWidth - 30, 10, 'F'); // Aumentado para fuentes más grandes

                        // Fuentes más grandes para toda la tabla
                        pdf.setFontSize(12); // Aumentado de 10 a 12
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFont('helvetica', 'bold');

                        // Encabezados de columnas - MEJOR DISTRIBUCIÓN
                        const columnas = [
                            { texto: 'CÓDIGO', x: 18, ancho: 30 },
                            { texto: 'FECHA', x: 52, ancho: 25 },
                            { texto: 'EMPLEADO', x: 82, ancho: 50 },
                            { texto: 'CLIENTE', x: 137, ancho: 80 }, // MÁS ESPACIO PARA CLIENTE
                            { texto: 'TOTAL', x: 222, ancho: 30 }
                        ];

                        columnas.forEach(col => {
                            pdf.text(col.texto, col.x, startY + 6); // Ajustado posición vertical
                        });

                        // Datos de la tabla
                        let yPos = startY + 18; // Aumentado espacio entre encabezado y datos
                        pdf.setFontSize(11); // Aumentado de 9 a 11
                        pdf.setTextColor(30, 41, 59);
                        pdf.setFont('helvetica', 'normal');

                        datosPagina.forEach((row, index) => {
                            if (yPos > pageHeight - 25) {
                                agregarPiePagina(pdf, paginaActual, totalPaginas);
                                pdf.addPage();
                                paginaActual++;
                                yPos = 20;

                                // Redibujar encabezado de tabla en nueva página
                                pdf.setFillColor(30, 41, 59);
                                pdf.rect(15, yPos - 5, pageWidth - 30, 10, 'F');
                                pdf.setTextColor(255, 255, 255);
                                pdf.setFont('helvetica', 'bold');
                                pdf.setFontSize(12);

                                columnas.forEach(col => {
                                    pdf.text(col.texto, col.x, yPos + 3);
                                });

                                yPos += 12;
                                pdf.setTextColor(30, 41, 59);
                                pdf.setFont('helvetica', 'normal');
                                pdf.setFontSize(11);
                            }

                            // Fondo alternado para filas
                            if (index % 2 === 0) {
                                pdf.setFillColor(248, 250, 252);
                                pdf.rect(15, yPos - 5, pageWidth - 30, 8, 'F'); // Aumentado altura
                            }

                            // Datos de cada columna - SIN DIVIDIR NOMBRES
                            pdf.text(row.codigo_venta, 18, yPos);
                            pdf.text(formatearFechaPDF(row.fecha), 52, yPos);
                            
                            // Empleado - UNA SOLA LÍNEA
                            const empleado = row.empleado;
                            // Si el nombre es muy largo, usar puntos suspensivos
                            const empleadoMostrar = empleado.length > 30 ? empleado.substring(0, 30) + '...' : empleado;
                            pdf.text(empleadoMostrar, 82, yPos);
                            
                            // Cliente - UNA SOLA LÍNEA, SIN DIVISIÓN
                            const cliente = (row.cliente && row.cliente !== 'N/A') ? row.cliente : 'N/A';
                            // Si el nombre es muy largo, usar puntos suspensivos
                            const clienteMostrar = cliente.length > 40 ? cliente.substring(0, 40) + '...' : cliente;
                            pdf.text(clienteMostrar, 137, yPos);
                            
                            // Total - UNA SOLA LÍNEA
                            pdf.setFont('helvetica', 'bold');
                            pdf.text(`$${formatoMoneda(parseFloat(row.total_venta))}`, 222, yPos);
                            pdf.setFont('helvetica', 'normal');

                            yPos += 8; // Espacio fijo entre filas
                        });

                        return {
                            yPos,
                            paginaActual
                        };
                    }

                    // Función para agregar total general - CON FUENTE MÁS GRANDE
                    function agregarTotalGeneral(pdf, yPos, esUltimaPagina) {
                        if (!esUltimaPagina) return;

                        // Línea separadora
                        pdf.setDrawColor(30, 41, 59);
                        pdf.setLineWidth(0.5);
                        pdf.line(15, yPos + 5, pageWidth - 15, yPos + 5);

                        // Total general con fuente más grande
                        pdf.setFontSize(14); // Aumentado de 12 a 14
                        pdf.setFont('helvetica', 'bold');
                        pdf.setTextColor(30, 41, 59);
                        pdf.text(`TOTAL GENERAL: $${formatoMoneda(<?= $total_general ?>)}`, pageWidth - 20, yPos + 12, {
                            align: 'right'
                        });

                        return yPos + 15;
                    }

                    // Función para agregar pie de página - CON FUENTE MÁS GRANDE
                    function agregarPiePagina(pdf, paginaActual, totalPaginas) {
                        pdf.setFontSize(10); // Aumentado de 9 a 10
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
                        agregarTotalGeneral(pdf, resultadoTabla.yPos, esUltimaPagina);
                        agregarPiePagina(pdf, currentPage, totalPaginas);
                    }

                    // Descargar PDF con nombre específico
                    const nombreArchivo = `Reporte_ventas_${empleadoSeleccionado.replace(/ /g, '_')}_<?= date('Y-m-d') ?>.pdf`;
                    pdf.save(nombreArchivo);

                } catch (error) {
                    console.error('Error al generar PDF:', error);
                    alert('Error al generar el PDF. Intente nuevamente.');
                } finally {
                    // Restaurar botón
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }
            <?php endif; ?>
        });
    </script>

</body>

</html>