<?php
session_start();

// Detectar rol en sesión
$rol = null;
$possibleKeys = ['rol', 'role', 'id_rol', 'tipo_usuario', 'cargo', 'nombre_rol', 'perfil', 'user_rol'];
foreach ($possibleKeys as $k) {
    if (!empty($_SESSION[$k])) {
        $rol = strtolower(trim($_SESSION[$k]));
        break;
    }
}
if ($rol === 'administrador' || $rol === 'admin' || $rol === '1') $rol = 'admin';
if ($rol === 'vendedor' || $rol === 'ventas' || $rol === '2') $rol = 'vendedor';

// Control de visibilidad por sección
function puedeVer($section, $rol) {
    if ($rol === 'admin') return true;
    if ($rol === 'vendedor') {
        $permitidas = ['introduccion', 'navegacion', 'clientes', 'ventas', 'reportes', 'faq', 'contacto', 'flujo_venta'];
        return in_array($section, $permitidas, true);
    }
    $publicas = ['introduccion', 'faq', 'contacto'];
    return in_array($section, $publicas, true);
}

// Contenido del manual
$secciones = [
    'introduccion' => [
        'titulo' => 'Introducción',
        'contenido' => '
            <p>Bienvenido al sistema FerreSys. Este manual le guiará en el uso de las principales funcionalidades del sistema, 
            como la gestión de clientes, productos, ventas, reportes y más. Cada sección incluye pasos detallados para facilitar su comprensión.</p>'
    ],
    'navegacion' => [
        'titulo' => 'Navegación general',
        'contenido' => '
            <ul>
                <li><strong>Menú lateral:</strong> Permite acceder a los módulos principales del sistema, como Clientes, Productos, Ventas, etc.</li>
                <li><strong>Barra superior:</strong> Incluye el nombre del Empleado que esta en el sistema, perfil de usuario y si se presiona se extendera un apartado para 
                cerrar la sesión.</li>
                <li><strong>Listados:</strong> Cada módulo cuenta con tablas para visualizar, filtrar y gestionar información.</li>
            </ul>'
    ],
    'usuarios' => [
        'titulo' => 'Gestión de Usuarios',
        'contenido' => '
            <p>El módulo de usuarios permite administrar las cuentas de acceso al sistema. Solo los administradores tienen acceso a esta sección.</p>
            <ol>
                <li>Seleccione "Usuarios" en el menú lateral.</li>
                <li>Y introduzca algún nuevo rol que sea necesario (cabe aclarar que el sistema actualmente esta delimitado).</li>
                <li>Utilice las opciones de edición para actualizar información o desactivar usuarios.</li>
            </ol>'
    ],
    'empleados' => [
        'titulo' => 'Gestión de Empleados',
        'contenido' => '
            <p>En este módulo puede registrar y gestionar la información de los empleados de su empresa y son quienes estarán adiministrando ciertos modulos de el sistema.</p>
            <ol>
                <li>Seleccione "Empleados" en el menú lateral.</li>
                <li>Complete los datos requeridos, como nombre, cargo y datos de contacto.</li>
                <li>Haga clic en "Guardar" para ver el nuevo registro en la tabla.</li>
                <li>Edite a los empleados según sea necesario desde las opciones de la tabla.</li>
                <li>Tenga en cuenta que solo los administradores pueden acceder a este módulo y si desactiva un Empleado el tal no podrá
                acceder al sistema nuevamente hasta hacer una recuperación de contraseña, o que el Administrador lo vuelva a activar.</li>
            </ol>'
    ],
    'categorias' => [
        'titulo' => 'Gestión de Categorías',
        'contenido' => '
            <p>Las categorías permiten organizar los productos para facilitar su búsqueda y gestión.</p>
            <ol>
                <li>Seleccione "Categorías" en el menú lateral.</li>
                <li>Asigne un nombre y una descripción a la categoría.</li>
                <li>Haga clic en "Guardar" para ver el nuevo registro en la tabla.</li>
                <li>Edite a las categorías según sea necesario desde las opciones de la tabla.</li>
            </ol>'
    ],
    'unidad_medida' => [
        'titulo' => 'Gestión de Unidades de Medida',
        'contenido' => '
            <p>Este módulo permite definir las unidades de medida utilizadas para los productos (por ejemplo, unidad, caja, metro).</p>
            <ol>
                <li>Seleccione "Unidades de Medida" en el menú lateral.</li>
                <li>Haga clic en "Nueva Unidad" para agregar una nueva.</li>
                <li>Haga clic en "Guardar" para ver el nuevo registro en la tabla.</li>
                <li>Edite las unidades de medida según sea necesario desde las opciones de la tabla.</li>
            </ol>'
    ],
    'productos' => [
        'titulo' => 'Gestión de Productos',
        'contenido' => '
            <p>Este módulo le permite administrar su catálogo de productos. Pasos básicos:</p>
            <ol>
                <li>Seleccione "Productos" en el menú lateral.</li>
                <li>Complete los datos requeridos, como nombre, categoría, precio y stock inicial.</li>
                <li>Haga clic en "Guardar" para ver el nuevo registro en la tabla.</li>
                <li>Edite o elimine productos según sea necesario.</li>
            </ol>'
    ],
    'compras' => [
        'titulo' => 'Gestión de Compras',
        'contenido' => '
            <p>En este módulo puede registrar las compras realizadas a proveedores.</p>
            <ol>
                <li>Seleccione "Compras" en el menú lateral.</li>
                <li>Haga clic en "Agregar Producto" para registrar una transacción.</li>
                <li>Seleccione el proveedor, agregue los productos y confirme la compra.</li>
                <li>Tenga en cuenta que estas compras llevan el 13% de IVA (impuesto al valor agregado).</li>
                <li>Para ver la orden de compra dirijase al menú lateral y haga clic en "Reportes".</li>
                <li>Busque uno llamado "Historial de Compra" haga clic ahí para ver compras actuales o anteriores.</li>
                </ol>'
    ],
    'clientes' => [
        'titulo' => 'Gestión de Clientes',
        'contenido' => '
            <p>En este módulo puede registrar, editar y gestionar la información de sus clientes. Pasos básicos:</p>
            <ol>
                <li>Seleccione "Clientes" en el menú lateral.</li>
                <li>Complete los datos requeridos, como nombre, dirección y contacto.</li>
                <li>Utilice las opciones de edición para actualizar clientes existentes.</li>
            </ol>'
    ],
    'ventas' => [
        'titulo' => 'Gestión de Ventas',
        'contenido' => '
            <p>En este módulo puede registrar y gestionar las ventas realizadas. Pasos básicos:</p>
            <ol>
                <li>Seleccione "Ventas" en el menú lateral.</li>
                <li>Haga clic en "Nueva Venta" para registrar una transacción.</li>
                <li>Seleccione el cliente, agregue los productos y confirme la venta.</li>
            </ol>'
    ],
    'reportes' => [
        'titulo' => 'Reportes',
        'contenido' => '
            <p>Genere reportes para analizar el desempeño de su negocio. Puede consultar reportes de ventas, productos y clientes.</p>'
    ],
    'backup' => [
        'titulo' => 'Generar y Restaurar Backup',
        'contenido' => '
            <p>Este módulo permite realizar copias de seguridad de la base de datos y restaurarlas cuando sea necesario.</p>
            <ol>
                <li>Seleccione "Backup" en el menú lateral.</li>
                <li>Haga clic en "Generar Backup" para crear una copia de seguridad.</li>
                <li>Para restaurar un backup, seleccione el archivo correspondiente y haga clic en "Restaurar".</li>
            </ol>'
    ],
    'faq' => [
        'titulo' => 'Preguntas Frecuentes',
        'contenido' => '
            <ul>
                <li><strong>No puedo acceder a un módulo:</strong> Verifique que su cuenta tenga los permisos necesarios.</li>
                <li><strong>Error al guardar información:</strong> Asegúrese de completar todos los campos obligatorios.</li>
                <li><strong>Problemas con el stock:</strong> Revise los movimientos de inventario en el módulo de productos.</li>
            </ul>'
    ],
    'contacto' => [
        'titulo' => 'Contacto y Soporte',
        'contenido' => '
            <p>Si necesita ayuda, contacte al soporte técnico proporcionando una descripción detallada del problema y capturas de pantalla si es posible.</p>
            <p>Correo de soporte: <strong>soporte@ferresys.com</strong></p>'
    ]
];

// HTML de salida
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ayuda - FerreSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .help-container {
            max-width: 900px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .seccion {
            margin-bottom: 20px;
        }
        .seccion h2 {
            font-size: 18px;
            cursor: pointer;
            color: #0d6efd;
        }
        .seccion h2:hover {
            text-decoration: underline;
        }
        .contenido {
            display: none;
            margin-top: 10px;
        }
        .contenido p, .contenido ul, .contenido ol {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="help-container">
            <h1 class="text-center">Manual de Usuario - FerreSys</h1>
            <p class="text-center text-muted">Guía para el uso del sistema</p>
            <div class="text-center mb-3">
                <button class="btn btn-primary" onclick="window.print()">Imprimir Manual</button>
                <button class="btn btn-secondary" onclick="expandAll()">Expandir Todo</button>
            </div>
            <?php foreach ($secciones as $clave => $s): ?>
                <?php if (!puedeVer($clave, $rol)) continue; ?>
                <div class="seccion">
                    <h2 onclick="toggle(this)"><?= htmlspecialchars($s['titulo']) ?></h2>
                    <div class="contenido"><?= $s['contenido'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        function toggle(h2) {
            const contenido = h2.nextElementSibling;
            if (contenido.style.display === 'block') {
                contenido.style.display = 'none';
            } else {
                contenido.style.display = 'block';
            }
        }

        function expandAll() {
            document.querySelectorAll('.contenido').forEach(function(contenido) {
                contenido.style.display = 'block';
            });
        }
    </script>
</body>
</html>