<?php
// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}

include_once '../../conexion/conexion.php';
include_once '../ventas/ventas.php';
include_once '../ventas/detalle_venta.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$venta = new Ventas($db);
$detalleVenta = new Detalle_venta($db);

// Obtener listado de ventas
$ventasResult = $venta->obtenerListadoVentas();
$ventasArray = $ventasResult->fetchAll(PDO::FETCH_ASSOC);

// Manejo de solicitudes AJAX para detalles
if (isset($_GET['ajax']) && $_GET['ajax'] == '1' && isset($_GET['id_venta'])) {
    $id_Venta = $_GET['id_venta'];
    $detalleData = $detalleVenta->obtenerDetalledeVenta($id_Venta);

    // Formatear la fecha tal como viene de la base (solo día-mes-año)
    if (!empty($detalleData)) {
        foreach ($detalleData as &$detalle) {
            if (!empty($detalle['Fecha'])) {
                try {
                    $fechaObj = new DateTime($detalle['Fecha']);
                    $detalle['Fecha'] = $fechaObj->format('d/m/Y'); // formato día/mes/año
                } catch (Exception $e) {
                    // fallback: dejar la fecha cruda
                }
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($detalleData);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Ventas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/hCompras.css">
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> Historial de Ventas</h2>
            </div>
            <div class="card-body">
                <table id="ventasTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Código Venta</th>
                            <th>Fecha</th>
                            <th>Empleado</th>
                            <th>Cliente</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="ventasBody">
                        <?php if (count($ventasArray) > 0): ?>
                            <?php foreach ($ventasArray as $venta): ?>
                                <tr>
                                    <td>VTA-<?php echo htmlspecialchars($venta['CodigoVenta']); ?></td>
                                    <td>
                                        <?php
                                        // Formatear la fecha tal como viene de la base
                                        $fechaSalida = '';
                                        if (!empty($venta['Fecha'])) {
                                            try {
                                                $fechaObj = new DateTime($venta['Fecha']);
                                                $fechaSalida = $fechaObj->format('d/m/Y'); // solo día/mes/año
                                            } catch (Exception $e) {
                                                $fechaSalida = htmlspecialchars($venta['Fecha']); // fallback
                                            }
                                        }
                                        echo $fechaSalida;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($venta['Empleado']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Cliente']); ?></td>
                                    <td>
                                        <button class="btn-modal view-detail" data-id="<?php echo $venta['CodigoVenta']; ?>">
                                            <i class="fas fa-eye"></i> Ver Detalle
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">
                                    No se encontraron ventas registradas.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de detalle -->
        <div id="detailModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-file-invoice"></i> Detalle de Venta</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body" id="modalBody">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Cargando detalles...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            const filas = $('#ventasTable tbody tr');
            const tieneRegistros = filas.length > 0 && filas.find('td[colspan]').length === 0;

            if (!tieneRegistros) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin registros',
                    text: 'Aún no se han realizado ventas.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            $('#ventasTable').DataTable({
                "language": { 
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" 
                },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
                "order": [[1, 'desc']] // Ordenar por fecha descendente por defecto
            });
        });

        class VentaManager {
            constructor() {
                this.initEventListeners();
            }

            initEventListeners() {
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('view-detail') || e.target.closest('.view-detail')) {
                        const button = e.target.classList.contains('view-detail') ? e.target : e.target.closest('.view-detail');
                        const idVenta = button.getAttribute('data-id');
                        this.loadVentaDetail(idVenta);
                    }
                });

                document.querySelector('.close-modal').addEventListener('click', () => this.closeModal());
                document.getElementById('detailModal').addEventListener('click', (e) => {
                    if (e.target === document.getElementById('detailModal')) this.closeModal();
                });
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.closeModal(); });
            }

            async loadVentaDetail(idVenta) {
                this.showModal();
                this.showLoading();

                try {
                    const response = await fetch(`?id_venta=${idVenta}&ajax=1`);
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    const data = await response.json();
                    this.displayVentaDetail(data, idVenta);
                } catch (error) {
                    console.error('Error al cargar el detalle:', error);
                    this.showError('Error al cargar los detalles de la venta.');
                }
            }

            showModal() {
                document.getElementById('detailModal').style.display = 'flex';
            }

            closeModal() {
                document.getElementById('detailModal').style.display = 'none';
            }

            showLoading() {
                document.getElementById('modalBody').innerHTML =
                    '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Cargando detalles...</div>';
            }

            displayVentaDetail(data, idVenta) {
                let html = '';

                if (data && data.length > 0) {
                    let totalVenta = 0;
                    data.forEach(detalle => totalVenta += detalle.total);

                    // Obtener información de la venta del primer registro (todos tienen la misma info de venta)
                    const infoVenta = data[0];

                    html = `
                        <div class="detail-info">
                            <div class="detail-row">
                                <div class="detail-label">Código de Venta:</div>
                                <div class="detail-value">VTA-${idVenta}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Fecha:</div>
                                <div class="detail-value">${infoVenta.Fecha || 'N/A'}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Empleado:</div>
                                <div class="detail-value">${infoVenta.Empleado || 'N/A'}</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Cliente:</div>
                                <div class="detail-value">${infoVenta.Cliente || 'N/A'}</div>
                            </div>
                        </div>

                        <h4 style="margin-top: 20px;">Productos Vendidos:</h4>
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Unidad Base</th>
                                    <th>Unidad Venta</th>
                                    <th>Cantidad</th>
                                    <th>Precio Venta</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    data.forEach(detalle => {
                        html += `
                            <tr>
                                <td>${detalle.producto}</td>
                                <td>${detalle.categoria}</td>
                                <td>${detalle.unidad_base}</td>
                                <td>${detalle.unidad_venta}</td>
                                <td>${detalle.cantidad}</td>
                                <td>$${parseFloat(detalle.precio_venta).toFixed(2)}</td>
                                <td>$${parseFloat(detalle.total).toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    html += `
                            </tbody>
                        </table>
                        <div class="total-section">
                            <div>Total: <span class="total-amount">$${(totalVenta).toFixed(2)}</span></div>
                        </div>
                    `;
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin detalles',
                        text: 'No se encontraron detalles para esta venta.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#3085d6'
                    });
                    html = '<div style="text-align:center; padding:20px;">No se encontraron detalles para esta venta.</div>';
                }

                document.getElementById('modalBody').innerHTML = html;
            }

            showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    confirmButtonColor: '#d33'
                });
                document.getElementById('modalBody').innerHTML =
                    `<div style="text-align:center; padding:20px; color:#dc3545;">${message}</div>`;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            new VentaManager();
        });
    </script>
</body>

</html>