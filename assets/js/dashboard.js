// Verificar dependencias al cargar
console.log('Dashboard.js cargado. Chart.js disponible:', typeof Chart !== 'undefined');
console.log('Swal disponible:', typeof Swal !== 'undefined');

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM cargado, inicializando dashboard...');
    cargarDatosCompletos();
    // Refrescar con botón
    const btnRefresh = document.getElementById('btnRefresh');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            cargarDatosCompletos();
        });
    }
    // Mostrar fecha actual
    const fechaActual = document.getElementById('fechaActual');
    if (fechaActual) {
        const hoy = new Date();
        fechaActual.innerText = hoy.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
});

let graficaTendencia = null;
let graficaEstados = null;

async function cargarDatosCompletos() {
    try {
        // 1. Resumen completo
        const resumen = await apiRequest('dashboard/resumen-completo');

        // Verificar y actualizar elementos del resumen
        const elementosResumen = {
            'total-clientes': resumen.totalClientes,
            'total-vehiculos': resumen.totalVehiculos,
            'total-ordenes': resumen.totalOrdenes,
            'ordenes-completadas': resumen.ordenesCompletadas,
            'ingresos-mes': `$${parseFloat(resumen.ingresosMes || 0).toFixed(2)}`,
            'citas-proximas': resumen.citasProximas
        };

        Object.keys(elementosResumen).forEach(id => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.innerText = elementosResumen[id];
            }
        });

        // 2. Tendencia de clientes
        const tendencia = await apiRequest('dashboard/tendencia');
        dibujarGraficaTendencia(tendencia);

        // 3. Órdenes por estado
        const estados = await apiRequest('dashboard/ordenes-por-estado');
        dibujarGraficaEstados(estados);

        // 4. Últimas órdenes
        const ultimasOrdenes = await apiRequest('dashboard/ultimas-ordenes', { limite: 5 });
        const tbodyOrdenes = document.getElementById('ultimas-ordenes');
        if (tbodyOrdenes) {
            if (ultimasOrdenes && ultimasOrdenes.length) {
                tbodyOrdenes.innerHTML = ultimasOrdenes.map(ot => `
                    <tr>
                        <td>${ot.codigo_ot || ''}</td>
                        <td>${escapeHtml(ot.cliente || '')}</td>
                        <td>${ot.placa || ''}</td>
                        <td>${ot.fechaCreacion ? new Date(ot.fechaCreacion).toLocaleDateString() : ''}</td>
                        <td><span class="status-badge ${ot.estado || ''}">${ot.estado || ''}</span></td>
                    </tr>
                `).join('');
            } else {
                tbodyOrdenes.innerHTML = '<tr><td colspan="5">No hay órdenes recientes</td></tr>';
            }
        }

        // 5. Próximas citas
        const citas = await apiRequest('dashboard/citas-proximas', { limite: 5 });
        const tbodyCitas = document.getElementById('proximas-citas');
        if (tbodyCitas) {
            if (citas && citas.length) {
                tbodyCitas.innerHTML = citas.map(c => `
                    <tr>
                        <td>${escapeHtml(c.cliente || '')}</td>
                        <td>${c.placa || '-'}</td>
                        <td>${c.fechaHora ? new Date(c.fechaHora).toLocaleString() : ''}</td>
                        <td>${escapeHtml(c.motivo || 'Sin motivo')}</td>
                        <td><span class="status-badge ${c.estado || ''}">${c.estado || ''}</span></td>
                    </tr>
                `).join('');
            } else {
                tbodyCitas.innerHTML = '<tr><td colspan="5">No hay citas próximas</td></tr>';
            }
        }

    } catch (error) {
        console.error('Error en dashboard:', error);
        // Mostrar error con Swal si está disponible
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'No se pudieron cargar los datos del dashboard', 'error');
        } else {
            alert('Error: No se pudieron cargar los datos del dashboard');
        }
    }
}


function dibujarGraficaTendencia(data) {
    // Verificar que Chart.js esté disponible
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no está cargado');
        return;
    }

    const ctx = document.getElementById('grafica-tendencia');
    if (!ctx) {
        console.warn('Elemento grafica-tendencia no encontrado');
        return;
    }

    // Destruir gráfica anterior si existe
    if (graficaTendencia) graficaTendencia.destroy();

    // Usar Chart.js
    const canvas = document.createElement('canvas');
    ctx.innerHTML = '';
    ctx.appendChild(canvas);

    try {
        graficaTendencia = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data ? data.map(d => d.mes || '') : [],
                datasets: [{
                    label: 'Nuevos clientes',
                    data: data ? data.map(d => d.cantidad || 0) : [],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#3b82f6',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Cantidad' } },
                    x: { title: { display: true, text: 'Mes' } }
                }
            }
        });
    } catch (error) {
        console.error('Error al crear gráfica de tendencia:', error);
    }
}

function dibujarGraficaEstados(data) {
    // Verificar que Chart.js esté disponible
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js no está cargado');
        return;
    }

    const canvas = document.getElementById('chart-ordenes-estado');
    if (!canvas) {
        console.warn('Elemento chart-ordenes-estado no encontrado');
        return;
    }

    if (graficaEstados) graficaEstados.destroy();

    try {
        const labels = data ? data.map(e => e.estado || '') : [];
        const valores = data ? data.map(e => e.cantidad || 0) : [];
        const colores = ['#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'];

        graficaEstados = new Chart(canvas, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: valores,
                    backgroundColor: colores.slice(0, labels.length),
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    } catch (error) {
        console.error('Error al crear gráfica de estados:', error);
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
}