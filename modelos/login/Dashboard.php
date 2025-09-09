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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferretería Michapa - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../css/menu.css" rel="stylesheet" />
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <div class="logo-content">
                <h2>Michapa <span>Ferretería</span></h2>
            </div>
            <button class="toggle-btn" id="toggle-sidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="menu">
            <div class="menu-item active" onclick="location.href='Dashboard.php'" style="cursor:pointer;">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-users"></i>
                <a href="../../modelos/empleado/crear_empleado.php" onclick="abrirFormularios(event)">Empleados</a>
            </div>
            <div class="menu-item">
                <i class="fas fa-user-friends"></i>
                <a href="../../modelos/usuario/crear_usuario.php" onclick="abrirFormularios(event)">Usuarios</a>
            </div>
            <div class="menu-item">
                <i class="fas fa-box"></i>
                <span>Inventario</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Ventas</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Facturación</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reportes</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </div>
        </div>
    </div>

    <!-- Barra superior -->
    <div class="top-bar">
        <!-- Nombre completo y rol con guion -->
        <span class="user-name">
            <?php
            $nombre = $_SESSION['nombre'] ?? '';
            $apellido = $_SESSION['apellido'] ?? '';
            $rol = $_SESSION['rol'] ?? '';
            echo htmlspecialchars(trim("$nombre $apellido - $rol"));
            ?>
        </span>
        <i class="fas fa-user-circle" id="userTopIcon"></i>

        <div class="dropdown-top" id="dropdownTop">
            <a href="../login/login.php">Cerrar Sesión</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Iframe oculto inicialmente -->
        <iframe name="myFrame" id="myFrame" class="embed-responsive-item" style="display:none;"></iframe>

        <!-- Contenedor de dashboard cards -->
        <div class="dashboard-cards-container" id="dashboardCardsContainer">
            <div class="dashboard-cards" id="dashboardCards">
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
                    <div class="icon red">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info">
                        <h3>15</h3>
                        <p>Empleados</p>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="icon orange">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="info">
                        <h3>$8,542</h3>
                        <p>Ganancias Hoy</p>
                    </div>
                </div>
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
</body>

</html>