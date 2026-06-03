document.addEventListener('DOMContentLoaded', () => {
    // Elementos comunes
    const modal = document.getElementById('modalConfig');
    const closeModal = modal.querySelector('.close');
    const form = document.getElementById('formConfig');
    const cancelBtn = document.getElementById('btnCancelarModal');
    const panelLateral = document.getElementById('panelLateral');
    const cerrarPanel = document.getElementById('cerrarPanel');
    
    let currentType = '';
    let currentId = null;
    let serviciosData = [], marcasData = [], modelosData = [];
    
    // Cerrar panel lateral
    cerrarPanel.addEventListener('click', () => panelLateral.classList.remove('open'));
    
    // Funciones de modal
    function cerrarModal() { modal.style.display = 'none'; }
    closeModal.addEventListener('click', cerrarModal);
    cancelBtn.addEventListener('click', cerrarModal);
    window.addEventListener('click', (e) => { if (e.target === modal) cerrarModal(); });
    
    // Tabs
    const tabs = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');
    tabs.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.tab;
            contents.forEach(c => c.classList.remove('active'));
            document.getElementById(`tab-${tabId}`).classList.add('active');
            tabs.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (tabId === 'servicios') { cargarServicios(); cargarEstadisticasServicios(); }
            else if (tabId === 'marcas') { cargarMarcas(); cargarEstadisticasMarcas(); }
            else if (tabId === 'modelos') { cargarModelos(); cargarEstadisticasModelos(); }
        });
    });
    
    // ==================== SERVICIOS ====================
    const btnNuevoServicio = document.getElementById('btnNuevoServicio');
    const searchServicios = document.getElementById('search-servicios');
    const btnExportarServicios = document.getElementById('btnExportarServiciosPDF');
    
    btnNuevoServicio.addEventListener('click', () => abrirFormulario('servicio'));
    searchServicios.addEventListener('input', () => filtrarServicios());
    btnExportarServicios.addEventListener('click', () => exportarPDF('servicios', 'tablaServicios', 'Reporte de Servicios'));
    
    async function cargarServicios() {
        showSkeleton('tbodyServicios', true);
        try {
            serviciosData = await apiRequest('servicios');
            renderizarServicios(serviciosData);
            actualizarContadoresServicios();
        } catch (error) {
            document.getElementById('tbodyServicios').innerHTML = '<tr><td colspan="7">Error al cargar</td></tr>';
        } finally {
            showSkeleton('tbodyServicios', false);
        }
    }
    
    function renderizarServicios(data) {
        const tbody = document.getElementById('tbodyServicios');
        if (!data.length) {
            tbody.innerHTML = '<tr><td colspan="7">No hay servicios registrados</td></tr>';
            return;
        }
        tbody.innerHTML = data.map(s => `
            <tr data-id="${s.idServicio}">
                <td>${s.idServicio}</td>
                <td>${escapeHtml(s.codigo)}</td>
                <td>${escapeHtml(s.nombre)}</td>
                <td>${escapeHtml(s.descripcion || '-')}</td>
                <td>$${parseFloat(s.precioUnitario).toFixed(2)}</td>
                <td><span class="status-badge ${s.estaActivo ? 'activo' : 'inactivo'}">${s.estaActivo ? 'Activo' : 'Inactivo'}</span></td>
                <td class="actions-cell">
                    <button class="btn-edit" data-id="${s.idServicio}"><i class="fas fa-edit"></i></button>
                    <button class="btn-delete" data-id="${s.idServicio}"><i class="fas fa-trash"></i></button>
                    <button class="btn-view" data-id="${s.idServicio}"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
        attachEventosTabla();
    }
    
    function filtrarServicios() {
        const term = searchServicios.value.toLowerCase();
        if (!term) return renderizarServicios(serviciosData);
        const filtrados = serviciosData.filter(s => 
            s.codigo.toLowerCase().includes(term) || 
            s.nombre.toLowerCase().includes(term)
        );
        renderizarServicios(filtrados);
    }
    
    async function cargarEstadisticasServicios() {
        try {
            const data = await apiRequest('servicios');
            const total = data.length;
            const activos = data.filter(s => s.estaActivo).length;
            const inactivos = total - activos;
            document.getElementById('total-servicios').innerText = total;
            document.getElementById('servicios-activos').innerText = activos;
            document.getElementById('servicios-inactivos').innerText = inactivos;
        } catch(e) {}
    }
    
    // ==================== MARCAS ====================
    const btnNuevaMarca = document.getElementById('btnNuevaMarca');
    const searchMarcas = document.getElementById('search-marcas');
    const btnExportarMarcas = document.getElementById('btnExportarMarcasPDF');
    
    btnNuevaMarca.addEventListener('click', () => abrirFormulario('marca'));
    searchMarcas.addEventListener('input', () => filtrarMarcas());
    btnExportarMarcas.addEventListener('click', () => exportarPDF('marcas', 'tablaMarcas', 'Reporte de Marcas'));
    
    async function cargarMarcas() {
        try {
            marcasData = await apiRequest('marcas-admin');
            renderizarMarcas(marcasData);
            document.getElementById('total-marcas').innerText = marcasData.length;
        } catch(e) {}
    }
    
    function renderizarMarcas(data) {
        const tbody = document.getElementById('tbodyMarcas');
        if (!data.length) {
            tbody.innerHTML = '<tr><td colspan="3">No hay marcas</td></tr>';
            return;
        }
        tbody.innerHTML = data.map(m => `
            <tr data-id="${m.idMarca}">
                <td>${m.idMarca}</td>
                <td>${escapeHtml(m.nombre_marca)}</td>
                <td class="actions-cell">
                    <button class="btn-edit" data-id="${m.idMarca}"><i class="fas fa-edit"></i></button>
                    <button class="btn-delete" data-id="${m.idMarca}"><i class="fas fa-trash"></i></button>
                    <button class="btn-view" data-id="${m.idMarca}"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
        attachEventosTabla();
    }
    
    function filtrarMarcas() {
        const term = searchMarcas.value.toLowerCase();
        if (!term) return renderizarMarcas(marcasData);
        const filtrados = marcasData.filter(m => m.nombre_marca.toLowerCase().includes(term));
        renderizarMarcas(filtrados);
    }
    
    async function cargarEstadisticasMarcas() {
        const data = await apiRequest('marcas-admin');
        document.getElementById('total-marcas').innerText = data.length;
    }
    
    // ==================== MODELOS ====================
    const btnNuevoModelo = document.getElementById('btnNuevoModelo');
    const searchModelos = document.getElementById('search-modelos');
    const btnExportarModelos = document.getElementById('btnExportarModelosPDF');
    
    btnNuevoModelo.addEventListener('click', () => abrirFormulario('modelo'));
    searchModelos.addEventListener('input', () => filtrarModelos());
    btnExportarModelos.addEventListener('click', () => exportarPDF('modelos', 'tablaModelos', 'Reporte de Modelos'));
    
    async function cargarModelos() {
        try {
            modelosData = await apiRequest('modelos-admin');
            renderizarModelos(modelosData);
            document.getElementById('total-modelos').innerText = modelosData.length;
        } catch(e) {}
    }
    
    function renderizarModelos(data) {
        const tbody = document.getElementById('tbodyModelos');
        if (!data.length) {
            tbody.innerHTML = '<tr><td colspan="4">No hay modelos</td></tr>';
            return;
        }
        tbody.innerHTML = data.map(m => `
            <tr data-id="${m.idModelo}">
                <td>${m.idModelo}</td>
                <td>${escapeHtml(m.nombre_marca || '')}</td>
                <td>${escapeHtml(m.nombre_modelo)}</td>
                <td class="actions-cell">
                    <button class="btn-edit" data-id="${m.idModelo}"><i class="fas fa-edit"></i></button>
                    <button class="btn-delete" data-id="${m.idModelo}"><i class="fas fa-trash"></i></button>
                    <button class="btn-view" data-id="${m.idModelo}"><i class="fas fa-eye"></i></button>
                </td>
            </tr>
        `).join('');
        attachEventosTabla();
    }
    
    function filtrarModelos() {
        const term = searchModelos.value.toLowerCase();
        if (!term) return renderizarModelos(modelosData);
        const filtrados = modelosData.filter(m => 
            (m.nombre_marca && m.nombre_marca.toLowerCase().includes(term)) ||
            m.nombre_modelo.toLowerCase().includes(term)
        );
        renderizarModelos(filtrados);
    }
    
    async function cargarEstadisticasModelos() {
        const data = await apiRequest('modelos-admin');
        document.getElementById('total-modelos').innerText = data.length;
    }
    
    // ==================== CRUD y Formularios ====================
    async function abrirFormulario(tipo, id = null) {
        currentType = tipo;
        currentId = id;
        const titleMap = { servicio: 'Servicio', marca: 'Marca', modelo: 'Modelo' };
        document.getElementById('modalConfigTitle').innerText = id ? `Editar ${titleMap[tipo]}` : `Nuevo ${titleMap[tipo]}`;
        
        let html = '';
        if (tipo === 'servicio') {
            html = `
                <div class="form-group"><label>Código *</label><input type="text" id="codigo" required></div>
                <div class="form-group"><label>Nombre *</label><input type="text" id="nombre" required></div>
                <div class="form-group"><label>Descripción</label><textarea id="descripcion"></textarea></div>
                <div class="form-group"><label>Precio Unitario *</label><input type="number" step="0.01" id="precioUnitario" required></div>
                <div class="form-group"><label>Estado</label><select id="estaActivo"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
            `;
            if (id) {
                const data = await apiRequest(`servicios/${id}`);
                document.getElementById('codigo').value = data.codigo;
                document.getElementById('nombre').value = data.nombre;
                document.getElementById('descripcion').value = data.descripcion || '';
                document.getElementById('precioUnitario').value = data.precioUnitario;
                document.getElementById('estaActivo').value = data.estaActivo ? '1' : '0';
            }
        } else if (tipo === 'marca') {
            html = `<div class="form-group"><label>Nombre de Marca *</label><input type="text" id="nombre_marca" required></div>`;
            if (id) {
                const data = await apiRequest(`marcas-admin/${id}`);
                document.getElementById('nombre_marca').value = data.nombre_marca;
            }
        } else if (tipo === 'modelo') {
            const marcas = await apiRequest('marcas');
            html = `
                <div class="form-group"><label>Marca *</label><select id="idMarca" required>${marcas.map(m => `<option value="${m.idMarca}">${escapeHtml(m.nombre_marca)}</option>`).join('')}</select></div>
                <div class="form-group"><label>Nombre del Modelo *</label><input type="text" id="nombre_modelo" required></div>
            `;
            if (id) {
                const data = await apiRequest(`modelos-admin/${id}`);
                document.getElementById('idMarca').value = data.idMarca;
                document.getElementById('nombre_modelo').value = data.nombre_modelo;
            }
        }
        document.getElementById('dynamicFields').innerHTML = html;
        modal.style.display = 'flex';
    }
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        let endpoint = '', method = 'POST', data = {};
        // clear potential inline errors
        const dynamicFields = document.getElementById('dynamicFields');
        dynamicFields.querySelectorAll('input, select, textarea').forEach(el => Validators.clearError(el));

        if (currentType === 'servicio') {
            const codigoEl = document.getElementById('codigo');
            const nombreEl = document.getElementById('nombre');
            const descripcionEl = document.getElementById('descripcion');
            const precioEl = document.getElementById('precioUnitario');
            const estadoEl = document.getElementById('estaActivo');
            if (!Validators.required(Validators.sanitize(codigoEl.value))) { Validators.showError(codigoEl, 'Código obligatorio'); codigoEl.focus(); return; }
            if (!Validators.required(Validators.sanitize(nombreEl.value))) { Validators.showError(nombreEl, 'Nombre obligatorio'); nombreEl.focus(); return; }
            if (!precioEl.value || isNaN(parseFloat(precioEl.value)) || parseFloat(precioEl.value) <= 0) { Validators.showError(precioEl, 'Precio unitario inválido'); precioEl.focus(); return; }
            endpoint = currentId ? `servicios/${currentId}` : 'servicios';
            method = currentId ? 'PUT' : 'POST';
            data = { codigo: Validators.sanitize(codigoEl.value), nombre: Validators.sanitize(nombreEl.value), descripcion: Validators.sanitize(descripcionEl.value || ''), precioUnitario: parseFloat(precioEl.value), estaActivo: parseInt(estadoEl.value) };
        } else if (currentType === 'marca') {
            const nombreEl = document.getElementById('nombre_marca');
            if (!Validators.required(Validators.sanitize(nombreEl.value))) { Validators.showError(nombreEl, 'Nombre de marca obligatorio'); nombreEl.focus(); return; }
            endpoint = currentId ? `marcas-admin/${currentId}` : 'marcas-admin';
            method = currentId ? 'PUT' : 'POST';
            data = { nombre_marca: Validators.sanitize(nombreEl.value) };
        } else if (currentType === 'modelo') {
            const idMarcaEl = document.getElementById('idMarca');
            const nombreEl = document.getElementById('nombre_modelo');
            if (!Validators.required(idMarcaEl.value)) { Validators.showError(idMarcaEl, 'Seleccione marca'); idMarcaEl.focus(); return; }
            if (!Validators.required(Validators.sanitize(nombreEl.value))) { Validators.showError(nombreEl, 'Nombre del modelo obligatorio'); nombreEl.focus(); return; }
            endpoint = currentId ? `modelos-admin/${currentId}` : 'modelos-admin';
            method = currentId ? 'PUT' : 'POST';
            data = { idMarca: parseInt(idMarcaEl.value), nombre_modelo: Validators.sanitize(nombreEl.value) };
        }
        try {
            await apiRequest(endpoint, { method, body: JSON.stringify(data) });
            Swal.fire('Éxito', 'Guardado correctamente', 'success');
            cerrarModal();
            // Recargar la pestaña actual
            const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
            if (activeTab === 'servicios') { cargarServicios(); cargarEstadisticasServicios(); }
            else if (activeTab === 'marcas') { cargarMarcas(); cargarEstadisticasMarcas(); }
            else if (activeTab === 'modelos') { cargarModelos(); cargarEstadisticasModelos(); }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });
    
    // ==================== Eventos de tabla (editar, eliminar, ver) ====================
    function attachEventosTabla() {
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
                if (activeTab === 'servicios') abrirFormulario('servicio', id);
                else if (activeTab === 'marcas') abrirFormulario('marca', id);
                else if (activeTab === 'modelos') abrirFormulario('modelo', id);
            });
        });
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
                eliminarItem(activeTab, id);
            });
        });
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
                verDetalle(activeTab, id);
            });
        });
        // Click en fila para ver detalle también
        document.querySelectorAll('#tbodyServicios tr, #tbodyMarcas tr, #tbodyModelos tr').forEach(row => {
            row.addEventListener('click', (e) => {
                if (!e.target.closest('.actions-cell')) {
                    const id = row.dataset.id;
                    const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
                    if (id) verDetalle(activeTab, id);
                }
            });
        });
    }
    
    async function eliminarItem(tipo, id) {
        let title = '', text = '', endpoint = '';
        if (tipo === 'servicios') {
            title = '¿Eliminar servicio?';
            text = 'Si tiene órdenes asociadas no se podrá eliminar';
            endpoint = `servicios/${id}`;
        } else if (tipo === 'marcas') {
            title = '¿Eliminar marca?';
            text = 'Si tiene modelos asociados no se podrá eliminar';
            endpoint = `marcas-admin/${id}`;
        } else {
            title = '¿Eliminar modelo?';
            text = 'Si hay vehículos con este modelo no se podrá eliminar';
            endpoint = `modelos-admin/${id}`;
        }
        const confirm = await Swal.fire({ title, text, icon: 'warning', showCancelButton: true });
        if (confirm.isConfirmed) {
            await apiRequest(endpoint, { method: 'DELETE' });
            Swal.fire('Eliminado', '', 'success');
            if (tipo === 'servicios') { cargarServicios(); cargarEstadisticasServicios(); }
            else if (tipo === 'marcas') { cargarMarcas(); cargarEstadisticasMarcas(); }
            else { cargarModelos(); cargarEstadisticasModelos(); }
        }
    }
    
    async function verDetalle(tipo, id) {
        panelLateral.classList.add('open');
        const contenido = document.getElementById('panelContenido');
        contenido.innerHTML = '<div class="loading">Cargando detalles...</div>';
        try {
            let data, html = '';
            if (tipo === 'servicios') {
                data = await apiRequest(`servicios/${id}`);
                html = `
                    <div class="detail-item"><strong>ID:</strong> ${data.idServicio}</div>
                    <div class="detail-item"><strong>Código:</strong> ${escapeHtml(data.codigo)}</div>
                    <div class="detail-item"><strong>Nombre:</strong> ${escapeHtml(data.nombre)}</div>
                    <div class="detail-item"><strong>Descripción:</strong> ${escapeHtml(data.descripcion || 'Sin descripción')}</div>
                    <div class="detail-item"><strong>Precio:</strong> $${parseFloat(data.precioUnitario).toFixed(2)}</div>
                    <div class="detail-item"><strong>Estado:</strong> <span class="status-badge ${data.estaActivo ? 'activo' : 'inactivo'}">${data.estaActivo ? 'Activo' : 'Inactivo'}</span></div>
                `;
            } else if (tipo === 'marcas') {
                data = await apiRequest(`marcas-admin/${id}`);
                html = `
                    <div class="detail-item"><strong>ID:</strong> ${data.idMarca}</div>
                    <div class="detail-item"><strong>Nombre:</strong> ${escapeHtml(data.nombre_marca)}</div>
                `;
            } else {
                data = await apiRequest(`modelos-admin/${id}`);
                const marcas = await apiRequest('marcas');
                const marcaNombre = marcas.find(m => m.idMarca == data.idMarca)?.nombre_marca || '';
                html = `
                    <div class="detail-item"><strong>ID:</strong> ${data.idModelo}</div>
                    <div class="detail-item"><strong>Marca:</strong> ${escapeHtml(marcaNombre)}</div>
                    <div class="detail-item"><strong>Modelo:</strong> ${escapeHtml(data.nombre_modelo)}</div>
                `;
            }
            contenido.innerHTML = html;
        } catch (error) {
            contenido.innerHTML = '<p>Error al cargar detalles</p>';
        }
    }
    
    // ==================== Exportación a PDF ====================
    async function exportarPDF(tipo, tablaId, titulo) {
        const tabla = document.getElementById(tablaId);
        if (!tabla) return;
        Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const cloneTable = tabla.cloneNode(true);
            // Eliminar columna de acciones
            cloneTable.querySelectorAll('.actions-cell, th:last-child').forEach(el => el.remove());
            const container = document.createElement('div');
            container.style.padding = '20px';
            container.style.background = 'white';
            const title = document.createElement('h2');
            title.innerText = titulo;
            title.style.textAlign = 'center';
            title.style.fontFamily = 'Inter, sans-serif';
            const date = document.createElement('p');
            date.innerText = `Generado: ${new Date().toLocaleString()}`;
            date.style.textAlign = 'center';
            date.style.color = '#666';
            container.appendChild(title);
            container.appendChild(date);
            container.appendChild(cloneTable);
            document.body.appendChild(container);
            const canvas = await html2canvas(container, { scale: 2, backgroundColor: '#ffffff' });
            document.body.removeChild(container);
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('landscape');
            const imgWidth = 280;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
            pdf.save(`${tipo}_${new Date().toISOString().slice(0,19)}.pdf`);
            Swal.fire('Éxito', 'PDF generado', 'success');
        } catch (error) {
            Swal.fire('Error', 'No se pudo generar PDF', 'error');
        }
    }
    
    // ==================== Utilidades ====================
    function showSkeleton(elementId, show) {
        const el = document.getElementById(elementId);
        if (el && show) {
            el.innerHTML = '<tr><td colspan="7" class="skeleton-row">Cargando...</td></tr>';
        }
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[m]));
    }
    
    function actualizarContadoresServicios() {
        // Ya se actualiza en estadísticas
    }
    
    // Carga inicial
    cargarServicios();
    cargarEstadisticasServicios();
    cargarMarcas();
    cargarEstadisticasMarcas();
    cargarModelos();
    cargarEstadisticasModelos();

    // ==================== CORREO (SMTP) ====================
    const btnGuardarCorreo = document.getElementById('btnGuardarCorreo');
    const btnProbarCorreo = document.getElementById('btnProbarCorreo');
    async function cargarCorreoConfig() {
        try {
            const res = await fetch(BASE_URL + 'api/settings.php');
            if (!res.ok) throw new Error('Error al cargar configuración');
            const data = await res.json();
            const s = data.smtp || {};
            document.getElementById('correoEnabled').value = s.enabled ? '1' : '0';
            document.getElementById('smtpHost').value = s.host || 'smtp.gmail.com';
            document.getElementById('smtpPort').value = s.port || 587;
            document.getElementById('smtpSecure').value = s.secure || 'tls';
            document.getElementById('smtpUser').value = s.username || '';
            // No rellenamos password real por seguridad
            document.getElementById('smtpPass').value = '';
            document.getElementById('smtpFrom').value = s.from || 'AutoGestión';
        } catch (e) {
            console.warn('No se pudo cargar configuración de correo', e);
        }
    }

    async function guardarCorreoConfig() {
        const hostEl = document.getElementById('smtpHost');
        const portEl = document.getElementById('smtpPort');
        const secureEl = document.getElementById('smtpSecure');
        const userEl = document.getElementById('smtpUser');
        const passEl = document.getElementById('smtpPass');
        const fromEl = document.getElementById('smtpFrom');
        const enabledEl = document.getElementById('correoEnabled');

        // limpiar errores previos
        [hostEl, portEl, secureEl, userEl, passEl, fromEl].forEach(el => Validators.clearError(el));

        const host = Validators.sanitize(hostEl.value || '');
        const port = parseInt(portEl.value, 10) || 0;
        const secure = secureEl.value;
        const username = Validators.sanitize(userEl.value || '');
        const passwordValue = passEl.value || '';
        const from = Validators.sanitize(fromEl.value || '');
        const enabled = enabledEl.value === '1';

        if (!Validators.required(host)) { Validators.showError(hostEl, 'Host SMTP requerido'); hostEl.focus(); return; }
        if (!port || !Validators.integer(String(port))) { Validators.showError(portEl, 'Puerto inválido'); portEl.focus(); return; }
        if (!Validators.required(username) || !Validators.email(username)) { Validators.showError(userEl, 'Email SMTP inválido'); userEl.focus(); return; }
        if (!Validators.required(from)) { Validators.showError(fromEl, 'Remitente requerido'); fromEl.focus(); return; }

        const payload = { host, port, secure, username, from, enabled };
        if (passwordValue) payload.password = passwordValue;
        try {
            Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const res = await fetch(BASE_URL + 'api/settings.php?action=save', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Error al guardar');
            Swal.fire('Éxito', 'Configuración guardada', 'success');
            // limpiar campo password por seguridad
            document.getElementById('smtpPass').value = '';
        } catch (e) {
            Swal.fire('Error', e.message || 'No se pudo guardar', 'error');
        }
    }

    async function probarCorreo() {
        try {
            Swal.fire({ title: 'Enviando prueba...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            // Recolectar datos (incluye password si se ingresó)
            const hostEl = document.getElementById('smtpHost');
            const portEl = document.getElementById('smtpPort');
            const userEl = document.getElementById('smtpUser');
            const passEl = document.getElementById('smtpPass');
            const fromEl = document.getElementById('smtpFrom');
            // limpiar errores
            [hostEl, portEl, userEl, passEl, fromEl].forEach(el => Validators.clearError(el));
            const smtp = {
                host: Validators.sanitize(hostEl.value || ''),
                port: parseInt(portEl.value, 10) || 0,
                secure: document.getElementById('smtpSecure').value,
                username: Validators.sanitize(userEl.value || ''),
                password: passEl.value || '',
                from: Validators.sanitize(fromEl.value || ''),
            };
            if (!Validators.required(smtp.host)) { Validators.showError(hostEl, 'Host requerido'); hostEl.focus(); Swal.close(); return; }
            if (!smtp.port || !Validators.integer(String(smtp.port))) { Validators.showError(portEl, 'Puerto inválido'); portEl.focus(); Swal.close(); return; }
            if (!Validators.required(smtp.username) || !Validators.email(smtp.username)) { Validators.showError(userEl, 'Email inválido'); userEl.focus(); Swal.close(); return; }
            if (!Validators.required(smtp.from)) { Validators.showError(fromEl, 'Remitente requerido'); fromEl.focus(); Swal.close(); return; }
            const res = await fetch(BASE_URL + 'api/settings.php?action=test', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ smtp }) });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Error al enviar prueba');
            Swal.fire('Éxito', json.message || 'Correo de prueba enviado', 'success');
            document.getElementById('smtpPass').value = '';
        } catch (e) {
            Swal.fire('Error', e.message || 'No se pudo enviar', 'error');
        }
    }

    // Eventos de botones
    if (btnGuardarCorreo) btnGuardarCorreo.addEventListener('click', guardarCorreoConfig);
    if (btnProbarCorreo) btnProbarCorreo.addEventListener('click', probarCorreo);
    // Cargar configuración al entrar en la pestaña correo
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (btn.dataset.tab === 'correo') cargarCorreoConfig();
        });
    });

    // Mostrar/ocultar contraseña
    const togglePass = document.getElementById('togglePassVisibility');
    const passInput = document.getElementById('smtpPass');
    if (togglePass && passInput) {
        togglePass.addEventListener('click', () => {
            const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passInput.setAttribute('type', type);
            const icon = togglePass.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    }

    // Sugerir puerto y encriptación para Gmail
    const hostInput = document.getElementById('smtpHost');
    if (hostInput) {
        hostInput.addEventListener('change', () => {
            if (hostInput.value.toLowerCase().includes('gmail.com')) {
                const portEl = document.getElementById('smtpPort');
                const secureEl = document.getElementById('smtpSecure');
                if (portEl) portEl.value = 587;
                if (secureEl) secureEl.value = 'tls';
            }
        });
    }
});