<?php

namespace Gemvc\Core;

use Gemvc\Core\ApiDocGenerator;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;

class Documentation
{
    /**
     * @param array<string, array{description: string, endpoints: array<string, array{method: string, url: string, description: string, parameters?: array<string, array{type: string, required: bool}>, urlparams?: array<string, array{type: string, required: bool}>, query_parameters?: array<string, array<string, array{type: string, required: bool}>>, response?: string|false}>}> $documentation
     */
    private function generateHtmlView(array $documentation): string
    {
        return $this->generateHtmlStructure($documentation);
    }

    private function generateHtmlStructure(array $documentation): string
    {
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>API Documentation</title>
            <style>
                {$this->getStyles()}
            </style>
        </head>
        <body>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>
            <div class="container">
                <div class="nav-tree">
                    <div class="header-section">
                        <h1>API Documentation</h1>
                        <button onclick="downloadPostmanCollection()" class="export-button">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Export to Postman
                        </button>
                    </div>
                    <div class="tree-content">
                        {$this->generateTreeNavigation($documentation)}
                    </div>
                </div>
                <div class="content-area">
                    <div id="endpoint-content"></div>
                </div>
            </div>
            <script>
                {$this->getJavaScript($documentation)}
            </script>
        </body>
        </html>
        HTML;

        return $html;
    }

    private function generateTreeNavigation(array $documentation): string
    {
        $html = '';
        foreach ($documentation as $serviceName => $service) {
            $html .= <<<HTML
            <div class="tree-item">
                <div class="service-name" onclick="toggleService(this)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                    {$serviceName}
                </div>
                <div class="service-methods" style="display: none;">
            HTML;

            foreach ($service['endpoints'] as $methodName => $method) {
                $methodClass = strtolower($method['method']);
                $html .= <<<HTML
                    <div class="method-item" onclick="showEndpoint('{$serviceName}', '{$methodName}')">
                        <span class="method-icon method-{$methodClass}">{$method['method']}</span>
                        {$methodName}
                    </div>
                HTML;
            }

            $html .= '</div></div>';
        }
        return $html;
    }

    private function getStyles(): string
    {
        return <<<CSS
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 0;
                background: #f5f5f5;
                height: 100vh;
                overflow: hidden;
            }
            .container {
                display: grid;
                grid-template-columns: 350px 1fr;
                height: 100vh;
                overflow: hidden;
            }
            .nav-tree {
                background: #fff;
                border-right: 1px solid #e0e0e0;
                padding: 20px;
                overflow-y: auto;
                height: 100vh;
                min-width: 350px;
            }
            .content-area {
                padding: 20px;
                overflow-y: auto;
                height: 100vh;
            }
            .tree-item {
                margin: 8px 0;
            }
            .service-name {
                font-weight: 600;
                color: #1976d2;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px;
                border-radius: 4px;
                transition: background-color 0.2s;
            }
            .service-name:hover {
                background: #f5f5f5;
            }
            .method-item {
                margin-left: 24px;
                padding: 6px 8px;
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                border-radius: 4px;
                transition: background-color 0.2s;
            }
            .method-item:hover {
                background: #f5f5f5;
            }
            .method-icon {
                width: 16px;
                height: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
                color: white;
            }
            .method-get { background: #2e7d32; }
            .method-post { background: rgb(221, 190, 17); }
            .method-put { background: rgb(0, 65, 245); }
            .method-delete { background: rgb(221, 13, 13); }
            .endpoint-details {
                background: white;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .endpoint-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 15px;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 6px;
            }
            .endpoint {
                background: white;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .endpoint-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 15px;
            }
            .method {
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: bold;
                font-size: 14px;
                text-transform: uppercase;
            }
            .method-get { background: #e8f5e9; color: #2e7d32; }
            .method-post { background: #fff3e0; color:rgb(221, 190, 17); }
            .method-put { background: #fff3e0; color:rgb(0, 65, 245); }
            .method-delete { background: #ffebee; color:rgb(221, 13, 13); }
            .url {
                font-family: monospace;
                font-size: 16px;
                color: #333;
            }
            .description {
                color: #666;
                margin-bottom: 20px;
            }
            .content-wrapper {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                align-items: start;
            }
            .main-content {
                flex: 1;
            }
            .parameters {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 6px;
                border-left: 4px solid #1976d2;
            }
            .parameters h3 {
                margin-top: 0;
                color: #1976d2;
                font-size: 18px;
                margin-bottom: 15px;
            }
            .parameter-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                font-size: 14px;
            }
            .parameter-table th, .parameter-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #e0e0e0;
            }
            .parameter-table th {
                background: #e3f2fd;
                font-weight: 600;
                color: #1976d2;
            }
            .required {
                color: #c62828;
                font-size: 12px;
                margin-left: 4px;
            }
            .response-section {
                margin-top: 0;
            }
            .response-header {
                font-weight: 600;
                margin-bottom: 10px;
                color: #333;
            }
            .response-code {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
                font-family: monospace;
                white-space: pre-wrap;
                overflow-x: auto;
                border: 1px solid #e0e0e0;
                height: 100%;
            }
            .response-code pre {
                margin: 0;
                font-size: 14px;
                line-height: 1.5;
            }
            .response-code code {
                display: block;
                padding: 10px;
                background: #1e1e1e;
                color: #d4d4d4;
                border-radius: 4px;
                overflow-x: auto;
            }
            .response-code .json-key { color: #9cdcfe; }
            .response-code .json-string { color: #ce9178; }
            .response-code .json-number { color: #b5cea8; }
            .response-code .json-boolean { color: #569cd6; }
            .response-code .json-null { color: #808080; }
            .service-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
                padding: 15px 20px;
                background: #fff;
                border-radius: 8px;
                margin-bottom: 2px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .service-header h2 {
                margin: 0;
                font-size: 20px;
                color: #1976d2;
            }
            .service-content {
                display: none;
                padding: 20px;
                background: #fff;
                border-radius: 0 0 8px 8px;
                margin-bottom: 20px;
            }
            .service-content.active {
                display: block;
            }
            .accordion-icon {
                width: 24px;
                height: 24px;
                transition: transform 0.3s ease;
            }
            .service-header.active .accordion-icon {
                transform: rotate(180deg);
            }
            .service-section {
                margin-bottom: 20px;
                border-radius: 8px;
                overflow: hidden;
            }
            .endpoint-description {
                margin: 10px 0 15px;
                padding: 15px;
                background: #f8f9fa;
                border-left: 4px solid #1976d2;
                color: #666;
                font-size: 14px;
                line-height: 1.6;
                white-space: pre-line;
            }
            .endpoint-description:empty {
                display: none;
            }
            .endpoint-example {
                margin: 0 0 20px;
                padding: 12px 15px;
                background: #e8f5e9;
                border-left: 4px solid #2e7d32;
                color: #1b5e20;
                font-size: 14px;
                line-height: 1.6;
            }
            .endpoint-example code {
                background: #c8e6c9;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                color: #1b5e20;
            }
            .header-section {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                flex-wrap: wrap;
                gap: 10px;
            }
            .header-section h1 {
                margin: 0;
                font-size: 24px;
            }
            .export-button {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #1976d2;
                color: white;
                padding: 8px 16px;
                border-radius: 4px;
                text-decoration: none;
                font-weight: 500;
                border: none;
                cursor: pointer;
                transition: background-color 0.2s;
                white-space: nowrap;
                min-height: 40px;
            }
            .export-button:hover {
                background: #1565c0;
            }
            .mobile-menu-toggle {
                display: none;
                background: none;
                border: none;
                padding: 10px;
                cursor: pointer;
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 1001;
                background: #1976d2;
                border-radius: 4px;
            }
            .mobile-menu-toggle svg {
                width: 24px;
                height: 24px;
                color: white;
            }
            @media (max-width: 1024px) {
                .container {
                    grid-template-columns: 300px 1fr;
                }
                .nav-tree {
                    min-width: 300px;
                }
            }
            @media (max-width: 768px) {
                .container {
                    grid-template-columns: 1fr;
                }
                .nav-tree {
                    position: fixed;
                    left: 0;
                    top: 0;
                    width: 280px;
                    transform: translateX(-100%);
                    transition: transform 0.3s ease;
                    z-index: 1000;
                }
                .nav-tree.active {
                    transform: translateX(0);
                }
                .content-area {
                    margin-left: 0;
                }
                .header-section {
                    flex-direction: column;
                    align-items: flex-start;
                }
                .export-button {
                    width: 100%;
                    justify-content: center;
                }
                .mobile-menu-toggle {
                    display: block;
                }
                .container {
                    padding-top: 60px;
                }
            }
        CSS;
    }

    private function getJavaScript(array $documentation): string
    {
        return <<<JS
            const documentation = {$this->formatJson(json_encode($documentation, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP))};
            
            function toggleService(element) {
                const methodsContainer = element.nextElementSibling;
                const isExpanded = methodsContainer.style.display === 'block';
                const arrow = element.querySelector('svg');
                
                methodsContainer.style.display = isExpanded ? 'none' : 'block';
                arrow.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(90deg)';
            }

            function showEndpoint(serviceName, methodName) {
                const service = documentation[serviceName];
                const endpoint = service.endpoints[methodName];
                const methodClass = endpoint.method.toLowerCase();
                
                const content = `
                    <div class="endpoint-details">
                        <div class="endpoint-header">
                            <span class="method method-\${methodClass}">\${endpoint.method}</span>
                            <span class="url">\${endpoint.url}</span>
                        </div>
                        <div class="endpoint-description">
                            \${endpoint.description || 'No description available'}
                        </div>
                        <div class="endpoint-example">
                            <strong>Example:</strong> <code>\${endpoint.example || 'No example documented by developer'}</code>
                        </div>
                        <div class="content-wrapper">
                            <div class="main-content">
                                <div class="response-section">
                                    <div class="response-code">
                                        <pre><code>\${formatJson(endpoint.response)}</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="parameters">
                                <h3>Parameters</h3>
                                \${generateParameterTable(endpoint)}
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('endpoint-content').innerHTML = content;
            }

            function generateParameterTable(endpoint) {
                const hasParams = endpoint.parameters && Object.keys(endpoint.parameters).length > 0;
                const hasGetParams = endpoint.get_parameters && Object.keys(endpoint.get_parameters).length > 0;
                const hasUrlParams = endpoint.urlparams && Object.keys(endpoint.urlparams).length > 0;
                const hasQueryParams = endpoint.query_parameters && Object.keys(endpoint.query_parameters).length > 0;

                if (!hasParams && !hasGetParams && !hasQueryParams && !hasUrlParams) {
                    return '<p>No parameters required</p>';
                }

                let html = '';

                if (hasUrlParams) {
                    html += '<h4>URL Parameters</h4>';
                    html += generateParamTable(endpoint.urlparams);
                }

                if (hasGetParams) {
                    html += '<h4>GET Parameters</h4>';
                    html += generateParamTable(endpoint.get_parameters);
                }

                if (hasParams) {
                    html += '<h4>Body Parameters</h4>';
                    html += generateParamTable(endpoint.parameters);
                }

                if (hasQueryParams) {
                    html += '<h4>Query Parameters</h4>';
                    if (endpoint.query_parameters.filters) {
                        html += '<h5>Filters</h5>';
                        html += generateParamTable(endpoint.query_parameters.filters);
                    }
                    if (endpoint.query_parameters.sort) {
                        html += '<h5>Sort</h5>';
                        html += generateParamTable(endpoint.query_parameters.sort);
                    }
                    if (endpoint.query_parameters.search) {
                        html += '<h5>Search</h5>';
                        html += generateParamTable(endpoint.query_parameters.search);
                    }
                }

                return html;
            }

            function generateParamTable(params) {
                let html = `
                    <table class="parameter-table">
                        <tr><th>Parameter</th><th>Type</th><th>Required</th></tr>
                `;
                
                for (const [name, param] of Object.entries(params)) {
                    const required = param.required ? '<span class="required">*</span>' : '';
                    html += `
                        <tr>
                            <td>\${name}\${required}</td>
                            <td>\${param.type}</td>
                            <td>\${param.required ? 'Yes' : 'No'}</td>
                        </tr>
                    `;
                }
                
                html += '</table>';
                return html;
            }

            function formatJson(json) {
                if (!json) return 'No example response available';
                try {
                    const parsed = typeof json === 'string' ? JSON.parse(json) : json;
                    return JSON.stringify(parsed, null, 2);
                } catch (e) {
                    return json;
                }
            }

            // Postman export functionality
            function downloadPostmanCollection() {
                try {
                    const collection = {
                        info: {
                            name: 'API Documentation',
                            _postman_id: Date.now().toString(),
                            schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
                        },
                        item: []
                    };

                    Object.entries(documentation).forEach(([endpointName, endpoint]) => {
                        const folder = {
                            name: endpointName,
                            description: endpoint.description,
                            item: []
                        };

                        Object.entries(endpoint.endpoints).forEach(([methodName, method]) => {
                            const request = {
                                name: methodName,
                                request: {
                                    method: method.method,
                                    description: method.description,
                                    url: {
                                        raw: method.method === 'GET' && method.urlparams 
                                            ? '{{base_url}}' + method.url + (method.url.endsWith('/') ? '?' : '/?') + Object.keys(method.urlparams).map(key => key + '=').join('&')
                                            : '{{base_url}}' + method.url,
                                        host: ['{{base_url}}'],
                                        path: method.url.split('/').filter(Boolean),
                                        query: method.method === 'GET' && method.urlparams
                                            ? Object.keys(method.urlparams).map(key => ({
                                                key: key,
                                                value: ''
                                            }))
                                            : []
                                    },
                                    header: [
                                        {
                                            key: 'Content-Type',
                                            value: 'application/json'
                                        }
                                    ]
                                }
                            };

                            if (method.urlparams && method.method !== 'GET') {
                                request.request.url.variable = [];
                                Object.entries(method.urlparams).forEach(([name, param]) => {
                                    request.request.url.variable.push({
                                        key: name,
                                        value: '',
                                        description: 'Type: ' + param.type + (param.required ? ' (Required)' : '')
                                    });
                                });
                            }

                            if (method.parameters) {
                                request.request.body = {
                                    mode: 'formdata',
                                    formdata: []
                                };
                                
                                Object.entries(method.parameters).forEach(([name, param]) => {
                                    request.request.body.formdata.push({
                                        key: name,
                                        value: '',
                                        type: 'text',
                                        description: 'Type: ' + param.type + (param.required ? ' (Required)' : '')
                                    });
                                });
                            }

                            folder.item.push(request);
                        });

                        collection.item.push(folder);
                    });

                    const blob = new Blob([JSON.stringify(collection, null, 2)], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'api_collection.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } catch (error) {
                    console.error('Error generating Postman collection:', error);
                    alert('Error generating Postman collection. Please check the console for details.');
                }
            }

            function toggleMobileMenu() {
                const navTree = document.querySelector('.nav-tree');
                navTree.classList.toggle('active');
            }

            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                const navTree = document.querySelector('.nav-tree');
                const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
                
                if (window.innerWidth <= 768 && 
                    !navTree.contains(event.target) && 
                    !mobileMenuToggle.contains(event.target)) {
                    navTree.classList.remove('active');
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                const navTree = document.querySelector('.nav-tree');
                if (window.innerWidth > 768) {
                    navTree.classList.remove('active');
                }
            });

            // Open first service by default
            document.addEventListener('DOMContentLoaded', function() {
                const firstService = document.querySelector('.service-name');
                if (firstService) {
                    toggleService(firstService);
                }
            });
        JS;
    }

    /**
     * @param array<string, array{description: string, endpoints: array<string, array{method: string, url: string, description: string, parameters?: array<string, array{type: string, required: bool}>, urlparams?: array<string, array{type: string, required: bool}>, query_parameters?: array<string, array<string, array{type: string, required: bool}>>, response?: string|false}>}> $documentation
     */
    private function generateParameterTable(array $method): string
    {
        $hasParams = isset($method['parameters']) && !empty($method['parameters']);
        $hasGetParams = isset($method['get_parameters']) && !empty($method['get_parameters']);
        $hasUrlParams = isset($method['urlparams']) && !empty($method['urlparams']);
        $hasQueryParams = isset($method['query_parameters']) && !empty($method['query_parameters']);

        if (!$hasParams && !$hasGetParams && !$hasQueryParams && !$hasUrlParams) {
            return '<p>No parameters required</p>';
        }

        $html = '';

        // URL Parameters
        if ($hasUrlParams) {
            $html .= '<h4>URL Parameters</h4>';
            $html .= '<table class="parameter-table">';
            $html .= '<tr><th>Parameter</th><th>Type</th><th>Required</th></tr>';
            
            foreach ($method['urlparams'] as $name => $param) {
                $required = $param['required'] ? '<span class="required">*</span>' : '';
                $html .= sprintf(
                    '<tr><td>%s%s</td><td>%s</td><td>%s</td></tr>',
                    htmlspecialchars($name),
                    $required,
                    htmlspecialchars($param['type']),
                    $param['required'] ? 'Yes' : 'No'
                );
            }
            
            $html .= '</table>';
        }

        // GET Parameters
        if ($hasGetParams && isset($method['get_parameters'])) {
            $html .= '<h4>GET Parameters</h4>';
            $html .= '<table class="parameter-table">';
            $html .= '<tr><th>Parameter</th><th>Type</th><th>Required</th></tr>';
            
            foreach ($method['get_parameters'] as $name => $param) {
                $required = $param['required'] ? '<span class="required">*</span>' : '';
                $html .= sprintf(
                    '<tr><td>%s%s</td><td>%s</td><td>%s</td></tr>',
                    htmlspecialchars($name),
                    $required,
                    htmlspecialchars($param['type']),
                    $param['required'] ? 'Yes' : 'No'
                );
            }
            
            $html .= '</table>';
        }

        // Regular Parameters
        if ($hasParams && isset($method['parameters'])) {
            $html .= '<h4>Body Parameters</h4>';
            $html .= '<table class="parameter-table">';
            $html .= '<tr><th>Parameter</th><th>Type</th><th>Required</th></tr>';
            
            foreach ($method['parameters'] as $name => $param) {
                $required = $param['required'] ? '<span class="required">*</span>' : '';
                $html .= sprintf(
                    '<tr><td>%s%s</td><td>%s</td><td>%s</td></tr>',
                    htmlspecialchars($name),
                    $required,
                    htmlspecialchars($param['type']),
                    $param['required'] ? 'Yes' : 'No'
                );
            }
            
            $html .= '</table>';
        }

        // Query Parameters
        if ($hasQueryParams) {
            if ($hasParams) {
                $html .= '<div style="margin-top: 20px;"></div>';
            }
            $html .= '<h4>Query Parameters</h4>';
            
            // Handle filters
            if (!empty($method['query_parameters']['filters'])) {
                $html .= '<h5>Filters</h5>';
                $html .= $this->generateSubParameterTable($method['query_parameters']['filters']);
            }

            // Handle sort
            if (!empty($method['query_parameters']['sort'])) {
                $html .= '<h5>Sort</h5>';
                $html .= $this->generateSubParameterTable($method['query_parameters']['sort']);
            }

            // Handle search
            if (!empty($method['query_parameters']['search'])) {
                $html .= '<h5>Search</h5>';
                $html .= $this->generateSubParameterTable($method['query_parameters']['search']);
            }
        }

        return $html;
    }

    /**
     * @param array<string, array{type: string, required: bool}> $params
     */
    private function generateSubParameterTable(array $params): string
    {
        $html = '<table class="parameter-table">';
        $html .= '<tr><th>Parameter</th><th>Type</th><th>Required</th></tr>';
        
        foreach ($params as $name => $param) {
            $required = $param['required'] ? '<span class="required">*</span>' : '';
            $html .= sprintf(
                '<tr><td>%s%s</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($name),
                $required,
                htmlspecialchars($param['type']),
                $param['required'] ? 'Yes' : 'No'
            );
        }
        
        $html .= '</table>';
        return $html;
    }

    /**
     * @param string|false|null $json
     */
    private function formatJson($json): string
    {
        if (empty($json)) {
            return 'No example response available';
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return (string)$json; // Cast to string to ensure type safety
        }

        // Remove any markdown code block markers and "Example Response:" text
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $json = (string)$json; // Cast to string to ensure type safety
        $json = str_replace(['```json', '```', 'Example Response:'], '', $json);
        return trim($json);
    }

    private function formatDescription(string $description): string
    {
        // Convert line breaks to <br> tags and escape HTML
        $description = htmlspecialchars($description, ENT_QUOTES | ENT_HTML5);
        $description = nl2br($description);
        return $description;
    }

    public function show(): JsonResponse
    {
        $generator = new ApiDocGenerator();
        $documentation = $generator->generate();
        return Response::success($documentation);
    }

    public function html(): void
    {
        $generator = new ApiDocGenerator();
        $documentation = $generator->generate();
        header('Content-Type: text/html');
        // Generate HTML view of the documentation
        echo $this->generateHtmlView($documentation);
        die();
    }
} 
