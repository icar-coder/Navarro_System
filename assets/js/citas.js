document.addEventListener('DOMContentLoaded', () => {
    let editandoId = null;
    const tbody = document.getElementById('tbodyCitas');
    const modal = document.getElementById('modalCita');
    const closeModal = modal.querySelector('.close');
    const form = document.getElementById('formCita');
    const btnNueva = document.getElementById('btnNuevaCita');
    const btnCancelar = document.getElementById('btnCancelarCita');
    const filtro = document.getElementById('filtroCitas');
    const btnRefrescar = document.getElementById('btnRefrescar');
    const btnExportar = document.getElementById('btn-exportar-pdf');
    
    function cerrarModal() { modal.style.display = 'none'; }
    closeModal.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
    btnNueva.addEventListener('click', () => {
        editandoId = null;
        document.getElementById('modalCitaTitle').innerText = 'Nueva Cita';
        form.reset();
        document.getElementById('idCita').value = '';
        modal.style.display = 'flex';
    });
    btnRefrescar.addEventListener('click', cargarCitas);
    filtro.addEventListener('change', cargarCitas);
    btnExportar?.addEventListener('click', exportarCitasPDF);
    
    async function cargarCitas() {
        const filtroVal = filtro.value;
        const citas = await apiRequest('citas', { filtro: filtroVal });
        tbody.innerHTML = citas.map(c => `
            <tr>
                <td>${c.idCita}</td>
                <td>${escapeHtml(c.cliente_nombre)}</td>
                <td>${c.placa || '-'}</td>
                <td>${new Date(c.fechaHora).toLocaleString()}</td>
                <td>${escapeHtml(c.motivo || '')}</td>
                <td><span class="status-badge ${c.estado === 'Pendiente' ? '' : 'inactivo'}">${c.estado}</span></td>
                <td class="actions-cell">
                    <button class="btn-edit" data-id="${c.idCita}"><i class="fas fa-edit"></i></button>
                    <button class="btn-delete" data-id="${c.idCita}"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
        document.querySelectorAll('.btn-edit').forEach(btn => btn.addEventListener('click', () => editarCita(btn.dataset.id)));
        document.querySelectorAll('.btn-delete').forEach(btn => btn.addEventListener('click', () => eliminarCita(btn.dataset.id)));
    }
    
    async function editarCita(id) {
        const cita = await apiRequest(`citas/${id}`);
        editandoId = id;
        document.getElementById('modalCitaTitle').innerText = 'Editar Cita';
        document.getElementById('idCita').value = cita.idCita;
        document.getElementById('idCliente').value = cita.idCliente;
        await cargarVehiculosPorCliente(cita.idCliente);
        document.getElementById('idVehiculoCita').value = cita.idVehiculo || '';
        document.getElementById('fechaHora').value = cita.fechaHora.slice(0, 16);
        document.getElementById('motivo').value = cita.motivo || '';
        document.getElementById('estadoCita').value = cita.estado;
        document.getElementById('observaciones').value = cita.observaciones || '';
        modal.style.display = 'flex';
    }
    
    async function eliminarCita(id) {
        const confirm = await Swal.fire({ title: '¿Eliminar cita?', icon: 'warning', showCancelButton: true });
        if (confirm.isConfirmed) {
            await apiRequest(`citas/${id}`, { method: 'DELETE' });
            Swal.fire('Eliminada', '', 'success');
            cargarCitas();
        }
    }
    
    // Cargar clientes en el select del modal
    async function cargarClientes() {
        const clientes = await apiRequest('clientes');
        const select = document.getElementById('idCliente');
        select.innerHTML = '<option value="">Seleccione cliente</option>' + clientes.map(c => `<option value="${c.idCliente}">${c.nombre} ${c.apellido}</option>`).join('');
        select.addEventListener('change', async (e) => {
            if (e.target.value) cargarVehiculosPorCliente(e.target.value);
            else document.getElementById('idVehiculoCita').innerHTML = '<option value="">Ninguno</option>';
        });
    }
    
    async function cargarVehiculosPorCliente(idCliente) {
        const vehiculos = await apiRequest('vehiculos');
        const filtrados = vehiculos.filter(v => v.idCliente == idCliente);
        const select = document.getElementById('idVehiculoCita');
        select.innerHTML = '<option value="">Ninguno</option>' + filtrados.map(v => `<option value="${v.idVehiculo}">${v.placa}</option>`).join('');
    }
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const idClienteEl = document.getElementById('idCliente');
        const idVehiculoEl = document.getElementById('idVehiculoCita');
        const fechaEl = document.getElementById('fechaHora');
        const motivoEl = document.getElementById('motivo');
        const estadoEl = document.getElementById('estadoCita');
        const observacionesEl = document.getElementById('observaciones');

        [idClienteEl, idVehiculoEl, fechaEl, motivoEl, estadoEl, observacionesEl].forEach(el => Validators.clearError(el));

        const idCliente = idClienteEl?.value ? parseInt(idClienteEl.value) : null;
        const idVehiculo = idVehiculoEl?.value ? parseInt(idVehiculoEl.value) : null;
        const fechaHora = fechaEl?.value || '';
        const motivo = Validators.sanitize(motivoEl?.value || '');
        const estado = estadoEl?.value || '';
        const observaciones = Validators.sanitize(observacionesEl?.value || '');

        if (!idCliente || !Validators.integer(String(idCliente))) { Validators.showError(idClienteEl, 'Seleccione cliente'); idClienteEl.focus(); return; }
        if (!Validators.required(fechaHora) || !Validators.date(fechaHora)) { Validators.showError(fechaEl, 'Fecha y hora inválidas'); fechaEl.focus(); return; }

        const data = { idCliente, idVehiculo: idVehiculo || null, fechaHora, motivo, estado, observaciones };
        let endpoint = 'citas', method = 'POST';
        if (editandoId) { endpoint = `citas/${editandoId}`; method = 'PUT'; }
        try {
            await apiRequest(endpoint, { method, body: JSON.stringify(data) });
            Swal.fire('Éxito', 'Cita guardada', 'success');
            cerrarModal();
            cargarCitas();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });
    
    async function exportarCitasPDF() {
        Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const filtroVal = filtro?.value || 'todas';
            const citas = await apiRequest('citas', { filtro: filtroVal });
            const filtroTexto = filtroVal === 'todas' ? 'Todas' : filtroVal === 'pendientes' ? 'Pendientes' : 'Hoy';
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            pdf.setFontSize(16);
            pdf.text('Reporte de Citas', 14, 16);
            pdf.setFontSize(10);
            pdf.text(`Filtro: ${filtroTexto}`, 14, 24);
            pdf.text(`Total citas: ${citas.length}`, 14, 30);

            pdf.autoTable({
                startY: 38,
                head: [['ID', 'Cliente', 'Vehículo', 'Fecha y Hora', 'Motivo', 'Estado']],
                body: citas.map(c => [
                    c.idCita || '',
                    c.cliente_nombre || '',
                    c.placa || '-',
                    c.fechaHora ? new Date(c.fechaHora).toLocaleString() : '',
                    c.motivo || '',
                    c.estado || ''
                ]),
                theme: 'striped',
                styles: { fontSize: 8 }
            });

            pdf.save(`citas_${new Date().toISOString().slice(0,19)}.pdf`);
            Swal.fire('Éxito', 'PDF generado correctamente', 'success');
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'No se pudo generar el PDF', 'error');
        }
    }

    function escapeHtml(str) { if (!str) return ''; return str.replace(/[&<>]/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[m])); }
    
    cargarClientes();
    cargarCitas();
});