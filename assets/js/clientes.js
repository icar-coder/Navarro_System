// assets/js/clientes.js

document.addEventListener('DOMContentLoaded', () => {
    // Elementos DOM
    const tablaBody = document.getElementById('tabla-clientes-body');
    const buscador = document.getElementById('buscar-cliente');
    const filtroEstado = document.getElementById('filtro-estado');
    const fechaInicio = document.getElementById('fecha-inicio');
    const fechaFin = document.getElementById('fecha-fin');
    const btnLimpiar = document.getElementById('btn-limpiar-filtros');
    const btnNuevo = document.getElementById('btn-nuevo-cliente');
    const btnExportar = document.getElementById('btn-exportar-pdf');
    const modal = document.getElementById('modal-cliente');
    const closeModal = modal?.querySelector('.close');
    const formCliente = document.getElementById('form-cliente');
    const modalTitle = document.getElementById('modal-title');
    const drawer = document.getElementById('cliente-drawer');
    const drawerOverlay = document.querySelector('.drawer-overlay');
    const drawerClose = document.querySelector('.drawer-close');
    const drawerBody = document.getElementById('drawer-body');

    // Estado
    let clientesOriginales = [];
    let clienteEditando = null;
    let sortField = 'identificacion';
    let sortOrder = 'asc';
    let clienteActualId = null;

    // Cargar datos al inicio
    cargarClientes();
    cargarEstadisticas();

    // Eventos de filtro
    buscador?.addEventListener('input', aplicarFiltrosYOrden);
    filtroEstado?.addEventListener('change', aplicarFiltrosYOrden);
    fechaInicio?.addEventListener('change', aplicarFiltrosYOrden);
    fechaFin?.addEventListener('change', aplicarFiltrosYOrden);
    btnLimpiar?.addEventListener('click', () => {
        if (buscador) buscador.value = '';
        if (filtroEstado) filtroEstado.value = 'todos';
        if (fechaInicio) fechaInicio.value = '';
        if (fechaFin) fechaFin.value = '';
        aplicarFiltrosYOrden();
    });

    // Ordenamiento
    document.querySelectorAll('.sort-header').forEach(header => {
        header.addEventListener('click', () => {
            const field = header.dataset.sort;
            if (!field) return;
            if (sortField === field) {
                sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                sortField = field;
                sortOrder = 'asc';
            }
            document.querySelectorAll('.sort-header').forEach(h => h.classList.remove('active', 'asc', 'desc'));
            header.classList.add('active', sortOrder);
            aplicarFiltrosYOrden();
        });
    });

    // Modal nuevo cliente
    btnNuevo?.addEventListener('click', () => {
        clienteEditando = null;
        modalTitle.innerText = 'Registrar Cliente';
        formCliente.reset();
        const cedulaInput = document.getElementById('cliente-cedula');
        if (cedulaInput) cedulaInput.removeAttribute('readonly');
        modal.style.display = 'flex';
    });

    // Exportar PDF
    btnExportar?.addEventListener('click', exportarClientesPDF);

    // Cerrar modal
    const cerrarModal = () => modal && (modal.style.display = 'none');
    closeModal?.addEventListener('click', cerrarModal);
    window.addEventListener('click', (e) => { if (e.target === modal) cerrarModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal?.style.display === 'flex') cerrarModal(); });

    // Drawer
    const cerrarDrawer = () => {
        drawer?.classList.remove('open');
        clienteActualId = null;
    };
    drawerOverlay?.addEventListener('click', cerrarDrawer);
    drawerClose?.addEventListener('click', cerrarDrawer);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && drawer?.classList.contains('open')) cerrarDrawer(); });

    // Envío del formulario
    formCliente?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const cedEl = document.getElementById('cliente-cedula');
        const nombreEl = document.getElementById('cliente-nombre');
        const apellidoEl = document.getElementById('cliente-apellido');
        const telefonoEl = document.getElementById('cliente-telefono');
        const emailEl = document.getElementById('cliente-email');
        const direccionEl = document.getElementById('cliente-direccion');

        // limpiar errores previos
        [cedEl, nombreEl, apellidoEl, telefonoEl, emailEl, direccionEl].forEach(el => Validators.clearError(el));

        const identificacion = Validators.sanitize(cedEl?.value || '');
        const nombre = Validators.sanitize(nombreEl?.value || '');
        const apellido = Validators.sanitize(apellidoEl?.value || '');
        const telefono = Validators.sanitize(telefonoEl?.value || '');
        const email = Validators.sanitize(emailEl?.value || '');
        const direccion = Validators.sanitize(direccionEl?.value || '');

        if (!Validators.required(identificacion)) { Validators.showError(cedEl, 'Cédula obligatoria'); cedEl.focus(); return; }
        if (!Validators.required(nombre)) { Validators.showError(nombreEl, 'Nombre obligatorio'); nombreEl.focus(); return; }
        if (!Validators.required(apellido)) { Validators.showError(apellidoEl, 'Apellido obligatorio'); apellidoEl.focus(); return; }
        if (email && !Validators.email(email)) { Validators.showError(emailEl, 'Email inválido'); emailEl.focus(); return; }
        if (telefono && !Validators.phone(telefono)) { Validators.showError(telefonoEl, 'Teléfono inválido'); telefonoEl.focus(); return; }

        const clienteData = { identificacion, nombre, apellido, telefono, email, direccion };

        try {
            let endpoint = 'clientes';
            let method = 'POST';
            if (clienteEditando) {
                endpoint = `clientes/${clienteEditando.idCliente}`;
                method = 'PUT';
            }
            await apiRequest(endpoint, { method, body: JSON.stringify(clienteData) });
            Swal.fire('Éxito', clienteEditando ? 'Cliente actualizado' : 'Cliente creado', 'success');
            cerrarModal();
            cargarClientes();
            cargarEstadisticas();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });

    // Cargar estadísticas
    async function cargarEstadisticas() {
        try {
            const clientes = await apiRequest('clientes');
            const total = clientes.length;
            const activos = clientes.filter(c => c.estado === 'activo').length;
            document.getElementById('stat-total-clientes').innerText = total;
            document.getElementById('stat-clientes-activos').innerText = activos;

            // Estadísticas adicionales (vehículos y órdenes) - opcional, requiere endpoints extra
            // Si no tienes esos endpoints, puedes omitir o mostrar 0
            try {
                const vehiculos = await apiRequest('vehiculos');
                document.getElementById('stat-total-vehiculos').innerText = vehiculos.length;
            } catch (e) { document.getElementById('stat-total-vehiculos').innerText = '0'; }
            try {
                const ordenes = await apiRequest('ordenes');
                document.getElementById('stat-total-ordenes').innerText = ordenes.length;
            } catch (e) { document.getElementById('stat-total-ordenes').innerText = '0'; }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
        }
    }

    // Cargar clientes desde API
    async function cargarClientes() {
        mostrarSkeleton(true);
        try {
            clientesOriginales = await apiRequest('clientes');
            aplicarFiltrosYOrden();
        } catch (error) {
            console.error(error);
            showErrorInTable('tabla-clientes-body', 'Error al cargar clientes');
        } finally {
            mostrarSkeleton(false);
        }
    }

    function mostrarSkeleton(show) {
        if (!tablaBody) return;
        if (show && clientesOriginales.length === 0) {
            tablaBody.innerHTML = '<tr><td colspan="6"><div class="skeleton-row"><div class="skeleton-cell"></div><div class="skeleton-cell"></div><div class="skeleton-cell"></div><div class="skeleton-cell"></div><div class="skeleton-cell"></div><div class="skeleton-cell"></div></div></td></tr>';
        }
    }

    // Aplicar filtros y orden
    function aplicarFiltrosYOrden() {
        if (!clientesOriginales.length) return;
        let filtrados = [...clientesOriginales];
        const busqueda = buscador?.value.trim().toLowerCase() || '';
        const estadoFiltro = filtroEstado?.value || 'todos';
        const desde = fechaInicio?.value;
        const hasta = fechaFin?.value;

        if (busqueda) {
            filtrados = filtrados.filter(cli => 
                `${cli.identificacion} ${cli.nombre} ${cli.apellido} ${cli.telefono || ''} ${cli.email || ''}`.toLowerCase().includes(busqueda)
            );
        }
        if (estadoFiltro !== 'todos') {
            filtrados = filtrados.filter(cli => cli.estado === estadoFiltro);
        }
        if (desde) {
            filtrados = filtrados.filter(cli => cli.fechaRegistro >= desde);
        }
        if (hasta) {
            filtrados = filtrados.filter(cli => cli.fechaRegistro <= hasta);
        }

        // Ordenamiento local
        filtrados.sort((a, b) => {
            let aVal = a[sortField];
            let bVal = b[sortField];
            if (aVal === undefined || bVal === undefined) return 0;
            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }
            if (aVal < bVal) return sortOrder === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortOrder === 'asc' ? 1 : -1;
            return 0;
        });
        renderizarTabla(filtrados);
    }

    function renderizarTabla(clientes) {
        if (!tablaBody) return;
        if (!clientes.length) {
            tablaBody.innerHTML = '<tr><td colspan="6" class="text-center">No se encontraron clientes</td></tr>';
            return;
        }
        tablaBody.innerHTML = '';
        clientes.forEach(cliente => {
            const tr = document.createElement('tr');
            tr.style.cursor = 'pointer';
            tr.addEventListener('click', (e) => {
                // Evitar abrir drawer si se hizo clic en botones de acción
                if (e.target.closest('.btn-edit') || e.target.closest('.btn-delete')) return;
                abrirDrawer(cliente.idCliente);
            });
            const iniciales = (cliente.nombre[0] + cliente.apellido[0]).toUpperCase();
            const fechaRegistro = cliente.fechaRegistro ? new Date(cliente.fechaRegistro).toLocaleDateString('es-ES') : '—';
            const estadoClass = cliente.estado === 'activo' ? 'status-badge' : 'status-badge inactivo';
            const estadoTexto = cliente.estado === 'activo' ? 'Activo' : (cliente.estado === 'inactivo' ? 'Inactivo' : '—');
            tr.innerHTML = `
                <td class="id-cell">${escapeHtml(cliente.identificacion)}</td>
                <td>
                    <div class="user-info-table">
                        <div class="user-avatar-sm">${escapeHtml(iniciales)}</div>
                        <div class="user-details-table">
                            <span class="user-name-table">${escapeHtml(cliente.nombre)} ${escapeHtml(cliente.apellido)}</span>
                            <span class="user-email-table">${escapeHtml(cliente.email || 'Sin correo')}</span>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(cliente.telefono || 'N/A')}</td>
                <td><span class="${estadoClass}">${estadoTexto}</span></td>
                <td>${escapeHtml(fechaRegistro)}</td>
                <td class="actions-cell" onclick="event.stopPropagation()">
                    <button class="btn-edit" data-id="${cliente.idCliente}" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-delete" data-id="${cliente.idCliente}" title="Eliminar"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tablaBody.appendChild(tr);
        });

        // Eventos de edición/eliminación
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => editarCliente(btn.dataset.id));
        });
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => eliminarCliente(btn.dataset.id));
        });
    }

    // Abrir drawer con detalles
    async function abrirDrawer(idCliente) {
        if (clienteActualId === idCliente && drawer?.classList.contains('open')) {
            cerrarDrawer();
            return;
        }
        clienteActualId = idCliente;
        drawerBody.innerHTML = '<div class="skeleton-drawer"></div>';
        drawer?.classList.add('open');
        try {
            const cliente = await apiRequest(`clientes/${idCliente}`);
            // Cargar vehículos y órdenes (opcional, si tienes los endpoints)
            let vehiculos = [], ordenes = [];
            try {
                vehiculos = await apiRequest(`clientes/${idCliente}/vehiculos`);
            } catch(e) { console.warn('No se pudieron cargar vehículos', e); }
            try {
                ordenes = await apiRequest(`clientes/${idCliente}/ordenes`);
            } catch(e) { console.warn('No se pudieron cargar órdenes', e); }

            drawerBody.innerHTML = `
                <div class="drawer-section">
                    <h4><i class="fas fa-id-card"></i> Información Personal</h4>
                    <div class="info-row"><strong>Cédula:</strong> ${escapeHtml(cliente.identificacion)}</div>
                    <div class="info-row"><strong>Nombre:</strong> ${escapeHtml(cliente.nombre)} ${escapeHtml(cliente.apellido)}</div>
                    <div class="info-row"><strong>Teléfono:</strong> ${escapeHtml(cliente.telefono || 'N/A')}</div>
                    <div class="info-row"><strong>Email:</strong> ${escapeHtml(cliente.email || 'N/A')}</div>
                    <div class="info-row"><strong>Dirección:</strong> ${escapeHtml(cliente.direccion || 'N/A')}</div>
                    <div class="info-row"><strong>Registro:</strong> ${new Date(cliente.fechaRegistro).toLocaleDateString()}</div>
                </div>
                <div class="drawer-section">
                    <h4><i class="fas fa-car"></i> Vehículos (${vehiculos.length})</h4>
                    ${vehiculos.length ? `
                        <table class="mini-table">
                            <thead><tr><th>Placa</th><th>Marca</th><th>Modelo</th><th>Año</th></tr></thead>
                            <tbody>${vehiculos.map(v => `<tr><td>${escapeHtml(v.placa)}</td><td>${escapeHtml(v.nombre_marca || '-')}</td><td>${escapeHtml(v.nombre_modelo || '-')}</td><td>${v.anio || '-'}</td></tr>`).join('')}</tbody>
                        </table>
                    ` : '<p class="text-muted">No hay vehículos registrados.</p>'}
                </div>
                <div class="drawer-section">
                    <h4><i class="fas fa-clipboard-list"></i> Últimas Órdenes (${ordenes.length})</h4>
                    ${ordenes.length ? `
                        <table class="mini-table">
                            <thead><tr><th>Código</th><th>Fecha</th><th>Estado</th><th>Total</th></tr></thead>
                            <tbody>${ordenes.map(o => `<tr><td>${escapeHtml(o.codigo_ot)}</td><td>${new Date(o.fechaCreacion).toLocaleDateString()}</td><td>${escapeHtml(o.estado)}</td><td>$${parseFloat(o.presupuesto_total || 0).toFixed(2)}</td></tr>`).join('')}</tbody>
                        </table>
                    ` : '<p class="text-muted">No hay órdenes de trabajo.</p>'}
                </div>
            `;
        } catch (error) {
            drawerBody.innerHTML = '<div class="error-message">Error al cargar los detalles del cliente.</div>';
            console.error(error);
        }
    }

    // Exportar a PDF
    async function exportarClientesPDF() {
        Swal.fire({
            title: 'Generando PDF...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const busqueda = buscador?.value.trim() || '';
            const estadoFiltro = filtroEstado?.value || 'todos';
            const desde = fechaInicio?.value;
            const hasta = fechaFin?.value;

            const queryParams = {};
            if (busqueda) queryParams.search = busqueda;
            if (estadoFiltro && estadoFiltro !== 'todos') queryParams.estado = estadoFiltro;
            if (desde) queryParams.fechaInicio = desde;
            if (hasta) queryParams.fechaFin = hasta;

            const clientesFiltrados = await apiRequest('clientes', queryParams);

            const resumenActivo = clientesFiltrados.filter(c => c.estado === 'activo').length;
            const resumenInactivo = clientesFiltrados.filter(c => c.estado === 'inactivo').length;
            const filtroResumen = `Filtro: ${estadoFiltro === 'todos' ? 'Todos' : estadoFiltro}, Desde: ${desde || 'Todos'}, Hasta: ${hasta || 'Todos'}`;

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ unit: 'mm', format: 'a4' });
            pdf.setFontSize(16);
            pdf.text('Reporte de Clientes', 14, 16);
            pdf.setFontSize(10);
            pdf.text(filtroResumen, 14, 24);
            pdf.text(`Búsqueda: ${busqueda || 'Ninguna'}`, 14, 30);
            pdf.text(`Total clientes: ${clientesFiltrados.length}`, 14, 36);
            pdf.text(`Activos: ${resumenActivo} | Inactivos: ${resumenInactivo}`, 14, 42);

            pdf.autoTable({
                startY: 50,
                head: [['ID/Cédula', 'Nombre completo', 'Teléfono', 'Estado', 'Registro']],
                body: clientesFiltrados.map(cliente => [
                    cliente.identificacion || '',
                    `${cliente.nombre || ''} ${cliente.apellido || ''}`,
                    cliente.telefono || 'N/A',
                    cliente.estado || 'N/A',
                    cliente.fechaRegistro ? new Date(cliente.fechaRegistro).toLocaleDateString('es-ES') : 'N/A'
                ]),
                theme: 'striped',
                styles: { fontSize: 8 }
            });

            pdf.save(`clientes_${new Date().toISOString().slice(0, 19)}.pdf`);
            Swal.fire('Éxito', 'PDF generado correctamente', 'success');
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'No se pudo generar el PDF', 'error');
        }
    }
    // Editar y eliminar (igual que antes)
    window.editarCliente = async function(id) {
        try {
            const cliente = await apiRequest(`clientes/${id}`);
            clienteEditando = cliente;
            modalTitle.innerText = 'Editar Cliente';
            document.getElementById('cliente-cedula').value = cliente.identificacion;
            document.getElementById('cliente-nombre').value = cliente.nombre;
            document.getElementById('cliente-apellido').value = cliente.apellido;
            document.getElementById('cliente-telefono').value = cliente.telefono || '';
            document.getElementById('cliente-email').value = cliente.email || '';
            document.getElementById('cliente-direccion').value = cliente.direccion || '';
            document.getElementById('cliente-cedula').setAttribute('readonly', 'readonly');
            modal.style.display = 'flex';
        } catch (error) {
            Swal.fire('Error', 'No se pudo cargar el cliente', 'error');
        }
    };

    window.eliminarCliente = function(id) {
        Swal.fire({
            title: '¿Eliminar cliente?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await apiRequest(`clientes/${id}`, { method: 'DELETE' });
                    Swal.fire('Eliminado', 'Cliente eliminado', 'success');
                    cargarClientes();
                    cargarEstadisticas();
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }
        });
    };

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    window.cargarClientes = cargarClientes;
    window.cargarEstadisticas = cargarEstadisticas;
});