document.addEventListener('DOMContentLoaded', () => {
    // Configuración de fechas por defecto: últimos 12 meses
    const hoy = new Date();
    const haceUnAnio = new Date();
    haceUnAnio.setFullYear(hoy.getFullYear() - 1);
    document.getElementById('fecha-inicio').value = haceUnAnio.toISOString().slice(0, 7) + '-01';
    document.getElementById('fecha-fin').value = hoy.toISOString().slice(0, 10);

    // Elementos
    const btnAplicar = document.getElementById('btn-aplicar-filtros');
    const btnLimpiar = document.getElementById('btn-limpiar-filtros');
    const btnExportar = document.getElementById('btn-exportar-pdf');
    const chkIncluirGraficas = document.getElementById('chk-incluir-graficas');
    const chkIncluirOrdenes = document.getElementById('chk-incluir-ordenes');

    // Inicializar gráficos (variables globales)
    let chartIngresos, chartEstados, chartServicios, chartVehiculos;

    // Cargar datos iniciales
    cargarKPIs();
    cargarGraficos();
    cargarOrdenesRecientes();

    // Eventos
    btnAplicar.addEventListener('click', () => {
        cargarKPIs();
        cargarGraficos();
        cargarOrdenesRecientes();
    });
    btnLimpiar.addEventListener('click', () => {
        document.getElementById('fecha-inicio').value = '';
        document.getElementById('fecha-fin').value = '';
        cargarKPIs();
        cargarGraficos();
        cargarOrdenesRecientes();
    });
    btnExportar.addEventListener('click', exportarPDF);

    async function cargarKPIs() {
        try {
            const params = obtenerParamsFecha();
            const ingresos = await apiRequest('reportes/ingresos-totales', params);
            const ordenes = await apiRequest('reportes/total-ordenes', params);
            const vehiculos = await apiRequest('reportes/total-vehiculos');
            const clientes = await apiRequest('reportes/total-clientes-activos');
            document.getElementById('total-ingresos').innerText = `$${parseFloat(ingresos.total || 0).toFixed(2)}`;
            document.getElementById('total-ordenes').innerText = ordenes.total || 0;
            document.getElementById('total-vehiculos').innerText = vehiculos.total || 0;
            document.getElementById('total-clientes').innerText = clientes.total || 0;
        } catch (error) {
            console.error(error);
        }
    }

    async function cargarGraficos() {
        const params = obtenerParamsFecha();
        // Ingresos mensuales
        const ingresosData = await apiRequest('reportes/ingresos-mensuales', params);
        if (chartIngresos) chartIngresos.destroy();
        const ctx1 = document.getElementById('chart-ingresos').getContext('2d');
        chartIngresos = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: ingresosData.map(i => i.mes),
                datasets: [{ label: 'Ingresos (USD)', data: ingresosData.map(i => i.total), backgroundColor: '#2c7da0' }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });

        // Órdenes por estado
        const estadosData = await apiRequest('reportes/ordenes-por-estado', params);
        if (chartEstados) chartEstados.destroy();
        const ctx2 = document.getElementById('chart-estados').getContext('2d');
        chartEstados = new Chart(ctx2, {
            type: 'pie',
            data: {
                labels: estadosData.map(e => e.estado),
                datasets: [{ data: estadosData.map(e => e.cantidad), backgroundColor: ['#10b981','#f59e0b','#ef4444','#3b82f6','#6b7280'] }]
            }
        });

        // Top servicios
        const serviciosData = await apiRequest('reportes/top-servicios', params);
        if (chartServicios) chartServicios.destroy();
        const ctx3 = document.getElementById('chart-servicios').getContext('2d');
        chartServicios = new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: serviciosData.map(s => s.nombre),
                datasets: [{ label: 'Veces solicitado', data: serviciosData.map(s => s.total), backgroundColor: '#f97316' }]
            }
        });

        // Top vehículos
        const vehiculosData = await apiRequest('reportes/top-vehiculos', params);
        if (chartVehiculos) chartVehiculos.destroy();
        const ctx4 = document.getElementById('chart-vehiculos').getContext('2d');
        chartVehiculos = new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: vehiculosData.map(v => v.placa),
                datasets: [{ label: 'Cantidad de servicios', data: vehiculosData.map(v => v.total_servicios), backgroundColor: '#8b5cf6' }]
            }
        });
    }

    async function cargarOrdenesRecientes() {
        const tbody = document.getElementById('tbody-ordenes');
        try {
            const params = obtenerParamsFecha();
        const ordenes = await apiRequest('reportes/ordenes-recientes', params);
            if (!ordenes.length) {
                tbody.innerHTML = '<tr><td colspan="6">No hay órdenes recientes</td></tr>';
                return;
            }
            tbody.innerHTML = ordenes.map(ot => `
                <tr>
                    <td>${ot.codigo_ot}</td>
                    <td>${escapeHtml(ot.cliente_nombre || '')} ${escapeHtml(ot.cliente_apellido || '')}</td>
                    <td>${ot.placa}</td>
                    <td>${new Date(ot.fechaCreacion).toLocaleDateString()}</td>
                    <td><span class="status-badge ${ot.estado}">${ot.estado}</span></td>
                    <td>$${parseFloat(ot.presupuesto_total || 0).toFixed(2)}</td>
                </tr>
            `).join('');
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="6">Error al cargar</td></tr>';
        }
    }

    function obtenerParamsFecha() {
        const inicio = document.getElementById('fecha-inicio').value;
        const fin = document.getElementById('fecha-fin').value;
        const params = {};
        if (inicio) params.fecha_inicio = inicio;
        if (fin) params.fecha_fin = fin;
        return params;
    }

    async function exportarPDF() {
        Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const params = obtenerParamsFecha();
            const includeCharts = chkIncluirGraficas.checked;
            const includeOrders = chkIncluirOrdenes.checked;

            const [ingresos, ordenes, vehiculos, clientes, ingresosData, estadosData, serviciosData, vehiculosData, ordenesDetalle] = await Promise.all([
                apiRequest('reportes/ingresos-totales', params),
                apiRequest('reportes/total-ordenes', params),
                apiRequest('reportes/total-vehiculos'),
                apiRequest('reportes/total-clientes-activos'),
                apiRequest('reportes/ingresos-mensuales', params),
                apiRequest('reportes/ordenes-por-estado', params),
                apiRequest('reportes/top-servicios', params),
                apiRequest('reportes/top-vehiculos', params),
                includeOrders ? apiRequest('reportes/ordenes-detalladas', params) : Promise.resolve([])
            ]);

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            let y = 20;
            pdf.setFontSize(18);
            pdf.text('Reporte detallado', 14, y);
            y += 8;
            pdf.setFontSize(10);
            pdf.text(`Periodo: ${document.getElementById('fecha-inicio').value || 'Todos'} - ${document.getElementById('fecha-fin').value || 'Todos'}`, 14, y);
            y += 6;
            pdf.text(`Incluye gráficos: ${includeCharts ? 'Sí' : 'No'}`, 14, y);
            y += 6;
            pdf.text(`Incluye órdenes: ${includeOrders ? 'Sí' : 'No'}`, 14, y);
            y += 10;

            const summaryRows = [
                ['Ingresos totales', `$${parseFloat(ingresos.total || 0).toFixed(2)}`],
                ['Órdenes', ordenes.total || 0],
                ['Vehículos', vehiculos.total || 0],
                ['Clientes activos', clientes.total || 0]
            ];
            pdf.autoTable({
                startY: y,
                head: [['Métrica', 'Valor']],
                body: summaryRows,
                theme: 'grid',
                styles: { fontSize: 10 }
            });
            y = pdf.lastAutoTable.finalY + 10;

            if (includeOrders && ordenesDetalle.length) {
                pdf.setFontSize(12);
                pdf.text('Órdenes detalladas', 14, y);
                y += 6;
                pdf.autoTable({
                    startY: y,
                    head: [['Código', 'Cliente', 'Vehículo', 'Fecha', 'Estado', 'Total']],
                    body: ordenesDetalle.map(item => [
                        item.codigo_ot || '',
                        item.cliente || '',
                        item.placa || '',
                        item.fechaCreacion ? new Date(item.fechaCreacion).toLocaleDateString() : '',
                        item.estado || '',
                        `$${parseFloat(item.presupuesto_total || 0).toFixed(2)}`
                    ]),
                    theme: 'striped',
                    styles: { fontSize: 8 }
                });
                y = pdf.lastAutoTable.finalY + 10;
            }

            if (ingresosData && ingresosData.length) {
                pdf.setFontSize(12);
                pdf.text('Ingresos mensuales', 14, y);
                y += 6;
                pdf.autoTable({
                    startY: y,
                    head: [['Mes', 'Total']],
                    body: ingresosData.map(row => [row.mes || '', `$${parseFloat(row.total || 0).toFixed(2)}`]),
                    theme: 'striped',
                    styles: { fontSize: 9 }
                });
                y = pdf.lastAutoTable.finalY + 10;
            }

            if (estadosData && estadosData.length) {
                pdf.setFontSize(12);
                pdf.text('Órdenes por estado', 14, y);
                y += 6;
                pdf.autoTable({
                    startY: y,
                    head: [['Estado', 'Cantidad']],
                    body: estadosData.map(row => [row.estado || '', row.cantidad || 0]),
                    theme: 'striped',
                    styles: { fontSize: 9 }
                });
                y = pdf.lastAutoTable.finalY + 10;
            }

            if (serviciosData && serviciosData.length) {
                pdf.setFontSize(12);
                pdf.text('Top servicios', 14, y);
                y += 6;
                pdf.autoTable({
                    startY: y,
                    head: [['Servicio', 'Veces solicitado']],
                    body: serviciosData.map(row => [row.nombre || '', row.total || 0]),
                    theme: 'striped',
                    styles: { fontSize: 9 }
                });
                y = pdf.lastAutoTable.finalY + 10;
            }

            if (vehiculosData && vehiculosData.length) {
                pdf.setFontSize(12);
                pdf.text('Top vehículos', 14, y);
                y += 6;
                pdf.autoTable({
                    startY: y,
                    head: [['Placa', 'Servicios']],
                    body: vehiculosData.map(row => [row.placa || '', row.total_servicios || 0]),
                    theme: 'striped',
                    styles: { fontSize: 9 }
                });
                y = pdf.lastAutoTable.finalY + 10;
            }

            if (includeCharts) {
                const charts = ['chart-ingresos', 'chart-estados', 'chart-servicios', 'chart-vehiculos'];
                for (const id of charts) {
                    const canvas = document.getElementById(id);
                    if (!canvas) continue;
                    const imageData = canvas.toDataURL('image/png');
                    const imgWidth = 180;
                    const imgHeight = (canvas.height / canvas.width) * imgWidth;
                    if (y + imgHeight > 275) pdf.addPage(), y = 20;
                    pdf.setFontSize(12);
                    const title = id === 'chart-ingresos' ? 'Ingresos mensuales' : id === 'chart-estados' ? 'Órdenes por estado' : id === 'chart-servicios' ? 'Top servicios' : 'Top vehículos';
                    pdf.text(title, 14, y);
                    y += 6;
                    pdf.addImage(imageData, 'PNG', 15, y, imgWidth, imgHeight);
                    y += imgHeight + 10;
                }
            }

            pdf.save(`reporte_detallado_${new Date().toISOString().slice(0,19)}.pdf`);
            Swal.fire('Éxito', 'PDF generado', 'success');
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'No se pudo generar el PDF', 'error');
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[m]));
    }
});