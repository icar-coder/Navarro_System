// assets/js/config.js

// Detecta la URL base del proyecto automáticamente.
// Esto funciona bien si el proyecto está en la raíz o en un subdirectorio.
const BASE_URL = (() => {
    const path = window.location.pathname;
    const segments = path.split('/').filter(Boolean);
    if (segments.length === 0) return '/';
    return `/${segments[0]}/`;
})();
console.debug('[config] BASE_URL =', BASE_URL);

async function apiRequest(endpoint, queryParams = {}, options = {}) {
    // Si se pasó un objeto de opciones en la segunda posición, usarlo como options.
    const looksLikeOptions = queryParams && typeof queryParams === 'object' && (
        'method' in queryParams || 'body' in queryParams || 'headers' in queryParams || 'mode' in queryParams || 'credentials' in queryParams
    );
    if (looksLikeOptions && Object.keys(options).length === 0) {
        options = queryParams;
        queryParams = {};
    }

    // Separar endpoint y query string accidentalmente incluidos en el endpoint.
    let resource = endpoint;
    let extraQuery = '';
    if (endpoint.includes('?')) {
        [resource, extraQuery] = endpoint.split('?', 2);
    }

    const query = new URLSearchParams(queryParams).toString();
    let url = `${BASE_URL}api/index.php?resource=${resource}`;
    if (extraQuery) url += `&${extraQuery}`;
    if (query) url += `&${query}`;
    console.debug('[apiRequest] url=', url);
    
    const config = {
        headers: { 'Content-Type': 'application/json' },
        ...options
    };
    if (options.body) config.body = options.body;
    const response = await fetch(url, config);
    if (!response.ok) {
        const errorText = await response.text();
        let errorMessage = errorText;
        try {
            const errorJson = JSON.parse(errorText);
            errorMessage = errorJson.message || errorJson.error || errorJson.msg || JSON.stringify(errorJson);
        } catch (jsonError) {
            // No JSON, mantener el texto original
        }
        console.error('[apiRequest] HTTP error', response.status, url, errorText);
        throw new Error(errorMessage || `HTTP ${response.status}`);
    }
    return response.json();
}

function showLoading(elementId, show = true) {
    const element = document.getElementById(elementId);
    if (!element) return;
    if (show) {
        element.innerHTML = '<tr><td colspan="10" class="text-center"><i class="fas fa-spinner fa-pulse"></i> Cargando...</td></tr>';
    }
}

function showErrorInTable(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `<tr><td colspan="10" class="text-error">${message}</td></tr>`;
    }
}