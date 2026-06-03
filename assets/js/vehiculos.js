// assets/js/vehiculos.js

let vehiculos = [];
let marcas = [];
let modelos = [];

document.addEventListener('DOMContentLoaded', () => {
    // Elementos DOM
    const tbody = document.getElementById('tbody-vehiculos');
    const searchInput = document.getElementById('search-input');
    const filtroMarca = document.getElementById('filtro-marca');
    const filtroEstado = document.getElementById('filtro-estado-vehiculo');
    const btnLimpiar = document.getElementById('btn-limpiar-filtros');
    const btnNuevo = document.getElementById('btn-nuevo-vehiculo');
    const modal = document.getElementById('modal-vehiculo');
    const closeModal = modal.querySelector('.close');
    const form = document.getElementById('form-vehiculo');
    const cancelModal = document.getElementById('btn-cancelar-modal');
    const btnExportar = document.getElementById('btn-exportar-pdf');
    const panelLateral = document.getElementById('panel-lateral');
    const cerrarPanel = document.getElementById('cerrar-panel');

    let vehiculosOriginales = [];
    let editandoId = null;
    let marcasMap = new Map(); // id -> nombre

    // Cargar datos iniciales
    cargarEstadisticas();
    cargarMarcas();
    cargarClientes();
    cargarVehiculos();

    // Eventos
    searchInput.addEventListener('input', aplicarFiltros);
    filtroMarca.addEventListener('change', aplicarFiltros);
    filtroEstado.addEventListener('change', aplicarFiltros);
    btnLimpiar.addEventListener('click', limpiarFiltros);
    btnNuevo.addEventListener('click', () => abrirModal());
    closeModal.addEventListener('click', cerrarModal);
    cancelModal.addEventListener('click', cerrarModal);
    window.addEventListener('click', (e) => { if (e.target === modal) cerrarModal(); });
    btnExportar.addEventListener('click', exportarPDF);
    cerrarPanel.addEventListener('click', () => panelLateral.classList.remove('open'));

    // Carga de catálogos
    async function cargarMarcas() {
        const marcas = await apiRequest('marcas');
        const selectMarca = document.getElementById('marca');
        const filtroMarcaSelect = document.getElementById('filtro-marca');
        marcas.forEach(m => {
            marcasMap.set(m.idMarca, m.nombre_marca);
            selectMarca.innerHTML += `<option value="${m.idMarca}">${m.nombre_marca}</option>`;
            filtroMarcaSelect.innerHTML += `<option value="${m.idMarca}">${m.nombre_marca}</option>`;
        });
        selectMarca.addEventListener('change', (e) => cargarModelos(e.target.value));
    }

    async function cargarModelos(idMarca) {
        const selectModelo = document.getElementById('modelo');
        if (!idMarca) {
            selectModelo.innerHTML = '<option value="">Primero seleccione marca</option>';
            return;
        }
        const modelos = await apiRequest('modelos', { marca: idMarca });
        selectModelo.innerHTML = '<option value="">Seleccione modelo</option>';
        modelos.forEach(m => {
            selectModelo.innerHTML += `<option value="${m.idModelo}">${m.nombre_modelo}</option>`;
        });
    }

    async function cargarClientes() {
        const clientes = await apiRequest('clientes');
        const selectCliente = document.getElementById('idCliente');
        selectCliente.innerHTML = '<option value="">Seleccione cliente *</option>';
        clientes.forEach(c => {
            selectCliente.innerHTML += `<option value="${c.idCliente}">${c.nombre} ${c.apellido}</option>`;
        });
    }

    async function cargarEstadisticas() {
        try {
            const stats = await apiRequest('vehiculos/estadisticas');
            document.getElementById('total-vehiculos').innerText = stats.total;
            document.getElementById('vehiculos-activos').innerText = stats.activos;
            document.getElementById('vehiculos-servicio').innerText = stats.conServicio;
        } catch (error) { console.error(error); }
    }

    async function cargarVehiculos() {
        showSkeleton(true);
        try {
            vehiculosOriginales = await apiRequest('vehiculos');
            aplicarFiltros();
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="8">Error al cargar vehículos</td></tr>';
        } finally {
            showSkeleton(false);
        }
    }

    function aplicarFiltros() {
        let filtrados = [...vehiculosOriginales];
        const busqueda = searchInput.value.trim().toLowerCase();
        const marcaId = filtroMarca.value;
        const estado = filtroEstado.value;

        if (busqueda) {
            filtrados = filtrados.filter(v => 
                v.placa.toLowerCase().includes(busqueda) ||
                (v.vin && v.vin.toLowerCase().includes(busqueda)) ||
                (v.cliente_completo && v.cliente_completo.toLowerCase().includes(busqueda))
            );
        }
        if (marcaId) {
            filtrados = filtrados.filter(v => v.idMarca == marcaId);
        }
        if (estado !== 'todos') {
            filtrados = filtrados.filter(v => v.estado === estado);
        }
        renderizarTabla(filtrados);
    }

    function limpiarFiltros() {
        searchInput.value = '';
        filtroMarca.value = '';
        filtroEstado.value = 'todos';
        aplicarFiltros();
    }

    function renderizarTabla(vehiculos) {
        if (!vehiculos.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No se encontraron vehículos</td></tr>';
            return;
        }
        tbody.innerHTML = '';
        vehiculos.forEach(v => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-id', v.idVehiculo);
            tr.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-edit') && !e.target.closest('.btn-delete'))
                    abrirPanel(v.idVehiculo);
            });
            tr.innerHTML = `
                <td><strong>${escapeHtml(v.placa)}</strong></td>
                <td>${escapeHtml(v.cliente_completo || v.idCliente)}</td>
                <td>${escapeHtml(v.nombre_marca || '')}</td>
                <td>${escapeHtml(v.nombre_modelo || '')}</td>
                <td>${v.anio || '-'}</td>
                <td>${v.kilometraje ? v.kilometraje.toLocaleString() : '-'}</td>
                <td><span class="status-badge ${v.estado === 'activo' ? 'activo' : 'inactivo'}">${v.estado === 'activo' ? 'Activo' : 'Inactivo'}</span></td>
                <td class="actions-cell">
                    <button class="btn-edit" data-id="${v.idVehiculo}"><i class="fas fa-edit"></i></button>
                    <button class="btn-delete" data-id="${v.idVehiculo}"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        // Eventos edición/eliminación
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                editarVehiculo(btn.dataset.id);
            });
        });
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                eliminarVehiculo(btn.dataset.id);
            });
        });
    }

    // Panel lateral
    async function abrirPanel(id) {
        panelLateral.classList.add('open');
        const contenido = document.getElementById('panel-contenido');
        contenido.innerHTML = '<div class="loading">Cargando detalles...</div>';
        try {
            const vehiculo = await apiRequest(`vehiculos/${id}`);
            const ordenes = await apiRequest(`vehiculos/${id}/ordenes`);
            let citasHtml = '';
            try {
                const citas = await apiRequest(`vehiculos/${id}/citas`);
                if (citas.length) {
                    citasHtml = `<div class="subseccion"><h4>📅 Próximas citas</h4>
                        <table><tr><th>Fecha</th><th>Motivo</th><th>Estado</th></tr>
                        ${citas.map(c => `<tr><td>${new Date(c.fechaHora).toLocaleString()}</td><td>${escapeHtml(c.motivo || '')}</td><td>${c.estado}</td></tr>`).join('')}
                        </table></div>`;
                }
            } catch(e) { citasHtml = ''; }
            contenido.innerHTML = `
                <div class="vehiculo-info">
                    <p><strong>Placa:</strong> ${escapeHtml(vehiculo.placa)}</p>
                    <p><strong>Cliente:</strong> ${escapeHtml(vehiculo.cliente_completo || vehiculo.idCliente)}</p>
                    <p><strong>Marca/Modelo:</strong> ${escapeHtml(vehiculo.nombre_marca)} ${escapeHtml(vehiculo.nombre_modelo)}</p>
                    <p><strong>Año:</strong> ${vehiculo.anio || '-'}</p>
                    <p><strong>VIN:</strong> ${vehiculo.vin || '-'}</p>
                    <p><strong>Kilometraje:</strong> ${vehiculo.kilometraje ? vehiculo.kilometraje.toLocaleString() : '-'}</p>
                    <p><strong>Último servicio:</strong> ${vehiculo.fechaUltimoServicio || '-'}</p>
                    <p><strong>Estado:</strong> <span class="status-badge ${vehiculo.estado === 'activo' ? 'activo' : 'inactivo'}">${vehiculo.estado === 'activo' ? 'Activo' : 'Inactivo'}</span></p>
                </div>
                <div class="subseccion">
                    <h4>🔧 Órdenes de trabajo</h4>
                    ${ordenes.length ? `<table>
                        <tr><th>Código</th><th>Fecha</th><th>Estado</th><th>Total</th></tr>
                        ${ordenes.map(ot => `<tr><td>${ot.codigo_ot}</td><td>${new Date(ot.fechaCreacion).toLocaleDateString()}</td><td>${ot.estado}</td><td>$${ot.presupuesto_total}</td></tr>`).join('')}
                    </table>` : '<p>Sin órdenes registradas</p>'}
                </div>
                ${citasHtml}
            `;
        } catch (error) {
            contenido.innerHTML = '<p>Error al cargar detalles</p>';
        }
    }

    // CRUD
    async function abrirModal(vehiculo = null) {
        editandoId = vehiculo ? vehiculo.idVehiculo : null;
        document.getElementById('modal-title').innerText = vehiculo ? 'Editar Vehículo' : 'Registrar Vehículo';
        form.reset();
        if (vehiculo) {
            document.getElementById('idVehiculo').value = vehiculo.idVehiculo;
            document.getElementById('placa').value = vehiculo.placa;
            document.getElementById('idCliente').value = vehiculo.idCliente;
            document.getElementById('marca').value = vehiculo.idMarca;
            await cargarModelos(vehiculo.idMarca);
            setTimeout(() => {
                document.getElementById('modelo').value = vehiculo.idModelo;
            }, 200);
            document.getElementById('anio').value = vehiculo.anio;
            document.getElementById('vin').value = vehiculo.vin;
            document.getElementById('kilometraje').value = vehiculo.kilometraje;
            document.getElementById('fechaUltimoServicio').value = vehiculo.fechaUltimoServicio;
            document.getElementById('estadoVehiculo').value = vehiculo.estado || 'activo';
        } else {
            document.getElementById('idVehiculo').value = '';
            document.getElementById('modelo').innerHTML = '<option value="">Primero seleccione marca</option>';
        }
        modal.style.display = 'flex';
    }

    async function editarVehiculo(id) {
        const vehiculo = await apiRequest(`vehiculos/${id}`);
        abrirModal(vehiculo);
    }

    async function eliminarVehiculo(id) {
        const result = await Swal.fire({
            title: '¿Eliminar vehículo?',
            text: 'Si tiene órdenes asociadas no se podrá eliminar',
            icon: 'warning',
            showCancelButton: true
        });
        if (result.isConfirmed) {
            await apiRequest(`vehiculos/${id}`, { method: 'DELETE' });
            Swal.fire('Eliminado', '', 'success');
            cargarVehiculos();
            cargarEstadisticas();
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const placaEl = document.getElementById('placa');
        const idClienteEl = document.getElementById('idCliente');
        const marcaEl = document.getElementById('marca');
        const modeloEl = document.getElementById('modelo');
        const anioEl = document.getElementById('anio');
        const vinEl = document.getElementById('vin');
        const kmEl = document.getElementById('kilometraje');
        const fechaEl = document.getElementById('fechaUltimoServicio');
        const estadoEl = document.getElementById('estadoVehiculo');

        // limpiar errores
        [placaEl, idClienteEl, marcaEl, modeloEl, anioEl, vinEl, kmEl, fechaEl, estadoEl].forEach(el => Validators.clearError(el));

        const placa = Validators.sanitize(placaEl?.value || '');
        const idCliente = idClienteEl?.value ? parseInt(idClienteEl.value) : null;
        const marca = marcaEl?.value ? parseInt(marcaEl.value) : null;
        const modelo = modeloEl?.value ? parseInt(modeloEl.value) : null;
        const anio = anioEl?.value ? anioEl.value : null;
        const vin = Validators.sanitize(vinEl?.value || null);
        const kilometraje = kmEl?.value ? kmEl.value : null;
        const fechaUltimoServicio = fechaEl?.value || null;
        const estado = estadoEl?.value || 'activo';

        if (!Validators.required(placa)) { Validators.showError(placaEl, 'Placa obligatoria'); placaEl.focus(); return; }
        if (!Validators.plate(placa)) { Validators.showError(placaEl, 'Placa inválida'); placaEl.focus(); return; }
        if (!idCliente || !Validators.integer(String(idCliente))) { Validators.showError(idClienteEl, 'Seleccione cliente válido'); idClienteEl.focus(); return; }
        if (!marca || !Validators.integer(String(marca))) { Validators.showError(marcaEl, 'Seleccione marca'); marcaEl.focus(); return; }
        if (!modelo || !Validators.integer(String(modelo))) { Validators.showError(modeloEl, 'Seleccione modelo'); modeloEl.focus(); return; }
        if (anio && !Validators.integer(String(anio))) { Validators.showError(anioEl, 'Año inválido'); anioEl.focus(); return; }
        if (kilometraje && !Validators.integer(String(kilometraje))) { Validators.showError(kmEl, 'Kilometraje inválido'); kmEl.focus(); return; }
        if (fechaUltimoServicio && !Validators.date(fechaUltimoServicio)) { Validators.showError(fechaEl, 'Fecha inválida'); fechaEl.focus(); return; }

        const data = { placa, idCliente, idMarca: marca, idModelo: modelo, anio: anio || null, vin: vin || null, kilometraje: kilometraje || null, fechaUltimoServicio: fechaUltimoServicio || null, estado };

        try {
            let endpoint = 'vehiculos';
            let method = 'POST';
            if (editandoId) {
                endpoint = `vehiculos/${editandoId}`;
                method = 'PUT';
            }
            await apiRequest(endpoint, { method, body: JSON.stringify(data) });
            Swal.fire('Éxito', editandoId ? 'Vehículo actualizado' : 'Vehículo registrado', 'success');
            cerrarModal();
            cargarVehiculos();
            cargarEstadisticas();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });

    // Exportar PDF
    async function exportarPDF() {
        Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const busqueda = searchInput.value.trim();
            const marcaId = filtroMarca.value;
            const estado = filtroEstado.value;

            const queryParams = {};
            if (busqueda) queryParams.search = busqueda;
            if (marcaId) queryParams.marca = marcaId;
            if (estado && estado !== 'todos') queryParams.estado = estado;

            const vehiculosFiltrados = await apiRequest('vehiculos', queryParams);
            const filtroResumen = `Marca: ${marcaId ? marcasMap.get(parseInt(marcaId)) || 'Desconocida' : 'Todas'}, Estado: ${estado}`;
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            pdf.setFontSize(16);
            pdf.text('Reporte de Vehículos', 14, 16);
            pdf.setFontSize(10);
            pdf.text(`Filtros: ${filtroResumen}`, 14, 24);
            pdf.text(`Búsqueda: ${busqueda || 'Ninguna'}`, 14, 30);
            pdf.text(`Total de vehículos: ${vehiculosFiltrados.length}`, 14, 36);

            pdf.autoTable({
                startY: 44,
                head: [['Placa', 'Cliente', 'Marca', 'Modelo', 'Año', 'Kilometraje', 'Estado', 'Último servicio']],
                body: vehiculosFiltrados.map(v => [
                    v.placa || '',
                    v.cliente_completo || 'N/A',
                    v.nombre_marca || 'N/A',
                    v.nombre_modelo || 'N/A',
                    v.anio || '-',
                    v.kilometraje ? Number(v.kilometraje).toLocaleString() : '-',
                    v.estado === 'activo' ? 'Activo' : 'Inactivo',
                    v.fechaUltimoServicio || '-'
                ]),
                theme: 'striped',
                styles: { fontSize: 8 }
            });

            pdf.save(`vehiculos_${new Date().toISOString().slice(0,19)}.pdf`);
            Swal.fire('Éxito', 'PDF generado', 'success');
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'No se pudo generar el PDF', 'error');
        }
    }

    function cerrarModal() { modal.style.display = 'none'; }
    function showSkeleton(show) { /* implementar si se desea skeleton */ }
    function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[m])); }
});