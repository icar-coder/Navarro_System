document.addEventListener('DOMContentLoaded', () => {
    
    let ordenEditando = null;
    let serviciosGlobal = []; // almacena lista de servicios disponibles

    // Elementos DOM
    const tbody = document.getElementById('tbodyOrdenes');
    const searchInput = document.getElementById('searchOrden');
    const estadoFilter = document.getElementById('filtroEstado');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const btnNueva = document.getElementById('btnNuevaOrden');
    const btnExportar = document.getElementById('btn-exportar-pdf');
    const modalOrden = document.getElementById('modalOrden');
    const modalEstado = document.getElementById('modalEstado');
    const closeModalOrden = modalOrden?.querySelector('.close');
    const closeModalEstado = modalEstado?.querySelector('.close-estado');
    const formOrden = document.getElementById('formOrden');
    const btnCancelarModal = document.getElementById('btnCancelarModal');
    const btnAgregarServicio = document.getElementById('btnAgregarServicio');
    const serviciosContainer = document.getElementById('serviciosContainer');
    const presupuestoTotalInput = document.getElementById('presupuesto_total');
    const idVehiculoSelect = document.getElementById('idVehiculo');

    let currentOrdenId = null; // para cambiar estado

    // Cargar órdenes al inicio
    cargarOrdenes();
    cargarVehiculos(); // para el select de vehículo
    cargarServicios(); // para los selects de servicios

    // Eventos de filtro
    searchInput?.addEventListener('input', cargarOrdenes);
    estadoFilter?.addEventListener('change', cargarOrdenes);
    btnLimpiar?.addEventListener('click', () => {
        searchInput.value = '';
        estadoFilter.value = 'todos';
        cargarOrdenes();
    });
    btnExportar?.addEventListener('click', exportarOrdenesPDF);

    // Modal nueva orden
    btnNueva?.addEventListener('click', () => {
        ordenEditando = null;
        document.getElementById('modalTitle').innerText = 'Nueva Orden de Trabajo';
        formOrden.reset();
        document.getElementById('idOT').value = '';
        // Limpiar servicios container, dejar una fila vacía
        serviciosContainer.innerHTML = '';
        agregarFilaServicio();
        recalcularTotal();
        modalOrden.style.display = 'flex';
    });

    // Cerrar modales
    const cerrarModal = (modal) => modal && (modal.style.display = 'none');
    closeModalOrden?.addEventListener('click', () => cerrarModal(modalOrden));
    closeModalEstado?.addEventListener('click', () => cerrarModal(modalEstado));
    btnCancelarModal?.addEventListener('click', () => cerrarModal(modalOrden));
    window.addEventListener('click', (e) => {
        if (e.target === modalOrden) cerrarModal(modalOrden);
        if (e.target === modalEstado) cerrarModal(modalEstado);
    });

    // Agregar fila de servicio dinámica
    btnAgregarServicio?.addEventListener('click', () => agregarFilaServicio());
// Funciones auxiliares faltantes
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function showLoading(elementId, show) {
    const el = document.getElementById(elementId);
    if (el) {
        if (show) {
            el.innerHTML = '<tr><td colspan="7" class="text-center">Cargando...</td></tr>';
        } else {
            el.innerHTML = '';
        }
    }
}

function showErrorInTable(elementId, message) {
    const el = document.getElementById(elementId);
    if (el) {
        el.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${escapeHtml(message)}</td></tr>`;
    }
}
    function agregarFilaServicio(servicioSeleccionado = null, cantidad = 1, precio = 0) {
        const div = document.createElement('div');
        div.className = 'servicio-row';
        div.innerHTML = `
            <select class="servicio-select" style="width:60%;">
                <option value="">Seleccione servicio</option>
                ${serviciosGlobal.map(s => `<option value="${s.idServicio}" data-precio="${s.precioUnitario}">${s.codigo} - ${s.nombre} ($${s.precioUnitario})</option>`).join('')}
            </select>
            <input type="number" class="servicio-cantidad" placeholder="Cant." value="${cantidad}" min="1" style="width:15%;">
            <input type="number" class="servicio-precio" placeholder="Precio" step="0.01" value="${precio}" style="width:20%;">
            <button type="button" class="btn-remove-servicio"><i class="fas fa-trash"></i></button>
        `;
        if (servicioSeleccionado) {
            const select = div.querySelector('.servicio-select');
            select.value = servicioSeleccionado;
            // disparar cambio para actualizar precio
            const event = new Event('change');
            select.dispatchEvent(event);
        }
        div.querySelector('.btn-remove-servicio').addEventListener('click', () => {
            div.remove();
            recalcularTotal();
        });
        div.querySelector('.servicio-select').addEventListener('change', (e) => {
            const selected = e.target.options[e.target.selectedIndex];
            const precioBase = selected.getAttribute('data-precio') || 0;
            const precioInput = div.querySelector('.servicio-precio');
            precioInput.value = precioBase;
            recalcularTotal();
        });
        div.querySelector('.servicio-cantidad').addEventListener('input', () => recalcularTotal());
        div.querySelector('.servicio-precio').addEventListener('input', () => recalcularTotal());
        serviciosContainer.appendChild(div);
        recalcularTotal();
    }

    function recalcularTotal() {
        let total = 0;
        document.querySelectorAll('.servicio-row').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.servicio-cantidad')?.value) || 0;
            const precio = parseFloat(row.querySelector('.servicio-precio')?.value) || 0;
            total += cantidad * precio;
        });
        presupuestoTotalInput.value = total.toFixed(2);
    }

    // Cargar vehículos para el select
    async function cargarVehiculos() {
        try {
            const vehiculos = await apiRequest('vehiculos');
            idVehiculoSelect.innerHTML = '<option value="">Seleccione un vehículo</option>';
            vehiculos.forEach(v => {
                idVehiculoSelect.innerHTML += `<option value="${v.idVehiculo}">${v.placa} - ${v.cliente_completo || 'Sin cliente'}</option>`;
            });
        } catch (error) {
            console.error('Error cargando vehículos:', error);
        }
    }

    // Cargar servicios disponibles
    async function cargarServicios() {
        try {
            serviciosGlobal = await apiRequest('servicios');
            // Actualizar los selects existentes si los hay
            document.querySelectorAll('.servicio-select').forEach(select => {
                const currentVal = select.value;
                select.innerHTML = '<option value="">Seleccione servicio</option>' + 
                    serviciosGlobal.map(s => `<option value="${s.idServicio}" data-precio="${s.precioUnitario}">${s.codigo} - ${s.nombre} ($${s.precioUnitario})</option>`).join('');
                if (currentVal) select.value = currentVal;
            });
        } catch (error) {
            console.error('Error cargando servicios:', error);
        }
    }

    // Cargar órdenes
async function cargarOrdenes() {
    showLoading('tbodyOrdenes', true);
    try {
        const search = searchInput?.value || '';
        const estado = estadoFilter?.value || 'todos';
        const queryParams = {};
        if (search) queryParams.search = search;
        if (estado !== 'todos') queryParams.estado = estado;
        const ordenes = await apiRequest('ordenes', queryParams);
        renderizarTabla(ordenes);
    } catch (error) {
        console.error(error);
        showErrorInTable('tbodyOrdenes', 'Error al cargar órdenes');
    }
}

    function renderizarTabla(ordenes) {
        if (!tbody) return;
        if (!ordenes.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay órdenes registradas</td></tr>';
            return;
        }
        tbody.innerHTML = '';
        ordenes.forEach(ot => {
            const row = tbody.insertRow();
            const estadoClass = getEstadoClass(ot.estado);
            row.innerHTML = `
                <td>${escapeHtml(ot.codigo_ot)}</td>
                <td>${escapeHtml(ot.cliente_completo)}</td>
                <td>${escapeHtml(ot.placa)}</td>
                <td>${new Date(ot.fechaCreacion).toLocaleDateString()}</td>
                <td><span class="status-badge ${estadoClass}">${ot.estado}</span></td>
                <td>$${parseFloat(ot.total_presupuesto || 0).toFixed(2)}</td>
                <td class="actions-cell">
                    <button class="btn-edit" data-id="${ot.idOT}" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-status" data-id="${ot.idOT}" title="Cambiar estado"><i class="fas fa-exchange-alt"></i></button>
                    <button class="btn-delete" data-id="${ot.idOT}" title="Eliminar"><i class="fas fa-trash"></i></button>
                </td>
            `;
        });
        // Eventos
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => editarOrden(btn.dataset.id));
        });
        document.querySelectorAll('.btn-status').forEach(btn => {
            btn.addEventListener('click', () => abrirModalEstado(btn.dataset.id));
        });
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => eliminarOrden(btn.dataset.id));
        });
    }

    function getEstadoClass(estado) {
        switch(estado) {
            case 'Recepcionada': return 'status-badge';
            case 'EnProceso': return 'status-badge inactivo'; // reutilizamos
            case 'Terminada': return 'status-badge';
            case 'Facturada': return 'status-badge';
            case 'Cancelada': return 'status-badge inactivo';
            default: return '';
        }
    }

    async function editarOrden(id) {
        try {
            const orden = await apiRequest(`ordenes/${id}`);
            ordenEditando = orden;
            document.getElementById('modalTitle').innerText = 'Editar Orden de Trabajo';
            document.getElementById('idOT').value = orden.idOT;
            idVehiculoSelect.value = orden.idVehiculo;
            document.getElementById('problemaReportado').value = orden.problemaReportado || '';
            document.getElementById('diagnostico').value = orden.diagnostico || '';
            document.getElementById('observacionesMecanico').value = orden.observacionesMecanico || '';
            // Limpiar servicios container y agregar los existentes
            serviciosContainer.innerHTML = '';
            if (orden.detalles && orden.detalles.length) {
                orden.detalles.forEach(det => {
                    agregarFilaServicio(det.idServicio, det.cantidad, det.precioAplicado);
                });
            } else {
                agregarFilaServicio();
            }
            recalcularTotal();
            modalOrden.style.display = 'flex';
        } catch (error) {
            Swal.fire('Error', 'No se pudo cargar la orden', 'error');
        }
    }

    function abrirModalEstado(id) {
        currentOrdenId = id;
        document.getElementById('nuevoEstado').value = '';
        document.getElementById('comentarioEstado').value = '';
        modalEstado.style.display = 'flex';
    }

    document.getElementById('btnConfirmarEstado')?.addEventListener('click', async () => {
        const nuevoEstado = document.getElementById('nuevoEstado').value;
        const comentario = document.getElementById('comentarioEstado').value;
        if (!nuevoEstado) {
            Swal.fire('Error', 'Debe seleccionar un estado', 'error');
            return;
        }
        try {
            await apiRequest(`ordenes/${currentOrdenId}/estado`, {
                method: 'PUT',
                body: JSON.stringify({ estado: nuevoEstado, comentario })
            });
            Swal.fire('Éxito', 'Estado actualizado', 'success');
            cerrarModal(modalEstado);
            cargarOrdenes();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });

    document.getElementById('btnCancelarEstado')?.addEventListener('click', () => cerrarModal(modalEstado));

    async function eliminarOrden(id) {
        const confirm = await Swal.fire({
            title: '¿Eliminar orden?',
            text: 'Solo se pueden eliminar órdenes en estado Recepcionada o Cancelada',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar'
        });
        if (!confirm.isConfirmed) return;
        try {
            await apiRequest(`ordenes/${id}`, { method: 'DELETE' });
            Swal.fire('Eliminada', 'Orden eliminada', 'success');
            cargarOrdenes();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function exportarOrdenesPDF() {
        Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const search = searchInput?.value.trim() || '';
            const estado = estadoFilter?.value || 'todos';
            const queryParams = {};
            if (search) queryParams.search = search;
            if (estado !== 'todos') queryParams.estado = estado;

            const ordenes = await apiRequest('ordenes', queryParams);
            const filtroResumen = `Estado: ${estado === 'todos' ? 'Todos' : estado}, Búsqueda: ${search || 'Ninguna'}`;
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            pdf.setFontSize(16);
            pdf.text('Reporte de Órdenes', 14, 16);
            pdf.setFontSize(10);
            pdf.text(filtroResumen, 14, 24);
            pdf.text(`Total de órdenes: ${ordenes.length}`, 14, 30);

            pdf.autoTable({
                startY: 38,
                head: [['Código', 'Cliente', 'Vehículo', 'Fecha', 'Estado', 'Total']],
                body: ordenes.map(ot => [
                    ot.codigo_ot || '',
                    ot.cliente_completo || '',
                    ot.placa || '',
                    ot.fechaCreacion ? new Date(ot.fechaCreacion).toLocaleDateString() : '',
                    ot.estado || '',
                    `$${parseFloat(ot.total_presupuesto || ot.presupuesto_total || 0).toFixed(2)}`
                ]),
                theme: 'striped',
                styles: { fontSize: 8 }
            });

            pdf.save(`ordenes_${new Date().toISOString().slice(0,19)}.pdf`);
            Swal.fire('Éxito', 'PDF generado correctamente', 'success');
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'No se pudo generar el PDF', 'error');
        }
    }

    // Guardar orden (crear o editar)
    formOrden?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const idOT = document.getElementById('idOT').value;
        const idVehiculoEl = idVehiculoSelect;
        Validators.clearError(idVehiculoEl);
        if (!Validators.required(idVehiculoEl.value)) {
            Validators.showError(idVehiculoEl, 'Seleccione un vehículo'); idVehiculoEl.focus(); return;
        }
        // Recolectar servicios
        const detalles = [];
        let detalleIndex = 0;
        let firstDetalleError = null;
        document.querySelectorAll('.servicio-row').forEach(row => {
            detalleIndex++;
            const idServicio = row.querySelector('.servicio-select')?.value;
            const cantidadEl = row.querySelector('.servicio-cantidad');
            const precioEl = row.querySelector('.servicio-precio');
            const cantidad = parseInt(cantidadEl?.value) || 0;
            const precioAplicado = parseFloat(precioEl?.value) || 0;
            // limpiar errores de fila
            Validators.clearError(cantidadEl);
            Validators.clearError(precioEl);
            if (idServicio && cantidad > 0 && precioAplicado > 0) {
                detalles.push({ idServicio, cantidad, precioAplicado });
            } else if (idServicio) {
                if (cantidad <= 0) { Validators.showError(cantidadEl, 'Cantidad debe ser mayor a 0'); if (!firstDetalleError) firstDetalleError = cantidadEl; }
                if (precioAplicado <= 0) { Validators.showError(precioEl, 'Precio debe ser mayor a 0'); if (!firstDetalleError) firstDetalleError = precioEl; }
            }
        });
        if (detalles.length === 0) {
            Swal.fire('Error', 'Agregue al menos un servicio con cantidad y precio válidos', 'error');
            if (firstDetalleError) firstDetalleError.focus();
            return;
        }

        const problemaReportado = Validators.sanitize(document.getElementById('problemaReportado')?.value || '');
        const diagnostico = Validators.sanitize(document.getElementById('diagnostico')?.value || '');
        const observacionesMecanico = Validators.sanitize(document.getElementById('observacionesMecanico')?.value || '');
        const presupuesto_total = parseFloat(presupuestoTotalInput.value) || 0;

        const data = { idVehiculo: parseInt(idVehiculoEl.value), problemaReportado, diagnostico, observacionesMecanico, presupuesto_total, detalles };
        try {
            let endpoint = 'ordenes';
            let method = 'POST';
            if (idOT) { endpoint = `ordenes/${idOT}`; method = 'PUT'; }
            await apiRequest(endpoint, { method, body: JSON.stringify(data) });
            Swal.fire('Éxito', idOT ? 'Orden actualizada' : 'Orden creada', 'success');
            cerrarModal(modalOrden);
            cargarOrdenes();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });
});