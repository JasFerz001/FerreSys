<?php
date_default_timezone_set('America/El_Salvador');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/venta.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>

    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-8">
                <!-- Información de la Venta -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>Información de la Venta
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Venta</label>
                                    <!-- Campo visible (solo lectura) -->
                                    <input type="text" class="form-control" id="fechaVentaVisual" readonly
                                        value="<?= date('d-m-Y') ?>">

                                    <!-- Campo oculto para enviar al servidor -->
                                    <input type="hidden" name="fechaVenta" id="fechaVenta" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Cliente *</label>
                                    <select class="form-select" id="selectCliente" required>
                                        <option value="">Seleccionar cliente...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Empleado</label>
                                    <input type="text" class="form-control" id="empleadoActual" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selección de Productos -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-boxes me-2"></i>Selección de Productos
                        </h5>
                        <div class="d-flex gap-2">
                            <div class="col">
                                <select class="form-select" id="selectCategoria">
                                    <option value="">Todas las categorías</option>
                                </select>
                            </div>
                            <div class="col">
                                <input type="text" id="buscadorProductos" class="form-control"
                                    placeholder="Buscar producto...">
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="productosContainer" class="row">
                            <!-- Los productos se cargarán aquí -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Carrito de Compra -->
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-receipt me-2"></i>Detalle de Venta
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="carritoVacio" class="text-center text-muted py-4">
                            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                            <p>No hay productos en el carrito</p>
                        </div>
                        <div id="carritoItems" style="display: none;">
                            <!-- Los items del carrito se mostrarán aquí -->
                        </div>
                        <div id="resumenVenta" style="display: none;">
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong id="totalVenta">$0.00</strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button id="btnRealizarVenta" class="btn btn-success w-100" disabled>
                            <i class="fas fa-check me-2"></i>Realizar Venta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para configurar producto -->
    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Producto</label>
                        <input type="text" class="form-control" id="modalProductoNombre" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proveedor</label>
                        <input type="text" class="form-control" id="modalProductoProveedor" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Unidad de Venta</label>
                        <select class="form-select" id="modalUnidadVenta">
                            <!-- Las opciones se cargan dinámicamente en JS -->
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Precio de Compra</label>
                                <input type="text" class="form-control" id="modalPrecioCompra" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Precio de Venta *</label>
                                <input type="number" class="form-control" id="modalPrecioVenta" step="0.01" min="0.01"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Existencia</label>
                                <input type="text" class="form-control" id="modalExistencia" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cantidad *</label>
                                <input type="number" class="form-control" id="modalCantidad" min="1" value="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtotal</label>
                        <input type="text" class="form-control" id="modalSubtotal" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnAgregarCarrito">Agregar al Carrito</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script></script>
    <script>
        class VentaManager {
            constructor() {
                this.carrito = [];
                this.productoSeleccionado = null;
                this.init();
            }

            init() {
                this.cargarDatosIniciales();
                this.setupEventListeners();
            }

            async cargarDatosIniciales() {
                await this.cargarClientes();
                await this.cargarCategorias();
                await this.cargarEmpleadoActual();
                await this.cargarProductos();
            }

            async cargarClientes() {
                try {
                    const response = await fetch('realizar_ventas.php?action=getClientes');
                    const data = await response.json();

                    if (data.success) {
                        const select = document.getElementById('selectCliente');
                        select.innerHTML = '<option value="">Seleccionar cliente...</option>';

                        data.clientes.forEach(cliente => {
                            const option = document.createElement('option');
                            option.value = cliente.id_Cliente;
                            option.textContent = `${cliente.nombre}`;
                            select.appendChild(option);
                        });
                    }
                } catch (error) {
                    console.error('Error al cargar clientes:', error);
                }
            }

            async cargarCategorias() {
                try {
                    const response = await fetch('realizar_ventas.php?action=getCategorias');
                    const data = await response.json();

                    if (data.success) {
                        const select = document.getElementById('selectCategoria');
                        select.innerHTML = '<option value="">Todas las categorías</option>';

                        data.categorias.forEach(categoria => {
                            const option = document.createElement('option');
                            option.value = categoria.id_Categoria;
                            option.textContent = categoria.nombre;
                            select.appendChild(option);
                        });
                    }
                } catch (error) {
                    console.error('Error al cargar categorías:', error);
                }
            }

            async cargarEmpleadoActual() {
                try {
                    const response = await fetch('realizar_ventas.php?action=getEmpleadoActual');
                    const data = await response.json();

                    if (data.success) {
                        document.getElementById('empleadoActual').value = data.empleado.nombre;
                    }
                } catch (error) {
                    console.error('Error al cargar empleado:', error);
                }
            }

            async cargarProductos(idCategoria = '') {
                try {
                    const url = idCategoria ?
                        `realizar_ventas.php?action=getProductos&idCategoria=${idCategoria}` :
                        'realizar_ventas.php?action=getProductos';

                    const response = await fetch(url);
                    const data = await response.json();

                    if (data.success) {
                        this.mostrarProductos(data.productos);
                    }
                } catch (error) {
                    console.error('Error al cargar productos:', error);
                }
            }

            async cargarUnidadesVenta(idProducto) {
                try {
                    const response = await fetch(`realizar_ventas.php?action=getUnidadesVenta&id_Producto=${idProducto}`);
                    const data = await response.json();

                    if (data.success) {
                        return data.unidades;
                    }
                    return null;
                } catch (error) {
                    console.error('Error al cargar unidades de venta:', error);
                    return null;
                }
            }

            async verificarExistenciaConversion(idDetallecompra, cantidad, factorConversion) {
                try {
                    const response = await fetch(`realizar_ventas.php?action=verificarExistenciaConversion&id_Detallecompra=${idDetallecompra}&cantidad=${cantidad}&factor_conversion=${factorConversion}`);
                    const data = await response.json();

                    return data;
                } catch (error) {
                    console.error('Error al verificar existencia:', error);
                    return { success: false, message: 'Error al verificar existencia' };
                }
            }

            async actualizarDisponibilidadPorUnidad() {
                const producto = this.productoSeleccionado;
                const selectorUnidad = document.getElementById('modalUnidadVenta');
                const factorConversion = parseFloat(selectorUnidad.options[selectorUnidad.selectedIndex].getAttribute('data-factor'));
                const cantidadInput = document.getElementById('modalCantidad');
                const existenciaElement = document.getElementById('modalExistencia');

                if (factorConversion !== 1) {
                    // Calcular y mostrar la existencia en la unidad de venta seleccionada
                    const existenciaEnVenta = producto.existencia * factorConversion;
                    const unidadVentaNombre = selectorUnidad.options[selectorUnidad.selectedIndex].text.split(' ')[0];

                    existenciaElement.value = `${existenciaEnVenta.toFixed(2)} ${unidadVentaNombre} (equivale a ${producto.existencia} ${producto.simbolo})`;

                    // Actualizar el máximo permitido
                    cantidadInput.max = Math.floor(existenciaEnVenta * 100) / 100; // Permitir decimales
                } else {
                    // Volver a mostrar la existencia normal
                    existenciaElement.value = `${producto.existencia} ${producto.simbolo}`;
                    cantidadInput.removeAttribute('max');
                }
            }

            mostrarProductos(productos) {
                const container = document.getElementById('productosContainer');
                container.innerHTML = '';

                if (productos.length === 0) {
                    container.innerHTML = `
            <div class="col-12 text-center text-muted py-4">
                <i class="fas fa-box-open fa-3x mb-3"></i>
                <p>No hay productos disponibles</p>
            </div>`;
                    return;
                }

                productos.forEach(producto => {
                    const card = document.createElement('div');
                    card.className = 'col-md-6 col-lg-4';
                    card.innerHTML = `
            <div class="product-card p-2 shadow-sm rounded" data-producto='${JSON.stringify(producto)}'>
                <div class="text-center mb-2">
                    ${producto.imagen
                            ? `<img src="${producto.imagen}" alt="${producto.nombre_producto}" class="img-fluid rounded" style="max-height:120px; object-fit:contain;">`
                            : `<div class="bg-light text-muted d-flex align-items-center justify-content-center rounded" style="height:120px;">
                                 <i class="fas fa-image fa-2x"></i>
                               </div>`
                        }
                </div>
                <h6 class="mb-1">${producto.nombre_producto}</h6>
                <p class="text-muted small mb-2">${producto.descripcion || 'Sin descripción'}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-secondary">${producto.categoria}</span>
                    <span class="badge bg-info stock-badge">
                        Stock: ${producto.existencia} ${producto.simbolo}
                    </span>
                </div>
                <div class="mt-2">
                    <small class="text-muted">Proveedor: ${producto.proveedor}</small>
                </div>
                <div class="mt-2">
                    <small class="text-success">Precio compra: $${parseFloat(producto.precio_unitario).toFixed(2)}</small>
                </div>
            </div>`;
                    container.appendChild(card);
                });

                // Agregar event listeners a las cards
                container.querySelectorAll('.product-card').forEach(card => {
                    card.addEventListener('click', () => this.seleccionarProducto(card));
                });
            }


            filtrarProductos() {
                const termino = document.getElementById('buscadorProductos').value.toLowerCase();
                const cards = document.querySelectorAll('#productosContainer .product-card');

                cards.forEach(card => {
                    const data = JSON.parse(card.getAttribute('data-producto'));
                    const nombre = data.nombre_producto.toLowerCase();
                    const descripcion = (data.descripcion || '').toLowerCase();
                    const coincide = nombre.includes(termino) || descripcion.includes(termino);

                    card.parentElement.style.display = coincide ? '' : 'none';
                });
            }


            seleccionarProducto(card) {
                this.productoSeleccionado = JSON.parse(card.getAttribute('data-producto'));
                this.mostrarModalProducto();
            }

            async mostrarModalProducto() {
                const producto = this.productoSeleccionado;

                document.getElementById('modalProductoNombre').value = producto.nombre_producto;
                document.getElementById('modalProductoProveedor').value = producto.proveedor;
                document.getElementById('modalPrecioCompra').value = `$${parseFloat(producto.precio_unitario).toFixed(2)}`;
                document.getElementById('modalExistencia').value = `${producto.existencia} ${producto.simbolo}`;

                // Cargar unidades de venta disponibles
                const unidadesData = await this.cargarUnidadesVenta(producto.id_Producto);

                // Crear selector de unidad de venta
                // Limpiar y rellenar el selector existente
                const selectUnidad = document.getElementById('modalUnidadVenta');
                selectUnidad.innerHTML = ''; // 🧹 Limpia las opciones previas

                // Opción base
                selectUnidad.innerHTML += `<option value="base" data-factor="1">${producto.unidad_medida} (${producto.simbolo}) - Unidad Base</option>`;

                // Otras unidades
                if (unidadesData && unidadesData.conversiones && unidadesData.conversiones.length > 0) {
                    unidadesData.conversiones.forEach(conversion => {
                        // Usar los datos de la base de datos
                        const factor = parseFloat(conversion.factor_conversion);
                        const nombreDisplay = `${conversion.unidad_venta} (${factor} ${conversion.simbolo_venta} = 1 ${unidadesData.unidad_base.simbolo_base})`;

                        selectUnidad.innerHTML += `<option value="${conversion.id_Unidad_Venta}" data-factor="${factor}">${nombreDisplay}</option>`;
                    });
                }

                // Actualizar el evento para recalcular cuando cambie la unidad
                document.getElementById('modalUnidadVenta').addEventListener('change', () => {
                    this.actualizarDisponibilidadPorUnidad();
                });
                // Precio de venta sugerido
                const precioSugerido = 0;
                document.getElementById('modalPrecioVenta').value = precioSugerido.toFixed(2);

                document.getElementById('modalCantidad').value = 1;

                this.calcularSubtotalModal();

                const modal = new bootstrap.Modal(document.getElementById('modalProducto'));
                modal.show();
                this.actualizarDisponibilidadPorUnidad();
            }

            calcularSubtotalModal() {
                const precio = parseFloat(document.getElementById('modalPrecioVenta').value) || 0;
                const cantidad = parseInt(document.getElementById('modalCantidad').value) || 0;
                const subtotal = precio * cantidad;

                document.getElementById('modalSubtotal').value = `$${subtotal.toFixed(2)}`;
            }

            async agregarAlCarrito() {
                const producto = this.productoSeleccionado;
                const precioVenta = parseFloat(document.getElementById('modalPrecioVenta').value);
                const cantidad = parseInt(document.getElementById('modalCantidad').value);
                const selectorUnidad = document.getElementById('modalUnidadVenta');
                const factorConversion = parseFloat(selectorUnidad.options[selectorUnidad.selectedIndex].getAttribute('data-factor'));
                const usarConversion = factorConversion !== 1;

                // 🔹 Validaciones con SweetAlert
                if (!precioVenta || precioVenta <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Precio inválido',
                        text: 'El precio de venta debe ser mayor a 0.'
                    });
                    return;
                }

                if (!cantidad || cantidad <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cantidad inválida',
                        text: 'Debe ingresar una cantidad mayor a 0.'
                    });
                    return;
                }

                // 🔹 Verificar existencia
                if (usarConversion) {
                    const verificacion = await this.verificarExistenciaConversion(
                        producto.id_Detallecompra, cantidad, factorConversion
                    );

                    if (!verificacion.success || !verificacion.resultado.suficiente) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Existencia insuficiente',
                            text: verificacion.resultado.mensaje || 'No hay suficiente existencia.'
                        });
                        return;
                    }
                } else {
                    if (cantidad > producto.existencia) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Existencia insuficiente',
                            text: 'No hay suficiente existencia para esta venta.'
                        });
                        return;
                    }
                }

                // 🔹 Agregar producto al carrito
                const unidadVentaNombre = selectorUnidad.options[selectorUnidad.selectedIndex].text.split(' - ')[0];
                const index = this.carrito.findIndex(item =>
                    item.id_Detallecompra === producto.id_Detallecompra &&
                    item.unidad_venta === unidadVentaNombre
                );

                if (index !== -1) {
                    this.carrito[index].cantidad += cantidad;
                    this.carrito[index].total = this.carrito[index].precio_venta * this.carrito[index].cantidad;
                } else {
                    this.carrito.push({
                        id_Detallecompra: producto.id_Detallecompra,
                        nombre_producto: producto.nombre_producto,
                        proveedor: producto.proveedor,
                        unidad_medida: producto.simbolo,
                        unidad_venta: unidadVentaNombre,
                        precio_venta: precioVenta,
                        cantidad: cantidad,
                        total: precioVenta * cantidad,
                        usar_conversion: usarConversion,
                        factor_conversion: factorConversion
                    });
                }

                this.actualizarCarrito();
                bootstrap.Modal.getInstance(document.getElementById('modalProducto')).hide();

                // 🔹 Éxito con SweetAlert
                Swal.fire({
                    icon: 'success',
                    title: 'Producto agregado',
                    text: `${producto.nombre_producto} fue agregado al carrito.`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }

            actualizarCarrito() {
                const carritoVacio = document.getElementById('carritoVacio');
                const carritoItems = document.getElementById('carritoItems');
                const resumenVenta = document.getElementById('resumenVenta');
                const totalVenta = document.getElementById('totalVenta');

                carritoItems.innerHTML = ''; // 🧹 Limpia todo
                let totalGeneral = 0;

                if (this.carrito.length === 0) {
                    carritoVacio.style.display = 'block';
                    carritoItems.style.display = 'none';
                    resumenVenta.style.display = 'none';
                    totalVenta.textContent = '$0.00';
                    document.getElementById('btnRealizarVenta').disabled = true;
                    return;
                }

                carritoVacio.style.display = 'none';
                carritoItems.style.display = 'block';
                resumenVenta.style.display = 'block';
                document.getElementById('btnRealizarVenta').disabled = false;

                this.carrito.forEach((item, index) => {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'carrito-item border-bottom py-2';

                    itemDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${item.nombre_producto}</strong><br>
                    <small>${item.proveedor}</small><br>
                    <small>${item.unidad_venta}</small>
                </div>
                <div class="text-end">
                    <div><strong>${item.cantidad}</strong> × $${item.precio_venta.toFixed(2)}</div>
                    <div class="text-success fw-bold">$${item.total.toFixed(2)}</div>
                    <button class="btn btn-danger btn-sm mt-1" onclick="ventaManager.eliminarDelCarrito(${index})">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

                    carritoItems.appendChild(itemDiv);
                    totalGeneral += item.total;
                });

                totalVenta.textContent = `$${totalGeneral.toFixed(2)}`;
            }


            eliminarDelCarrito(index) {
                const producto = this.carrito[index];

                Swal.fire({
                    title: '¿Eliminar producto?',
                    text: `¿Deseas quitar "${producto.nombre_producto}" del carrito?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.carrito.splice(index, 1);
                        this.actualizarCarrito();

                        Swal.fire({
                            icon: 'success',
                            title: 'Producto eliminado',
                            text: `"${producto.nombre_producto}" fue quitado del carrito.`,
                            timer: 1200,
                            showConfirmButton: false
                        });
                    }
                });
            }
            async realizarVenta() {
                const cliente = document.getElementById('selectCliente').value;
                const fecha = document.getElementById('fechaVenta').value;

                if (!cliente) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cliente requerido',
                        text: 'Debe seleccionar un cliente antes de continuar.'
                    });
                    return;
                }

                if (this.carrito.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Carrito vacío',
                        text: 'Debe agregar productos al carrito antes de realizar la venta.'
                    });
                    return;
                }

                // 🔹 Confirmación antes de enviar
                const confirmacion = await Swal.fire({
                    icon: 'question',
                    title: '¿Confirmar venta?',
                    text: '¿Desea procesar esta venta?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, realizar venta',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#dc3545'
                });

                if (!confirmacion.isConfirmed) return;

                const datosVenta = {
                    action: 'crearVenta',
                    fecha: fecha,
                    id_Cliente: cliente,
                    detalles: JSON.stringify(this.carrito)
                };

                try {
                    const response = await fetch('realizar_ventas.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams(datosVenta)
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Venta realizada',
                            text: `Venta registrada con éxito.`,
                            confirmButtonColor: '#198754'
                        });
                        this.carrito = [];
                        this.actualizarCarrito();
                        this.cargarProductos();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al registrar venta',
                            text: data.message || 'Ocurrió un problema al guardar la venta.'
                        });
                    }
                } catch (error) {
                    console.error('Error al realizar venta:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor.'
                    });
                }
            }

            setupEventListeners() {
                // Categoría change
                document.getElementById('selectCategoria').addEventListener('change', (e) => {
                    this.cargarProductos(e.target.value);
                });

                // Modal events
                document.getElementById('modalPrecioVenta').addEventListener('input', () => {
                    this.calcularSubtotalModal();
                });

                document.getElementById('modalCantidad').addEventListener('input', () => {
                    this.calcularSubtotalModal();
                });

                document.getElementById('btnAgregarCarrito').addEventListener('click', () => {
                    this.agregarAlCarrito();
                });

                // Realizar venta
                document.getElementById('btnRealizarVenta').addEventListener('click', () => {
                    this.realizarVenta();
                });

                document.getElementById('buscadorProductos').addEventListener('input', () => {
                    this.filtrarProductos();
                });

            }
        }

        // Inicializar la aplicación
        const ventaManager = new VentaManager();
    </script>
</body>

</html>