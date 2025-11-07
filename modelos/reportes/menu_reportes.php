<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Reportes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../css/menu_reportes.css">
</head>

<body>

    <h1 class="center">Reportes</h1>
    <div class="container">
        <div class="card stat-card">
            <a href="../reportes/compra_proveedor.php">
                <div class="icon blue">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="info">
                    <h3>Compra por proveedor</h3>
                    <p>Reporte de compras por proveedor</p>
                </div>
            </a>
        </div>
        <div class="card stat-card">
            <a href="../reportes/historial_compras.php">
                <div class="icon blue">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="info">
                    <h3>Historial de compras</h3>
                    <p>Reporte de compras realizadas</p>
                </div>
            </a>
        </div>


        <div class="card stat-card">
            <a href="../reportes/productos_stock.php">
                <div class="icon green">
                    <i class="fas fa-box"></i>
                </div>
                <div class="info">
                    <h3>Productos</h3>
                    <p>Reporte de productos disponibles</p>
                </div>
            </a>
        </div>

        <div class="card stat-card">
            <a href="../reportes/productos_baja_existencia.php">
                <div class="icon red">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div class="info">
                    <h3>Productos con baja existencia</h3>
                    <p>Reporte de productos con poco stock</p>
                </div>
            </a>
        </div>

        <div class="card stat-card">
            <a href="../reportes/historial_ventas.php">
                <div class="icon red">
                    <i class="fas fa-shop"></i>
                </div>
                <div class="info">
                    <h3>Historial de ventas</h3>
                    <p>Reporte de Historial de Ventas</p>
                </div>
            </a>
        </div>
        <div class="card stat-card">
            <a href="../reportes/ventas_rango_fechas.php">
                <div class="icon blue">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="info">
                    <h3>Ventas por Rango</h3>
                    <p>Reporte de ventas diarias, mensuales, trimestrales, semestrales y anuales</p>
                </div>
            </a>
        </div>

        <div class="card stat-card">
            <a href="../reportes/productos+-vendidos.php">
                <div class="icon blue">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="info">
                    <h3>Ventas por Rango</h3>
                    <p>Reporte de ventas diarias, mensuales, trimestrales, semestrales y anuales</p>
                </div>
            </a>
        </div>

    </div>
</body>

</html>