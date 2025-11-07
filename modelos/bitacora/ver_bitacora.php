<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora del Sistema</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            padding: 25px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            font-size: 2.4rem;
            font-weight: 300;
            letter-spacing: 1px;
        }

        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 8px;
            font-weight: 300;
        }

        .logo {
            font-size: 2.5rem;
            opacity: 0.9;
        }

        .filtros-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filtro-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filtro-group label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .filtro-input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            min-width: 180px;
        }

        .btn-filtrar {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-filtrar:hover {
            background: linear-gradient(135deg, #2980b9 0%, #2573a7 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-limpiar {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-limpiar:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .bitacora-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .bitacora-header {
            background-color: #34495e;
            color: white;
            padding: 18px 25px;
            display: grid;
            grid-template-columns: 100px 1fr 1fr 2fr 200px;
            gap: 20px;
            font-weight: 600;
            font-size: 1.05rem;
        }

        .bitacora-list {
            max-height: 70vh;
            overflow-y: auto;
        }

        .bitacora-item {
            padding: 18px 25px;
            display: grid;
            grid-template-columns: 100px 1fr 1fr 2fr 200px;
            gap: 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .bitacora-item:hover {
            background-color: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .bitacora-item:last-child {
            border-bottom: none;
        }

        .id-cell {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .empleado-cell {
            color: #2980b9;
            font-weight: 500;
        }

        .accion-cell {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 20px;
            text-align: center;
            font-size: 0.9rem;
            width: fit-content;
        }

        .descripcion-cell {
            color: #555;
        }

        .fecha-cell {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .accion-alta {
            background-color: rgba(39, 174, 96, 0.15);
            color: #27ae60;
        }

        .accion-baja {
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
        }

        .accion-modificacion {
            background-color: rgba(243, 156, 18, 0.15);
            color: #f39c12;
        }

        .accion-consulta {
            background-color: rgba(52, 152, 219, 0.15);
            color: #3498db;
        }

        .accion-login {
            background-color: rgba(155, 89, 182, 0.15);
            color: #9b59b6;
        }

        .accion-logout {
            background-color: rgba(149, 165, 166, 0.15);
            color: #95a5a6;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 25px;
            color: #7f8c8d;
            font-size: 0.95rem;
            border-top: 1px solid #eee;
        }

        @media (max-width: 1100px) {

            .bitacora-header,
            .bitacora-item {
                grid-template-columns: 80px 1fr 1fr 2fr 160px;
                gap: 15px;
            }
        }

        @media (max-width: 900px) {

            .bitacora-header,
            .bitacora-item {
                grid-template-columns: 70px 1fr 1fr 2fr 140px;
                gap: 12px;
                padding: 15px 20px;
            }

            .filtros-container {
                flex-direction: column;
                align-items: stretch;
            }

            .filtro-input {
                min-width: auto;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .bitacora-header {
                display: none;
            }

            .bitacora-item {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 20px;
                border: 1px solid #eee;
                margin-bottom: 15px;
                border-radius: 8px;
            }

            .bitacora-item:hover {
                transform: none;
            }

            .bitacora-item::before {
                content: "ID: " attr(data-id);
                font-weight: bold;
                color: #2c3e50;
                font-size: 1.1rem;
                margin-bottom: 5px;
            }
        }

        /* Estilos para la barra de desplazamiento */
        .bitacora-list::-webkit-scrollbar {
            width: 8px;
        }

        .bitacora-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .bitacora-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .bitacora-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div>
                    <h1>Bitácora del Sistema Ferreteria Michapa</h1>
                    <div class="subtitle">Registro histórico de actividades del sistema</div>
                </div>
                <div class="logo">
                    📋
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Filtros -->
        <form method="GET" action="" class="filtros-container">
            <div class="filtro-group">
                <label for="fecha_desde">Fecha Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" class="filtro-input"
                    value="<?php echo isset($_GET['fecha_desde']) ? htmlspecialchars($_GET['fecha_desde']) : ''; ?>">
            </div>

            <div class="filtro-group">
                <label for="fecha_hasta">Fecha Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" class="filtro-input"
                    value="<?php echo isset($_GET['fecha_hasta']) ? htmlspecialchars($_GET['fecha_hasta']) : ''; ?>">
            </div>

            <button type="submit" class="btn-filtrar">Filtrar</button>
            <button type="button" class="btn-limpiar" onclick="limpiarFiltros()">Limpiar</button>
        </form>

        <div class="bitacora-container">
            <div class="bitacora-header">
                <div>ID</div>
                <div>Empleado</div>
                <div>Acción</div>
                <div>Descripción</div>
                <div>Fecha y Hora</div>
            </div>

            <div class="bitacora-list" id="bitacoraList">
                <?php
                include_once '../../conexion/conexion.php';
                include_once '../categoria/categoria.php';
                include_once '../bitacora/Bitacora.php';

                try {
                    $conexion = new Conexion();
                    $db = $conexion->getConnection();
                    $bitacora = new Bitacora($db);

                    // Construir la consulta con filtros
                    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
                    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

                    // Obtener los registros de la bitácora con filtros
                    $query = "
                        SELECT 
                            b.id_Bitacora,
                            e.nombre AS empleado,
                            b.accion,
                            b.descripcion,
                            b.fecha_hora
                        FROM bitacora b
                        INNER JOIN empleados e ON b.id_Empleado = e.id_Empleado
                    ";

                    $conditions = [];
                    $params = [];

                    if (!empty($fecha_desde)) {
                        $conditions[] = "DATE(b.fecha_hora) >= ?";
                        $params[] = $fecha_desde;
                    }

                    if (!empty($fecha_hasta)) {
                        $conditions[] = "DATE(b.fecha_hora) <= ?";
                        $params[] = $fecha_hasta;
                    }

                    if (count($conditions) > 0) {
                        $query .= " WHERE " . implode(" AND ", $conditions);
                    }

                    $query .= " ORDER BY b.fecha_hora DESC";

                    $stmt = $db->prepare($query);
                    $stmt->execute($params);
                    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($registros) > 0) {
                        foreach ($registros as $registro) {
                            // Determinar la clase CSS según la acción
                            $claseAccion = '';
                            $accion = strtolower($registro['accion']);

                            if (strpos($accion, 'alta') !== false || strpos($accion, 'crear') !== false || strpos($accion, 'insert') !== false) {
                                $claseAccion = 'accion-alta';
                            } elseif (strpos($accion, 'baja') !== false || strpos($accion, 'eliminar') !== false || strpos($accion, 'delete') !== false) {
                                $claseAccion = 'accion-baja';
                            } elseif (strpos($accion, 'modif') !== false || strpos($accion, 'actualizar') !== false || strpos($accion, 'update') !== false) {
                                $claseAccion = 'accion-modificacion';
                            } elseif (strpos($accion, 'consulta') !== false || strpos($accion, 'leer') !== false || strpos($accion, 'select') !== false) {
                                $claseAccion = 'accion-consulta';
                            } elseif (strpos($accion, 'login') !== false) {
                                $claseAccion = 'accion-login';
                            } elseif (strpos($accion, 'logout') !== false) {
                                $claseAccion = 'accion-logout';
                            } else {
                                $claseAccion = 'accion-consulta';
                            }

                            // Formatear la fecha
                            $fechaFormateada = date('d M Y, H:i', strtotime($registro['fecha_hora']));

                            echo "
                            <div class='bitacora-item' data-id='{$registro['id_Bitacora']}'>
                                <div class='id-cell'>#{$registro['id_Bitacora']}</div>
                                <div class='empleado-cell'>{$registro['empleado']}</div>
                                <div class='accion-cell {$claseAccion}'>{$registro['accion']}</div>
                                <div class='descripcion-cell'>{$registro['descripcion']}</div>
                                <div class='fecha-cell'>{$fechaFormateada}</div>
                            </div>
                            ";
                        }
                    } else {
                        echo "
                        <div class='empty-state'>
                            <div class='icon'>📝</div>
                            <h3>No hay registros en la bitácora</h3>
                            <p>" .
                            (!empty($fecha_desde) || !empty($fecha_hasta)
                                ? "No se encontraron registros para el rango de fechas seleccionado."
                                : "No se han encontrado actividades registradas en el sistema.") .
                            "</p>
                        </div>
                        ";
                    }

                } catch (Exception $e) {
                    echo "
                    <div class='empty-state'>
                        <div class='icon'>⚠️</div>
                        <h3>Error al cargar la bitácora</h3>
                        <p>No se pudieron cargar los registros: " . htmlspecialchars($e->getMessage()) . "</p>
                    </div>
                    ";
                }
                ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            Sistema Ferreteria Michapa Bitácora &copy; <?php echo date('Y'); ?> - Total de registros:
            <?php echo isset($registros) ? count($registros) : '0'; ?>
        </div>
    </footer>

    <script>
        function limpiarFiltros() {
            // Limpiar los campos de fecha
            document.getElementById('fecha_desde').value = '';
            document.getElementById('fecha_hasta').value = '';

            // Recargar la página sin parámetros
            window.location.href = window.location.pathname;
        }

        // Validación de fechas
        document.addEventListener('DOMContentLoaded', function () {
            const fechaDesde = document.getElementById('fecha_desde');
            const fechaHasta = document.getElementById('fecha_hasta');

            if (fechaDesde && fechaHasta) {
                fechaDesde.addEventListener('change', function () {
                    if (this.value && fechaHasta.value && this.value > fechaHasta.value) {
                        alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"');
                        this.value = '';
                    }
                });

                fechaHasta.addEventListener('change', function () {
                    if (this.value && fechaDesde.value && this.value < fechaDesde.value) {
                        alert('La fecha "Hasta" no puede ser menor que la fecha "Desde"');
                        this.value = '';
                    }
                });
            }
        });
    </script>
</body>

</html>