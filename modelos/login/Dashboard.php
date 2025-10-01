<?php
session_start();

// Proteger el dashboard: solo usuarios logueados
if (!isset($_SESSION['id_Usuario'])) {
    header("Location: ../login/login.php");
    exit();
}

// Obtener nombre y rol del usuario desde la sesión
$nombre_usuario = $_SESSION['nombre'] ?? "";
$apellido = $_SESSION['apellido'] ?? '';
$rol_usuario = $_SESSION['rol'] ?? "";

// DEBUG: Verificar datos en sesión
// echo "<!-- DEBUG: " . $_SESSION['id_Empleado'] . " - " . $_SESSION['nombre'] . " " . $_SESSION['apellido'] . " -->";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferretería Michapa - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../../css/menu.css" rel="stylesheet" />
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-content">
                <h2>Ferretería <span>Michapa</span></h2>
            </div>
            <button class="toggle-btn" id="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="menu">
            <div class="menu-item active" onclick="location.href='Dashboard.php'" style="cursor:pointer;">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </div>

            <?php if ($rol_usuario === 'Administrador') : ?>
                <div class="menu-item">
                    <i class="fas fa-user-friends"></i>
                    <a href="../../modelos/usuario/crear_usuario.php" onclick="abrirFormularios(event)">Usuarios</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-users"></i>
                    <a href="../../modelos/empleado/crear_empleado.php" onclick="abrirFormularios(event)">Empleados</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-th-large"></i>
                    <a href="../../modelos/categoria/crear_categoria.php" onclick="abrirFormularios(event)">Categorias</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-ruler-combined"></i>
                   <a href="../../modelos/unidad de medida/crear_unidad.php" onclick="abrirFormularios(event)">Unidad de Medida</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-boxes"></i>
                    <a href="../../modelos/productos/crear_producto.php" onclick="abrirFormularios(event)">Productos</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-handshake"></i>
                    <a href="../../modelos/proveedores/crear_proveedor.php"onclick="abrirFormularios(event)">Proveedores</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Compras</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-users"></i>
                    <a href="../../modelos/cliente/crear_cliente.php" onclick="abrirFormularios(event)">Clientes</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-receipt"></i>
                    <span>Ventas</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-database"></i><i class="fas fa-download"></i>
                    <a href="../../backup/backup.php" onclick="abrirFormularios(event)">Generar Backup</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-database"></i><i  class="fas fa-upload"></i>
                    <a href="../../backup/restaurar_backup.php" onclick="abrirFormularios(event)">Restaurar Backup</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-circle-question"></i>
                    <span>Ayuda</span>
                </div>
            <?php endif; ?>

            <?php if ($rol_usuario === 'Vendedor') : ?>
                <div class="menu-item">
                    <i class="fas fa-users"></i>
                    <a href="../../modelos/cliente/crear_cliente.php" onclick="abrirFormularios(event)">Clientes</a>
                </div>
                <div class="menu-item">
                    <i class="fas fa-boxes"></i>
                    <span>Productos</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-receipt"></i>
                    <span>Ventas</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-circle-question"></i>
                    <span>Ayuda</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra superior -->
    <div class="top-bar">
        <span class="user-name">
            <?php
            echo htmlspecialchars(
                (explode(' ', trim($_SESSION['nombre'] ?? ''))[0] ?? '') . ' ' .
                    (explode(' ', trim($_SESSION['apellido'] ?? ''))[0] ?? '') . ' - ' .
                    ($_SESSION['rol'] ?? '')
            );
            ?>
        </span>
        <i class="fas fa-user-circle" id="userTopIcon"></i>

        <div class="dropdown-top" id="dropdownTop">
            <a href="../login/logout.php">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Iframe oculto inicialmente -->
        <iframe name="myFrame" id="myFrame" class="embed-responsive-item" style="display:none;"></iframe>

        <!-- Contenedor de dashboard cards -->
        <div class="dashboard-cards-container" id="dashboardCardsContainer">
            <div class="dashboard-cards" id="dashboardCards">
                <?php if ($rol_usuario === 'Administrador') : ?>
                    <div class="card stat-card">
                        <div class="icon blue">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="info">
                            <h3>152</h3>
                            <p>Ventas Hoy</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon green">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="info">
                            <h3>1,258</h3>
                            <p>Productos en Stock</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon purple">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="info">
                            <h3>24</h3>
                            <p>Productos Bajo Stock</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon orange">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="info">
                            <h3>15</h3>
                            <p>Compras </p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon teal">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <div class="info">
                            <h3>5</h3>
                            <p>Productos Más Vendidos</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon red">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info">
                            <h3>12</h3>
                            <p>Clientes</p>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($rol_usuario === 'Vendedor') : ?>
                    <div class="card stat-card">
                        <div class="icon blue">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="info">
                            <h3>152</h3>
                            <p>Ventas Hoy</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon green">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="info">
                            <h3>1,258</h3>
                            <p>Productos en Stock</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon purple">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="info">
                            <h3>24</h3>
                            <p>Productos Bajo Stock</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon teal">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <div class="info">
                            <h3>5</h3>
                            <p>Productos Más Vendidos</p>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="icon red">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info">
                            <h3>12</h3>
                            <p>Clientes</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer>
            Universidad Nacional de El Salvador. Todos los derechos reservados &copy; 2025
        </footer>
    </div>

    <script>
        // Toggle sidebar
        const toggleButton = document.getElementById('toggle-sidebar');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const topBar = document.querySelector('.top-bar');

        toggleButton.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');

            topBar.style.left = sidebar.classList.contains('collapsed') ?
                'var(--sidebar-collapsed-width)' :
                'var(--sidebar-width)';
        });

        // Menu items active
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Mostrar iframe y ocultar dashboard
        function abrirFormularios(event) {
            event.preventDefault();
            const iframe = document.getElementById('myFrame');
            const dashboardCardsContainer = document.getElementById('dashboardCardsContainer');

            iframe.style.display = 'block';
            iframe.src = event.currentTarget.getAttribute('href');

            dashboardCardsContainer.style.display = 'none';
        }

        // Dropdown usuario superior derecho
        const userTopIcon = document.getElementById('userTopIcon');
        const dropdownTop = document.getElementById('dropdownTop');

        userTopIcon.addEventListener('click', () => {
            dropdownTop.style.display = dropdownTop.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function(event) {
            if (!userTopIcon.contains(event.target) && !dropdownTop.contains(event.target)) {
                dropdownTop.style.display = 'none';
            }
        });
    </script>
    <script>
setInterval(() => {
    fetch("check_estado.php")
        .then(res => res.json())
        .then(data => {
            console.log("Respuesta del servidor:", data); 
            if (!data.activo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesión terminada',
                    text: 'Tu cuenta ha sido desactivada. Serás redirigido al login.',
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    window.location.href = "../login/logout.php"; 
                });
            }
        })
        .catch(err => console.error("Error verificando estado:", err));
}, 15000); 
</script>

</body>
</html>