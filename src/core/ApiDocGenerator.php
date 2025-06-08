<?php

namespace Gemvc\Core;

use ReflectionClass;
use ReflectionMethod;

/**
 * @template T of object
 */
class ApiDocGenerator
{
    /**
     * @var array<string, array{
     *     description: string,
     *     endpoints: array<string, array{
     *         method: string,
     *         url: string,
     *         description: string,
     *         parameters?: array<string, array{type: string, required: bool}>,
     *         urlparams?: array<string, array{type: string, required: bool}>,
     *         query_parameters?: array<string, array<string, array{type: string, required: bool}>>,
     *         response?: string
     *     }>
     * }>
     */
    private array $docs = [];
    private string $apiPath;

    public function __construct(string $apiPath = 'app/api')
    {
        $this->apiPath = $apiPath;
        
        if (!is_dir($this->apiPath)) {
            throw new \RuntimeException(sprintf('API directory "%s" does not exist', $this->apiPath));
        }
    }

    /**
     * @return array<string, array{
     *     description: string,
     *     endpoints: array<string, array{
     *         method: string,
     *         url: string,
     *         description: string,
     *         parameters?: array<string, array{type: string, required: bool}>,
     *         urlparams?: array<string, array{type: string, required: bool}>,
     *         query_parameters?: array<string, array<string, array{type: string, required: bool}>>,
     *         response?: string
     *     }>
     * }>
     */
    public function generate(): array
    {
        $apiFiles = $this->scanApiDirectory();
        
        foreach ($apiFiles as $file) {
            $this->processApiClass($file);
        }

        return $this->docs;
    }

    /**
     * @return array<int, string>
     */
    private function scanApiDirectory(): array
    {
        try {
            $files = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->apiPath)
            );

            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }

            return $files;
        } catch (\UnexpectedValueException $e) {
            throw new \RuntimeException(sprintf('Failed to scan API directory: %s', $e->getMessage()));
        }
    }

    private function processApiClass(string $filePath): void
    {
        $className = $this->getClassNameFromFile($filePath);
        if (!$className) return;

        /** @var class-string<T> $className */
        $reflection = new ReflectionClass($className);
        
        if ($this->isHidden($reflection)) {
            return;
        }

        $endpoint = $this->getEndpointName($reflection->getShortName());
        
        $this->docs[$endpoint] = [
            'description' => $this->getClassDocComment($reflection),
            'endpoints' => $this->getEndpoints($reflection)
        ];
    }

    /**
     * @param ReflectionClass<T> $reflection
     */
    private function isHidden(ReflectionClass $reflection): bool
    {
        $docComment = $reflection->getDocComment();
        if ($docComment === false) {
            return false;
        }
        return str_contains($docComment, '@hidden');
    }

    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = (string)file_get_contents($filePath);
        
        $matches = [];
        if (!preg_match('/namespace\s+(.+?);/s', $content, $matches)) {
            return null;
        }

        $namespace = $matches[1];
        $className = basename($filePath, '.php');
        return $namespace . '\\' . $className;
    }

    private function getEndpointName(string $className): string
    {
        // Convert first character to lowercase, keep the rest as is
        return lcfirst($className);
    }

    /**
     * @param ReflectionClass<T> $reflection
     * @return array<string, array{
     *     method: string,
     *     url: string,
     *     description: string,
     *     parameters?: array<string, array{type: string, required: bool}>,
     *     urlparams?: array<string, array{type: string, required: bool}>,
     *     query_parameters?: array<string, array<string, array{type: string, required: bool}>>,
     *     response?: string
     * }>
     */
    private function getEndpoints(ReflectionClass $reflection): array
    {
        $endpoints = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getName() === '__construct' || $method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            $docComment = $method->getDocComment();
            if ($docComment !== false && str_contains($docComment, '@hidden')) {
                continue;
            }

            $endpoints[$method->getName()] = $this->getMethodDetails($method, $reflection);
        }

        return $endpoints;
    }

    /**
     * @param ReflectionClass<T> $class
     * @return array{method: string, url: string, description: string, parameters?: array<string, array{type: string, required: bool}>, urlparams?: array<string, array{type: string, required: bool}>, query_parameters?: array<string, array<string, array{type: string, required: bool}>>, response?: string}
     */
    private function getMethodDetails(ReflectionMethod $method, ReflectionClass $class): array
    {
        $details = [
            'method' => $this->getHttpMethodFromDoc($method),
            'url' => $this->getEndpointUrl($class->getShortName(), $method->getName()),
            'description' => $this->getMethodDocComment($method) ?: 'No description available',
            'example' => $this->getMethodExample($method) ?: 'No example documented by developer'
        ];

        // Get URL parameters from @urlparams
        $docComment = $method->getDocComment();
        if ($docComment !== false && preg_match('/@urlparams\s+(.+)$/m', $docComment, $matches)) {
            $details['urlparams'] = $this->parseUrlParams($matches[1]);
        }

        // Get method file content
        $methodFile = $method->getFileName();
        if ($methodFile !== false) {
            $content = (string)file_get_contents($methodFile);
            
            // Get the method's content
            $lines = explode("\n", $content);
            $methodContent = implode("\n", array_slice(
                $lines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1
            ));
            
            // Get validation rules from definePostSchema within the method scope
            if (preg_match('/definePostSchema\(\s*\[\s*(.*?)\s*\]\s*\)/s', $methodContent, $matches)) {
                $details['parameters'] = $this->parseValidationRules($matches[1]);
            }
            
            // Get validation rules from defineGetSchema within the method scope
            if (preg_match('/defineGetSchema\(\s*\[\s*(.*?)\s*\]\s*\)/s', $methodContent, $matches)) {
                $details['get_parameters'] = $this->parseValidationRules($matches[1]);
            }

            // Get query parameters from filterable, sortable, and findable
            if ($method->getName() === 'list') {
                $details['query_parameters'] = $this->parseQueryParameters($methodContent);
            }
        }

        // Add mock response if available
        if (method_exists($class->getName(), 'mockResponse')) {
            $mockData = $class->getName()::mockResponse($method->getName());
            $encoded = json_encode($mockData, JSON_PRETTY_PRINT);
            $details['response'] = $encoded === false ? '{}' : $encoded;
        }

        // Parse @parameter annotations
        if (preg_match_all('/@parameter\s+(\w+)\s+(\w+)\s+(required|optional)/', $docComment, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $details['url_parameters'][$matches[1][$i]] = [
                    'type' => $matches[2][$i],
                    'required' => $matches[3][$i] === 'required'
                ];
            }
        }

        return $details;
    }

    private function getHttpMethodFromDoc(ReflectionMethod $method): string
    {
        $docComment = $method->getDocComment();
        /**@phpstan-ignore-next-line */
        if (preg_match('/@http\s+(GET|POST|PUT|DELETE|PATCH)/i', $docComment, $matches)) {
            return strtoupper($matches[1]);
        }
        return 'POST'; // default to POST if no method specified
    }

    private function getEndpointUrl(string $className, string $methodName): string
    {
        $baseUrl = $this->getEndpointName($className);
        return "/{$baseUrl}/{$methodName}";
    }

    /**
     * @return array<string, array{type: string, required: bool}>
     */
    private function parseValidationRules(string $rules): array
    {
        $parameters = [];
        // Updated pattern to handle whitespace and different quote styles
        preg_match_all("/['\"]([^'\"]+)['\"]\\s*=>\\s*['\"]([^'\"]+)['\"]/", $rules, $matches);
        
        for ($i = 0; $i < count($matches[1]); $i++) {
            $name = $matches[1][$i];
            $type = $matches[2][$i];
            $required = !str_starts_with($name, '?');
            $name = ltrim($name, '?');
            
            $parameters[$name] = [
                'type' => $type,
                'required' => $required
            ];
        }

        return $parameters;
    }

    /**
     * @return array<string, array<string, array{type: string, required: bool}>>
     */
    private function parseQueryParameters(string $content): array
    {
        $params = [
            'filters' => [],
            'sort' => [],
            'search' => []
        ];

        // Parse filterable parameters
        if (preg_match('/filterable\(\[(.*?)\]\)/s', $content, $matches)) {
            $params['filters'] = $this->parseValidationRulesAsOptional($matches[1]);
        }

        // Parse sortable parameters
        if (preg_match('/sortable\(\[(.*?)\]\)/s', $content, $matches)) {
            $params['sort'] = $this->parseValidationRulesAsOptional($matches[1]);
        }

        // Parse findable parameters
        if (preg_match('/findable\(\[(.*?)\]\)/s', $content, $matches)) {
            $params['search'] = $this->parseValidationRulesAsOptional($matches[1]);
        }

        return $params;
    }

    /**
     * @return array<string, array{type: string, required: bool}>
     */
    private function parseValidationRulesAsOptional(string $rules): array
    {
        $parameters = [];
        preg_match_all("/['\"]([^'\"]+)['\"]\\s*=>\\s*['\"]([^'\"]+)['\"]/", $rules, $matches);
        
        for ($i = 0; $i < count($matches[1]); $i++) {
            $name = $matches[1][$i];
            $type = $matches[2][$i];
            $name = ltrim($name, '?');
            
            $parameters[$name] = [
                'type' => $type,
                'required' => false  // All query parameters are optional
            ];
        }

        return $parameters;
    }

    /**
     * @param ReflectionClass<T> $reflection
     */
    private function getClassDocComment(ReflectionClass $reflection): string
    {
        $docComment = $reflection->getDocComment();
        return $this->formatDocComment($docComment === false ? null : $docComment);
    }

    private function getMethodDocComment(ReflectionMethod $method): string
    {
        $docComment = $method->getDocComment();
        if ($docComment === false) return '';
        
        // Look for @description tag and capture only the description text
        if (preg_match('/@description\s+([^\n@]+)/', $docComment, $matches)) {
            return trim($matches[1]);
        }
        
        return '';  // Return empty if no description found
    }

    private function getMethodExample(ReflectionMethod $method): string
    {
        $docComment = $method->getDocComment();
        if ($docComment === false) return '';
        
        // Look for @example tag and capture the example text
        if (preg_match('/@example\s+([^\n@]+)/', $docComment, $matches)) {
            return trim($matches[1]);
        }
        
        return '';  // Return empty if no example found
    }

    private function formatDocComment(?string $docComment): string
    {
        if ($docComment === null) return '';
        
        // Remove comment markers and extra whitespace
        $docComment = preg_replace('/^\s*\/\*+\s*|^\s*\*+\/\s*|^\s*\*\s*/m', '', $docComment);
        return trim($docComment ?? '');
    }

    /**
     * @return array<string, array{type: string, required: bool}>
     */
    private function parseUrlParams(string $params): array
    {
        $parameters = [];
        $parts = explode(',', $params);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/(\?)?([^=]+)=(\w+)/', $part, $matches)) {
                $required = empty($matches[1]); // If ? is present, it's optional
                $name = trim($matches[2]);
                $type = trim($matches[3]);
                
                $parameters[$name] = [
                    'type' => $type,
                    'required' => $required
                ];
            }
        }

        return $parameters;
    }

    private function validateDescription(string $description): string
    {
        // Remove any HTML tags for security
        $description = strip_tags($description);
        
        // Limit description length if too long
        $maxLength = 2000;
        if (strlen($description) > $maxLength) {
            $description = substr($description, 0, $maxLength) . '...';
        }
        
        return $description;
    }
} 
