<?php

namespace Gemvc\Core\Documentation;

/**
 * Parameter validation utilities for API documentation
 * Centralizes parameter validation logic and type checking
 */
class ParameterValidator
{
    /**
     * Validate that a parameter array has the required structure
     * 
     * @param mixed $param
     * @return bool
     */
    public function isValidParameter(mixed $param): bool
    {
        return is_array($param) && 
               isset($param['type']) && 
               isset($param['required']) &&
               is_string($param['type']) &&
               is_bool($param['required']);
    }

    /**
     * Validate that a method array has the required structure
     * 
     * @param mixed $method
     * @return bool
     */
    public function isValidMethod(mixed $method): bool
    {
        return is_array($method) && 
               isset($method['method']) && 
               is_string($method['method']);
    }

    /**
     * Validate that a service array has the required structure
     * 
     * @param mixed $service
     * @return bool
     */
    public function isValidService(mixed $service): bool
    {
        return is_array($service) && 
               isset($service['endpoints']) && 
               is_array($service['endpoints']);
    }

    /**
     * Get safe string value from mixed type
     * 
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public function getSafeString(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }
        
        if (is_scalar($value)) {
            return (string) $value;
        }
        
        return $default;
    }

    /**
     * Get safe array value from mixed type
     * 
     * @param mixed $value
     * @return array<string, mixed>
     */
    public function getSafeArray(mixed $value): array
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
     * Check if a method has parameters of a specific type
     * 
     * @param array<string, mixed> $method
     * @param string $paramType
     * @return bool
     */
    public function hasParameters(array $method, string $paramType): bool
    {
        return isset($method[$paramType]) && 
               is_array($method[$paramType]) && 
               !empty($method[$paramType]);
    }

    /**
     * Get parameters of a specific type safely
     * 
     * @param array<string, mixed> $method
     * @param string $paramType
     * @return array<string, array{type: string, required: bool}>
     */
    public function getParameters(array $method, string $paramType): array
    {
        if (!$this->hasParameters($method, $paramType)) {
            return [];
        }

        $params = $method[$paramType];
        if (!is_array($params)) {
            return [];
        }

        $validParams = [];
        foreach ($params as $name => $param) {
            if ($this->isValidParameter($param)) {
                $validParams[$name] = $param;
            }
        }

        return $validParams;
    }

    /**
     * Get query parameters safely
     * 
     * @param array<string, mixed> $method
     * @return array<string, mixed>
     */
    public function getQueryParameters(array $method): array
    {
        if (!isset($method['query_parameters']) || !is_array($method['query_parameters'])) {
            return [];
        }

        return $this->getSafeArray($method['query_parameters']);
    }

    /**
     * Get method name safely
     * 
     * @param array<string, mixed> $method
     * @return string
     */
    public function getMethodName(array $method): string
    {
        return $this->getSafeString($method['method'] ?? '', 'UNKNOWN');
    }

    /**
     * Get URL safely
     * 
     * @param array<string, mixed> $method
     * @return string
     */
    public function getUrl(array $method): string
    {
        return $this->getSafeString($method['url'] ?? '', '/');
    }

    /**
     * Get description safely
     * 
     * @param array<string, mixed> $method
     * @return string
     */
    public function getDescription(array $method): string
    {
        return $this->getSafeString($method['description'] ?? '', 'No description available');
    }

    /**
     * Get example safely
     * 
     * @param array<string, mixed> $method
     * @return string
     */
    public function getExample(array $method): string
    {
        return $this->getSafeString($method['example'] ?? '', 'No example documented by developer');
    }

    /**
     * Get response safely
     * 
     * @param array<string, mixed> $method
     * @return string
     */
    public function getResponse(array $method): string
    {
        $response = $method['response'] ?? '';
        
        if (is_string($response)) {
            return $response;
        }
        
        if (is_scalar($response)) {
            return (string) $response;
        }
        
        return 'No example response available';
    }

    /**
     * Validate documentation array structure
     * 
     * @param mixed $documentation
     * @return bool
     */
    public function isValidDocumentation(mixed $documentation): bool
    {
        if (!is_array($documentation)) {
            return false;
        }

        foreach ($documentation as $serviceName => $service) {
            if (!is_string($serviceName) || !$this->isValidService($service)) {
                return false;
            }

            if (is_array($service) && isset($service['endpoints']) && is_array($service['endpoints'])) {
                $endpoints = $service['endpoints'];
                foreach ($endpoints as $methodName => $method) {
                    if (!is_string($methodName) || !$this->isValidMethod($method)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
