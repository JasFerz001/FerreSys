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
// Corregir fecha de hoy correctamente
// ==========================
date_default_timezone_set('America/El_Salvador'); // Zona horaria
$today = date('Y-m-d'); // Fecha actual confiable

// Si no hay fechas manuales, se calculan según rango
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
// Consulta principal
// ==========================
$sql = "SELECT 
            v.id_Venta, v.fecha, 
            CONCAT(e.nombre,' ',e.apellido) AS empleado,
            CONCAT(c.nombre,' ',c.apellido) AS cliente,
            p.nombre AS producto,
            dv.cantidad, dv.precio_venta,
            (dv.cantidad * dv.precio_venta) AS total
        FROM ventas v
        INNER JOIN detalle_venta dv ON v.id_Venta = dv.id_Venta
        INNER JOIN detalle_compra dc ON dv.id_Detallecompra = dc.id_Detallecompra
        INNER JOIN producto p ON dc.id_Producto = p.id_Producto
        INNER JOIN empleados e ON v.id_Empleado = e.id_Empleado
        LEFT JOIN clientes c ON v.id_Cliente = c.id_Cliente
        WHERE DATE(v.fecha) BETWEEN :fecha_inicio AND :fecha_fin
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

    <div class="table-wrapper">
        <div class="table-responsive">
            <table id="tablaVentas" class="table table-striped table-hover align-middle text-center">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Empleado</th>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($resultados as $row): ?>
                    <tr>
                        <td><?= $row['id_Venta'] ?></td>
                        <td><?= $row['fecha'] ?></td>
                        <td><?= htmlspecialchars($row['empleado']) ?></td>
                        <td><?= htmlspecialchars($row['cliente'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['producto']) ?></td>
                        <td><?= $row['cantidad'] ?></td>
                        <td>$<?= number_format($row['precio_venta'],2) ?></td>
                        <td>$<?= number_format($row['total'],2) ?></td>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
$(document).ready(function(){
    $('#tablaVentas').DataTable({
        pageLength:10,
        lengthMenu:[10,25,50],
        language:{ url:"https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Mostrar/ocultar select de trimestre o semestre
    $('#rango').change(function(){
        const val = $(this).val();
        if(val=='trimestre') $('#selectorTrimestre').show(); else $('#selectorTrimestre').hide();
        if(val=='semestre') $('#selectorSemestre').show(); else $('#selectorSemestre').hide();
        ajustarFechas();
    });
    $('#trimestre, #semestre').change(ajustarFechas);

    // Botón filtrar
    $('#btnFiltrar').click(function(){
        const rango = $('#rango').val();
        const trimestre = $('#trimestre').val();
        const semestre = $('#semestre').val();
        const fecha_inicio = $('#fecha_inicio').val();
        const fecha_fin = $('#fecha_fin').val();
        if(!fecha_inicio || !fecha_fin){
            alert('Selecciona ambas fechas');
            return;
        }
        window.location.href = '?rango='+rango+'&trimestre='+trimestre+'&semestre='+semestre+'&fecha_inicio='+fecha_inicio+'&fecha_fin='+fecha_fin;
    });

    // =============================
    // Ajusta fechas automáticamente según rango usando la fecha local de El Salvador
    // =============================
    function ajustarFechas(){
        const phpToday = '<?= $today ?>'; // PHP ya da la fecha correcta
        const y = parseInt(phpToday.split('-')[0]);
        const m = parseInt(phpToday.split('-')[1]) - 1;
        const d = parseInt(phpToday.split('-')[2]);

        const rango = $('#rango').val();
        if(rango=='diario'){
            $('#fecha_inicio').val(phpToday);
            $('#fecha_fin').val(phpToday);
        } else if(rango=='mes'){
            const first = new Date(y, m,1).toISOString().split('T')[0];
            const last = new Date(y, m+1,0).toISOString().split('T')[0];
            $('#fecha_inicio').val(first);
            $('#fecha_fin').val(last);
        } else if(rango=='trimestre'){
            const t = parseInt($('#trimestre').val());
            const startMonth = (t-1)*3;
            const endMonth = startMonth +2;
            const first = new Date(y,startMonth,1).toISOString().split('T')[0];
            const last = new Date(y,endMonth+1,0).toISOString().split('T')[0];
            $('#fecha_inicio').val(first);
            $('#fecha_fin').val(last);
        } else if(rango=='semestre'){
            const s = parseInt($('#semestre').val());
            const startMonth = (s-1)*6;
            const endMonth = startMonth +5;
            const first = new Date(y,startMonth,1).toISOString().split('T')[0];
            const last = new Date(y,endMonth+1,0).toISOString().split('T')[0];
            $('#fecha_inicio').val(first);
            $('#fecha_fin').val(last);
        } else if(rango=='anual'){
            $('#fecha_inicio').val(y+'-01-01');
            $('#fecha_fin').val(y+'-12-31');
        }
    }

    ajustarFechas(); // al cargar

    // Export PDF
    document.getElementById('exportPDF').addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l','pt','a4');
        doc.setFontSize(18); doc.setTextColor(13,110,253);
        doc.text("Reporte de Ventas", doc.internal.pageSize.getWidth()/2,40,{align:'center'});
        doc.setFontSize(12); doc.setTextColor(73,80,87);
        doc.text("Ferretería Michapa, Cuscatlán", doc.internal.pageSize.getWidth()/2,60,{align:'center'});

        const headers=[["ID Venta","Fecha","Empleado","Cliente","Producto","Cantidad","Precio Unitario","Total"]];
        const body=[];
        <?php foreach($resultados as $row): ?>
        body.push([
            "<?= $row['id_Venta'] ?>",
            "<?= $row['fecha'] ?>",
            "<?= htmlspecialchars($row['empleado']) ?>",
            "<?= htmlspecialchars($row['cliente'] ?? 'N/A') ?>",
            "<?= htmlspecialchars($row['producto']) ?>",
            "<?= $row['cantidad'] ?>",
            "$<?= number_format($row['precio_venta'],2) ?>",
            "$<?= number_format($row['total'],2) ?>"
        ]);
        <?php endforeach; ?>
        doc.autoTable({startY:80,head:headers,body:body,theme:'grid',headStyles:{fillColor:[13,110,253],textColor:255},styles:{fontSize:9,halign:'center',valign:'middle'},margin:{top:80,left:20,right:20}});
        doc.save("Reporte_Ventas.pdf");
    });
});
</script>
</body>
</html>
