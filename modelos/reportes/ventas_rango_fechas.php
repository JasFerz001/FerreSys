<?php
require_once '../../conexion/conexion.php';

$conexion = new Conexion();
$db = $conexion->getConnection();

// ==========================
// Parámetros de filtro
// ==========================
$rango = $_GET['rango'] ?? 'diario';
$trimestre = $_GET['trimestre'] ?? 1;
$semestre  = $_GET['semestre'] ?? 1;
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin    = $_GET['fecha_fin'] ?? '';

// ==========================
// Fecha actual corregida
// ==========================
date_default_timezone_set('America/El_Salvador');
$today = date('Y-m-d');

// ==========================
// Cálculo automático de fechas según rango
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
            $fecha_inicio = date('Y-m-d', strtotime(date('Y').'-'.$mes_inicio.'-01'));
            $fecha_fin = date('Y-m-t', strtotime(date('Y').'-'.$mes_fin.'-01'));
            break;
        case 'semestre':
            $mes_inicio = ($semestre-1)*6 + 1;
            $mes_fin = $mes_inicio + 5;
            $fecha_inicio = date('Y-m-d', strtotime(date('Y').'-'.$mes_inicio.'-01'));
            $fecha_fin = date('Y-m-t', strtotime(date('Y').'-'.$mes_fin.'-01'));
            break;
        case 'anual':
            $fecha_inicio = date('Y-01-01');
            $fecha_fin = date('Y-12-31');
            break;
    }
}

// ==========================
// Consulta modificada: total por venta con prefijo VTA-
// ==========================
$sql = "SELECT 
            CONCAT('VTA-', v.id_Venta) AS codigo_venta,
            v.fecha,
            CONCAT(e.nombre,' ',e.apellido) AS empleado,
            CONCAT(c.nombre,' ',c.apellido) AS cliente,
            SUM(dv.cantidad * dv.precio_venta) AS total_venta
        FROM ventas v
        INNER JOIN detalle_venta dv ON v.id_Venta = dv.id_Venta
        INNER JOIN empleados e ON v.id_Empleado = e.id_Empleado
        LEFT JOIN clientes c ON v.id_Cliente = c.id_Cliente
        WHERE DATE(v.fecha) BETWEEN :fecha_inicio AND :fecha_fin
        GROUP BY v.id_Venta, v.fecha, empleado, cliente
        ORDER BY v.fecha ASC, v.id_Venta ASC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas por Rango de Fechas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body {background-color:#f5f6fa; font-family:'Segoe UI',sans-serif; color:#212529;}
.page-header{text-align:center;margin-top:30px;margin-bottom:10px;}
.page-header h2{font-weight:700;color:#0d6efd;}
.page-header .subtitle{font-size:1rem;color:#6c757d;}
.export-bar{display:flex;justify-content:flex-end;align-items:center;margin:25px 0 15px 0;}
#exportPDF{background-color:#0d6efd;color:white;border:none;font-weight:500;border-radius:6px;padding:10px 18px;transition:all 0.2s ease-in-out;}
#exportPDF:hover{background-color:#0b5ed7;transform:translateY(-2px);}
.table-wrapper{background-color:#fff;border:1px solid #dee2e6;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
table.dataTable thead{background-color:#0d6efd;color:white;}
table.dataTable tbody tr:nth-child(odd){background-color:#f9fafb;}
table.dataTable tbody tr:hover{background-color:#e2e6ea;}
</style>
</head>
<body>
<div class="container-fluid px-4">
    <div class="page-header">
        <h2><i class="fas fa-chart-line"></i> Ventas por Rango</h2>
        <div class="subtitle"><i class="fas fa-store text-primary"></i> Ferretería Michapa Cuscatlán</div>
    </div>

    <!-- Filtros -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label>Rango:</label>
            <select id="rango" class="form-select">
                <option value="diario" <?= $rango=='diario'?'selected':'' ?>>Diario</option>
                <option value="mes" <?= $rango=='mes'?'selected':'' ?>>Mensual</option>
                <option value="trimestre" <?= $rango=='trimestre'?'selected':'' ?>>Trimestral</option>
                <option value="semestre" <?= $rango=='semestre'?'selected':'' ?>>Semestral</option>
                <option value="anual" <?= $rango=='anual'?'selected':'' ?>>Anual</option>
            </select>
        </div>
        <div class="col-md-2" id="selectorTrimestre" style="<?= $rango=='trimestre'?'':'display:none;' ?>">
            <label>Trimestre:</label>
            <select id="trimestre" class="form-select">
                <?php for($i=1;$i<=4;$i++): ?>
                    <option value="<?= $i ?>" <?= $trimestre==$i?'selected':'' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2" id="selectorSemestre" style="<?= $rango=='semestre'?'':'display:none;' ?>">
            <label>Semestre:</label>
            <select id="semestre" class="form-select">
                <option value="1" <?= $semestre==1?'selected':'' ?>>1</option>
                <option value="2" <?= $semestre==2?'selected':'' ?>>2</option>
            </select>
        </div>
        <div class="col-md-2">
            <label>Inicio:</label>
            <input type="date" id="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
        </div>
        <div class="col-md-2">
            <label>Fin:</label>
            <input type="date" id="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button id="btnFiltrar" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
    </div>

    <div class="export-bar mb-3">
        <button id="exportPDF" class="btn btn-danger"><i class="fas fa-file-pdf me-2"></i>Exportar PDF</button>
    </div>

    <!-- Tabla -->
    <div class="table-wrapper">
        <div class="table-responsive">
            <table id="tablaVentas" class="table table-striped table-hover align-middle text-center">
                <thead>
                    <tr>
                        <th>Código de Venta</th>
                        <th>Fecha</th>
                        <th>Empleado</th>
                        <th>Cliente</th>
                        <th>Total de Venta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($resultados as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['codigo_venta']) ?></td>
                        <td><?= $row['fecha'] ?></td>
                        <td><?= htmlspecialchars($row['empleado']) ?></td>
                        <td><?= htmlspecialchars($row['cliente'] ?? 'N/A') ?></td>
                        <td><strong>$<?= number_format($row['total_venta'],2) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
$(document).ready(function(){
    $('#tablaVentas').DataTable({
        pageLength:10,
        language:{ url:"https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Filtros
    $('#rango').change(function(){
        const val = $(this).val();
        $('#selectorTrimestre').toggle(val=='trimestre');
        $('#selectorSemestre').toggle(val=='semestre');
    });

    $('#btnFiltrar').click(function(){
        const rango = $('#rango').val();
        const trimestre = $('#trimestre').val();
        const semestre = $('#semestre').val();
        const fi = $('#fecha_inicio').val();
        const ff = $('#fecha_fin').val();
        if(!fi || !ff){ alert('Selecciona ambas fechas'); return; }
        window.location.href = '?rango='+rango+'&trimestre='+trimestre+'&semestre='+semestre+'&fecha_inicio='+fi+'&fecha_fin='+ff;
    });

    // Exportar PDF
    document.getElementById('exportPDF').addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l','pt','a4');
        doc.setFontSize(18); doc.setTextColor(13,110,253);
        doc.text("Reporte de Ventas", doc.internal.pageSize.getWidth()/2,40,{align:'center'});
        doc.setFontSize(12); doc.setTextColor(73,80,87);
        doc.text("Ferretería Michapa, Cuscatlán", doc.internal.pageSize.getWidth()/2,60,{align:'center'});

        const headers=[["Código de Venta","Fecha","Empleado","Cliente","Total"]];
        const body=[];
        <?php foreach($resultados as $row): ?>
        body.push([
            "<?= htmlspecialchars($row['codigo_venta']) ?>",
            "<?= $row['fecha'] ?>",
            "<?= htmlspecialchars($row['empleado']) ?>",
            "<?= htmlspecialchars($row['cliente'] ?? 'N/A') ?>",
            "$<?= number_format($row['total_venta'],2) ?>"
        ]);
        <?php endforeach; ?>

        doc.autoTable({startY:80,head:headers,body:body,theme:'grid',
            headStyles:{fillColor:[13,110,253],textColor:255},
            styles:{fontSize:9,halign:'center'}});
        doc.save("Reporte_Ventas.pdf");
    });
});
</script>
</body>
</html>
