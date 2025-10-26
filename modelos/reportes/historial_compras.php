<?php
// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}

include_once '../../conexion/conexion.php';
include_once '../compras/detalle_compra.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$detalleCompra = new DetalleCompra($db);

// Obtener listado de compras
$comprasResult = $detalleCompra->obtenerListadoCompras();
$comprasArray = $comprasResult->fetchAll(PDO::FETCH_ASSOC);

// Manejo de solicitudes AJAX para detalles
if (isset($_GET['ajax']) && $_GET['ajax'] == '1' && isset($_GET['id_compra'])) {
    $id_Compra = $_GET['id_compra'];
    $detalleResult = $detalleCompra->obtenerDetalleCompra($id_Compra);
    $detalleData = $detalleResult->fetchAll(PDO::FETCH_ASSOC);

    // Formatear la fecha tal como viene de la base (solo día-mes-año)
    for ($i = 0; $i < count($detalleData); $i++) {
        if (!empty($detalleData[$i]['Fecha'])) {
            try {
                $fechaObj = new DateTime($detalleData[$i]['Fecha']);
                $detalleData[$i]['Fecha'] = $fechaObj->format('d/m/Y'); // formato día/mes/año
            } catch (Exception $e) {
                // fallback: dejar la fecha cruda
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
    <title>Sistema de Gestión de Compras</title>
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
                <h2><i class="fas fa-list"></i> Historial de Compras</h2>
            </div>
            <div class="card-body">
                <table id="comprasTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Código Compra</th>
                            <th>Fecha</th>
                            <th>Empleado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="comprasBody">
                        <?php if (count($comprasArray) > 0): ?>
                            <?php foreach ($comprasArray as $compra): ?>
                                <tr>
                                    <td>CMP-<?php echo htmlspecialchars($compra['CodigoCompra']); ?></td>
                                    <td>
                                        <?php
                                        // Formatear la fecha tal como viene de la base
                                        $fechaSalida = '';
                                        if (!empty($compra['Fecha'])) {
                                            try {
                                                $fechaObj = new DateTime($compra['Fecha']);
                                                $fechaSalida = $fechaObj->format('d/m/Y'); // solo día/mes/año
                                            } catch (Exception $e) {
                                                $fechaSalida = htmlspecialchars($compra['Fecha']); // fallback
                                            }
                                        }
                                        echo $fechaSalida;
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($compra['Empleado']); ?></td>
                                    <td>
                                        <button class="btn-modal view-detail" data-id="<?php echo $compra['CodigoCompra']; ?>">
                                            <i class="fas fa-eye"></i> Ver Detalle
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">
                                    No se encontraron compras registradas.
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
                    <h2><i class="fas fa-file-invoice"></i> Detalle de Compra</h2>
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
            const filas = $('#comprasTable tbody tr');
            const tieneRegistros = filas.length > 0 && filas.find('td[colspan]').length === 0;

            if (!tieneRegistros) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin registros',
                    text: 'Aún no se han realizado compras.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            $('#comprasTable').DataTable({
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
            });
        });

        class CompraManager {
            constructor() {
                this.initEventListeners();
            }

            initEventListeners() {
                document.addEventListener('click', (e) => {
                    if (e.target.classList.contains('view-detail') || e.target.closest('.view-detail')) {
                        const button = e.target.classList.contains('view-detail') ? e.target : e.target.closest('.view-detail');
                        const idCompra = button.getAttribute('data-id');
                        this.loadCompraDetail(idCompra);
                    }
                });

                document.querySelector('.close-modal').addEventListener('click', () => this.closeModal());
                document.getElementById('detailModal').addEventListener('click', (e) => {
                    if (e.target === document.getElementById('detailModal')) this.closeModal();
                });
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape') this.closeModal(); });
            }

            async loadCompraDetail(idCompra) {
                this.showModal();
                this.showLoading();

                try {
                    const response = await fetch(`?id_compra=${idCompra}&ajax=1`);
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    const data = await response.json();
                    this.displayCompraDetail(data);
                } catch (error) {
                    console.error('Error al cargar el detalle:', error);
                    this.showError('Error al cargar los detalles de la compra.');
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

            displayCompraDetail(data) {
                let html = '';

                if (data && data.length > 0) {
                    let totalCompra = 0;
                    data.forEach(detalle => totalCompra += detalle.Cantidad * detalle.PrecioUnitario);

                    html = `
                        <div class="detail-info">
                            <div class="detail-row"><div class="detail-label">Código de Compra:</div><div class="detail-value">CMP-${data[0].CodigoCompra}</div></div>
                            <div class="detail-row"><div class="detail-label">Fecha:</div><div class="detail-value">${data[0].Fecha}</div></div>
                            <div class="detail-row"><div class="detail-label">Proveedor:</div><div class="detail-value">${data[0].Proveedor || 'N/A'}</div></div>
                            <div class="detail-row"><div class="detail-label">Empleado:</div><div class="detail-value">${data[0].Empleado}</div></div>
                        </div>

                        <h4 style="margin-top: 20px;">Productos Comprados:</h4>
                        <table class="products-table">
                            <thead>
                                <tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                    `;

                    data.forEach(detalle => {
                        const subtotal = detalle.Cantidad * detalle.PrecioUnitario;
                        html += `
                            <tr>
                                <td>${detalle.Producto}</td>
                                <td>${detalle.Cantidad}</td>
                                <td>$${parseFloat(detalle.PrecioUnitario).toFixed(2)}</td>
                                <td>$${subtotal.toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    html += `
                            </tbody>
                        </table>
                        <div class="total-section">
                            <div>Sub-Total: <span class="total-amount">$${totalCompra.toFixed(2)}</span></div>
                            <div>IVA 13%: <span class="total-amount">$${(totalCompra * 0.13).toFixed(2)}</span></div>
                            <div>Total: <span class="total-amount">$${(totalCompra * 1.13).toFixed(2)}</span></div>
                        </div>
                    `;
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin detalles',
                        text: 'No se encontraron detalles para esta compra.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#3085d6'
                    });
                    html = '<div style="text-align:center; padding:20px;">No se encontraron detalles para esta compra.</div>';
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
            new CompraManager();
        });
    </script>
</body>

</html>