<?php

namespace Gemvc\Core;

use Gemvc\Core\ApiDocGenerator;
use Gemvc\Core\Documentation\ParameterTableGenerator;
use Gemvc\Core\Documentation\HtmlHelper;
use Gemvc\Core\Documentation\ParameterValidator;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;
use Gemvc\Http\HtmlResponse;

class Documentation
{
    private ParameterTableGenerator $parameterTableGenerator;
    private HtmlHelper $htmlHelper;
    private ParameterValidator $parameterValidator;

    public function __construct()
    {
        $this->parameterTableGenerator = new ParameterTableGenerator();
        $this->htmlHelper = new HtmlHelper();
        $this->parameterValidator = new ParameterValidator();
    }
    /**
     * @param array<string, array{description: string, endpoints: array<string, array{method: string, url: string, description: string, parameters?: array<string, array{type: string, required: bool}>, urlparams?: array<string, array{type: string, required: bool}>, query_parameters?: array<string, array<string, array{type: string, required: bool}>>, response?: string|false}>}> $documentation
     */
    private function generateHtmlView(array $documentation): string
    {
        return $this->generateHtmlStructure($documentation);
    }

    /** @param array<string, mixed> $documentation */
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

    /** @param array<string, mixed> $documentation */
    private function generateTreeNavigation(array $documentation): string
    {
        if (!$this->parameterValidator->isValidDocumentation($documentation)) {
            return '<p>Invalid documentation structure</p>';
        }

        $html = '';
        foreach ($documentation as $serviceName => $service) {
            if (!is_array($service) || !isset($service['endpoints']) || !is_array($service['endpoints'])) {
                continue;
            }

            $methodsHtml = '';
            foreach ($service['endpoints'] as $methodName => $method) {
                if (!is_string($methodName) || !is_array($method) || !isset($method['method'])) {
                    continue;
                }

                $methodType = $this->parameterValidator->getMethodName($method);
                $methodsHtml .= $this->htmlHelper->generateMethodNavigation($serviceName, $methodName, $methodType);
            }

            $html .= $this->htmlHelper->generateServiceNavigation($serviceName, $methodsHtml);
        }
        return $html;
    }

    private function getStyles(): string
    {
        $path = __DIR__ . '/Documentation/documentation.css';
        if (is_file($path)) {
            $css = (string)file_get_contents($path);
            return $css;
        }
        return '';
    }

    /** @param array<string, mixed> $documentation */
    private function getJavaScript(array $documentation): string
    {
        $parameterTableJs = $this->parameterTableGenerator->generateJavaScriptFunction();
        $docJson = $this->formatJson(json_encode($documentation, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
        $external = '';
        $externalPath = __DIR__ . '/Documentation/documentation.js';
        if (is_file($externalPath)) {
            $external = (string)file_get_contents($externalPath);
        }

        $js = <<<'JS'
            var documentationData = __DOC_JSON__;
            
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
                        <div class="endpoint-description">
                            ${endpoint.description || 'No description available'}
                        </div>
                        <div class="endpoint-example">
                            <strong>Example:</strong> <code>${endpoint.example || 'No example documented by developer'}</code>
                        </div>
                        <div class="content-wrapper">
                            <div class="main-content">
                                <div class="response-section">
                                    <div class="response-code">
                                        <pre><code>${formatJson(endpoint.response)}</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="parameters">
                                <h3>Parameters</h3>
                                ${generateParameterTable(endpoint)}
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('endpoint-content').innerHTML = content;
            }

            __PARAM_TABLE_JS__

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

            Object.entries(documentationData).forEach(([endpointName, endpoint]) => {
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

        $js = str_replace('__DOC_JSON__', (string)$docJson, $js);
        $js = str_replace('__PARAM_TABLE_JS__', $parameterTableJs, $js);
        if ($external !== '') {
            $js .= "\n" . $external;
        }
        return $js;
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

    public function htmlResponse(): HtmlResponse
    {
        $generator = new ApiDocGenerator();
        $documentation = $generator->generate();
        $html = $this->generateHtmlView($documentation);
        return new HtmlResponse($html);
    }
} 
