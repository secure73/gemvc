/* global documentationData */
function toggleService(element) {
    const methodsContainer = element.nextElementSibling;
    const isExpanded = methodsContainer.style.display === 'block';
    const arrow = element.querySelector('svg');
    methodsContainer.style.display = isExpanded ? 'none' : 'block';
    arrow.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(90deg)';
}

function showEndpoint(serviceName, methodName) {
    const service = documentationData[serviceName];
    if (!service || !service.endpoints || !service.endpoints[methodName]) {
        document.getElementById('endpoint-content').innerHTML = '<p>Endpoint not found</p>';
        return;
    }
    const endpoint = service.endpoints[methodName];
    const methodClass = endpoint.method ? endpoint.method.toLowerCase() : 'unknown';
    const content = `
        <div class="endpoint-details">
            <div class="endpoint-header">
                <span class="method method-${methodClass}">${endpoint.method || 'UNKNOWN'}</span>
                <span class="url">${endpoint.url || '/'}</span>
            </div>
            <div class="endpoint-description">${endpoint.description || 'No description available'}</div>
            <div class="endpoint-example"><strong>Example:</strong> <code>${endpoint.example || 'No example documented by developer'}</code></div>
            <div class="content-wrapper">
                <div class="main-content">
                    <div class="response-section">
                        <div class="response-code"><pre><code>${formatJson(endpoint.response)}</code></pre></div>
                    </div>
                </div>
                <div class="parameters"><h3>Parameters</h3>${generateParameterTable(endpoint)}</div>
            </div>
        </div>`;
    document.getElementById('endpoint-content').innerHTML = content;
}

function formatJson(json) {
    if (!json) return 'No example response available';
    try {
        const parsed = typeof json === 'string' ? JSON.parse(json) : json;
        return JSON.stringify(parsed, null, 2);
    } catch (e) { return json; }
}

function downloadPostmanCollection() {
    try {
        const collection = { info: { name: 'API Documentation', _postman_id: Date.now().toString(), schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json' }, item: [] };
        Object.entries(documentationData).forEach(([endpointName, endpoint]) => {
            const folder = { name: endpointName, description: endpoint.description, item: [] };
            Object.entries(endpoint.endpoints).forEach(([methodName, method]) => {
                const request = { name: methodName, request: { method: method.method, description: method.description, url: { raw: method.method === 'GET' && method.urlparams ? '{{base_url}}' + method.url + (method.url.endsWith('/') ? '?' : '/?') + Object.keys(method.urlparams).map(key => key + '=').join('&') : '{{base_url}}' + method.url, host: ['{{base_url}}'], path: method.url.split('/').filter(Boolean), query: method.method === 'GET' && method.urlparams ? Object.keys(method.urlparams).map(key => ({ key: key, value: '' })) : [] }, header: [ { key: 'Content-Type', value: 'application/json' } ] } };
                if (method.urlparams && method.method !== 'GET') {
                    request.request.url.variable = [];
                    Object.entries(method.urlparams).forEach(([name, param]) => { request.request.url.variable.push({ key: name, value: '', description: 'Type: ' + param.type + (param.required ? ' (Required)' : '') }); });
                }
                if (method.parameters) {
                    request.request.body = { mode: 'formdata', formdata: [] };
                    Object.entries(method.parameters).forEach(([name, param]) => { request.request.body.formdata.push({ key: name, value: '', type: 'text', description: 'Type: ' + param.type + (param.required ? ' (Required)' : '') }); });
                }
                folder.item.push(request);
            });
            collection.item.push(folder);
        });
        const blob = new Blob([JSON.stringify(collection, null, 2)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url; a.download = 'api_collection.json'; document.body.appendChild(a); a.click(); window.URL.revokeObjectURL(url); document.body.removeChild(a);
    } catch (error) { console.error('Error generating Postman collection:', error); alert('Error generating Postman collection. Please check the console for details.'); }
}

function generateParameterTable(endpoint) {
    const hasParams = endpoint.parameters && Object.keys(endpoint.parameters).length > 0;
    const hasGetParams = endpoint.get_parameters && Object.keys(endpoint.get_parameters).length > 0;
    const hasUrlParams = endpoint.urlparams && Object.keys(endpoint.urlparams).length > 0;
    const hasQueryParams = endpoint.query_parameters && Object.keys(endpoint.query_parameters).length > 0;
    if (!hasParams && !hasGetParams && !hasQueryParams && !hasUrlParams) return '<p>No parameters required</p>';
    let html = '';
    if (hasUrlParams) { html += '<h4>URL Parameters</h4>'; html += generateParamTable(endpoint.urlparams); }
    if (hasGetParams) { html += '<h4>GET Parameters</h4>'; html += generateParamTable(endpoint.get_parameters); }
    if (hasParams) { html += '<h4>Body Parameters</h4>'; html += generateParamTable(endpoint.parameters); }
    if (hasQueryParams) {
        html += '<h4>Query Parameters</h4>';
        if (endpoint.query_parameters.filters) { html += '<h5>Filters</h5>'; html += generateParamTable(endpoint.query_parameters.filters); }
        if (endpoint.query_parameters.sort) { html += '<h5>Sort</h5>'; html += generateParamTable(endpoint.query_parameters.sort); }
        if (endpoint.query_parameters.search) { html += '<h5>Search</h5>'; html += generateParamTable(endpoint.query_parameters.search); }
    }
    return html;
}

function generateParamTable(params) {
    let html = `<table class="parameter-table"><tr><th>Parameter</th><th>Type</th><th>Required</th></tr>`;
    for (const [name, param] of Object.entries(params)) {
        const required = param.required ? '<span class="required">*</span>' : '';
        html += `<tr><td>${name}${required}</td><td>${param.type}</td><td>${param.required ? 'Yes' : 'No'}</td></tr>`;
    }
    html += '</table>';
    return html;
}

function buildTree() {
    const container = document.getElementById('tree-content');
    if (!container) return;
    const frag = document.createDocumentFragment();
    Object.entries(documentationData).forEach(([serviceName, service]) => {
        const treeItem = document.createElement('div'); treeItem.className = 'tree-item';
        const header = document.createElement('div'); header.className = 'service-name'; header.onclick = function() { toggleService(header); };
        header.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>${serviceName}`;
        const methodsDiv = document.createElement('div'); methodsDiv.className = 'service-methods'; methodsDiv.style.display = 'none';
        if (service && service.endpoints) { Object.entries(service.endpoints).forEach(([methodName, method]) => { const methodItem = document.createElement('div'); methodItem.className = 'method-item'; methodItem.onclick = function() { showEndpoint(serviceName, methodName); }; const methodClass = method.method ? method.method.toLowerCase() : 'unknown'; methodItem.innerHTML = `<span class="method-icon method-${methodClass}">${method.method || ''}</span>${methodName}`; methodsDiv.appendChild(methodItem); }); }
        treeItem.appendChild(header); treeItem.appendChild(methodsDiv); frag.appendChild(treeItem);
    });
    container.innerHTML = ''; container.appendChild(frag);
}

document.addEventListener('DOMContentLoaded', function() { buildTree(); const firstService = document.querySelector('.service-name'); if (firstService) { toggleService(firstService); } });


