<?php

namespace Gemvc\Core\Documentation;

/**
 * Centralized parameter table generation for API documentation
 * Eliminates code duplication between PHP and JavaScript implementations
 */
class ParameterTableGenerator
{
    /**
     * Generate a complete parameter table for an endpoint
     * 
     * @param array<string, mixed> $method
     * @return string HTML table
     */
    public function generateParameterTable(array $method): string
    {
        $hasParams = $this->hasParameters($method, 'parameters');
        $hasGetParams = $this->hasParameters($method, 'get_parameters');
        $hasUrlParams = $this->hasParameters($method, 'urlparams');
        $hasQueryParams = $this->hasParameters($method, 'query_parameters');

        if (!$hasParams && !$hasGetParams && !$hasQueryParams && !$hasUrlParams) {
            return '<p>No parameters required</p>';
        }

        $html = '';

        // URL Parameters
        if ($hasUrlParams && isset($method['urlparams']) && is_array($method['urlparams'])) {
            $html .= $this->generateParameterSection('URL Parameters', $this->validateParameters($method['urlparams']));
        }

        // GET Parameters
        if ($hasGetParams && isset($method['get_parameters']) && is_array($method['get_parameters'])) {
            $html .= $this->generateParameterSection('GET Parameters', $this->validateParameters($method['get_parameters']));
        }

        // Body Parameters
        if ($hasParams && isset($method['parameters']) && is_array($method['parameters'])) {
            $html .= $this->generateParameterSection('Body Parameters', $this->validateParameters($method['parameters']));
        }

        // Query Parameters
        if ($hasQueryParams && isset($method['query_parameters']) && is_array($method['query_parameters'])) {
            $html .= $this->generateQueryParametersSection($this->getSafeArray($method['query_parameters']));
        }

        return $html;
    }

    /**
     * Generate a parameter section with header and table
     * 
     * @param string $title
     * @param array<string, array{type: string, required: bool}> $params
     * @return string HTML section
     */
    private function generateParameterSection(string $title, array $params): string
    {
        $html = "<h4>{$title}</h4>";
        $html .= $this->generateParameterTable($params);
        return $html;
    }

    /**
     * Generate query parameters section with subsections
     * 
     * @param array<string, mixed> $queryParams
     * @return string HTML section
     */
    private function generateQueryParametersSection(array $queryParams): string
    {
        $html = '<h4>Query Parameters</h4>';

        // Handle filters
        if (isset($queryParams['filters']) && is_array($queryParams['filters']) && !empty($queryParams['filters'])) {
            $html .= '<h5>Filters</h5>';
            $html .= $this->generateBasicParameterTable($this->validateParameters($queryParams['filters']));
        }

        // Handle sort
        if (isset($queryParams['sort']) && is_array($queryParams['sort']) && !empty($queryParams['sort'])) {
            $html .= '<h5>Sort</h5>';
            $html .= $this->generateBasicParameterTable($this->validateParameters($queryParams['sort']));
        }

        // Handle search
        if (isset($queryParams['search']) && is_array($queryParams['search']) && !empty($queryParams['search'])) {
            $html .= '<h5>Search</h5>';
            $html .= $this->generateBasicParameterTable($this->validateParameters($queryParams['search']));
        }

        return $html;
    }

    /**
     * Generate a basic parameter table
     * 
     * @param array<string, array{type: string, required: bool}> $params
     * @return string HTML table
     */
    private function generateBasicParameterTable(array $params): string
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
     * Validate and filter parameters to ensure correct type
     * 
     * @param array<mixed, mixed> $params
     * @return array<string, array{type: string, required: bool}>
     */
    private function validateParameters(array $params): array
    {
        $validParams = [];
        foreach ($params as $name => $param) {
            if (is_string($name) && is_array($param) && isset($param['type']) && isset($param['required']) && 
                is_string($param['type']) && is_bool($param['required'])) {
                $validParams[$name] = $param;
            }
        }
        return $validParams;
    }

    /**
     * Get safe array value from mixed type
     * 
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function getSafeArray(mixed $value): array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $val) {
                if (is_string($key)) {
                    $result[$key] = $val;
                }
            }
            return $result;
        }
        
        return [];
    }

    /**
     * Check if method has parameters of a specific type
     * 
     * @param array<string, mixed> $method
     * @param string $paramType
     * @return bool
     */
    private function hasParameters(array $method, string $paramType): bool
    {
        return isset($method[$paramType]) && 
               is_array($method[$paramType]) && 
               !empty($method[$paramType]);
    }

    /**
     * Generate JavaScript parameter table function
     * 
     * @return string JavaScript code
     */
    public function generateJavaScriptFunction(): string
    {
        return <<<'JS'
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
                            <td>${name}${required}</td>
                            <td>${param.type}</td>
                            <td>${param.required ? 'Yes' : 'No'}</td>
                        </tr>
                    `;
                }
                
                html += '</table>';
                return html;
            }
        JS;
    }
}
