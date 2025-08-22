<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../../css/empleado.css">



</head>

<body>
    <div class="container-fluid px-3">
        <div class="row form-table-container d-flex">
            <!-- Formulario -->
            <div class="col-md-4">
                <div class="card-form h-100">
                    <div class="card-title">Registro de Empleado</div>
                    <form id="empleadoForm">
                        <div class="row g-3">
                            <!-- Nombre y Apellido juntos -->
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-person-fill"></i>Nombre</label>
                                <input type="text" autocomplete="off" name="nombreEmpleado" class="form-control"
                                    id="nombreEmpleado" placeholder="Ingresar Nombre" required
                                    oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-person-vcard-fill"></i>Apellido</label>
                                <input type="text" autocomplete="off" name="apellidoEmpleado" class="form-control"
                                    id="apellidoEmpleado" placeholder="Ingresar Apellido" required
                                    oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                            </div>

                            <!-- DUI y Teléfono juntos -->
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-credit-card-2-front-fill"></i>DUI</label>
                                <input type="text" autocomplete="off" name="duiEmpleado" class="form-control"
                                    id="duiEmpleado" placeholder="Ingrese número de DUI" required maxlength="10"
                                    pattern="\d{8}-\d{1}" title="Formato válido: 12345678-9" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/\D/g,'').slice(0,9); if(this.value.length>8){this.value=this.value.slice(0,8)+'-'+this.value.slice(8)}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-telephone-fill"></i>Teléfono</label>
                                <input type="text" autocomplete="off" name="telefono" class="form-control" id="telefono"
                                    placeholder="Ingresar número de teléfono" required maxlength="9"
                                    pattern="\d{4}-\d{4}" inputmode="numeric" title="Formato válido: 1234-5678"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,8); if(this.value.length>4){this.value=this.value.slice(0,4)+'-'+this.value.slice(4)}">
                            </div>

                            <!-- Dirección sola -->
                            <div class="col-12">
                                <label class="form-label form-icon"><i class="bi bi-house-fill"></i>Dirección</label>
                                <input type="text" autocomplete="off" name="direccion" class="form-control"
                                    id="direccion" required placeholder="Ingresar Dirección">
                            </div>

                            <!-- Correo solo -->
                            <div class="col-12">
                                <label class="form-label form-icon"><i class="bi bi-envelope-fill"></i>Correo</label>
                                <input type="email" autocomplete="off" name="correoEmpleado" class="form-control"
                                    id="correoEmpleado" required placeholder="Ingresar correo electrónico">
                            </div>

                            <!-- Usuario y Estado juntos -->
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-person-gear"></i>Usuario</label>
                                <select class="form-select" id="usuario" required>
                                    <option value="">Seleccione</option>
                                    <option value="Administrador">Administrador</option>
                                    <option value="Empleado">Empleado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-toggle-on"></i>Estado</label>
                                <select class="form-select" id="estado" required>
                                    <option value="">Seleccione</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Baja">Baja</option>
                                </select>
                            </div>

                            <!-- Botones -->
                            <div class="col-12 text-center mt-4">
                                <button type="reset" class="btn btn-warning px-5 py-2">Cancelar</button>
                                <button type="submit" class="btn btn-success px-5 py-2">Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla -->
            <div class="col-md-8" id="tablaCol">
                <div class="table-section">
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Registros</div>
                    <div class="table-responsive">
                        <table id="tablaEmpleados" class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>DUI</th>
                                    <th>Teléfono</th>
                                    <th>Dirección</th>
                                    <th>Correo</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                               


                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        // DataTable
        $(document).ready(function () {
            $('#tablaEmpleados').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
            });
        });

        // Toggle form al hacer click en el título
        const tablaTitle = document.getElementById('tablaTitle');
        const formCard = document.querySelector('.card-form');
        const tablaCol = document.getElementById('tablaCol');

        window.addEventListener('load', () => {
            formCard.style.display = 'block';
            tablaCol.classList.remove('col-12');
            tablaCol.classList.add('col-md-8');
        });

        tablaTitle.addEventListener('click', () => {
            if (formCard.style.display === 'none') {
                formCard.style.display = 'block';
                tablaCol.classList.remove('col-12');
                tablaCol.classList.add('col-md-8');
            } else {
                formCard.style.display = 'none';
                tablaCol.classList.remove('col-md-8');
                tablaCol.classList.add('col-12');
            }
        });
    </script>
</body>

</html>
