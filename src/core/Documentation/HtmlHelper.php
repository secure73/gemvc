<?php

namespace Gemvc\Core\Documentation;

/**
 * HTML generation utilities for API documentation
 * Centralizes common HTML patterns and reduces duplication
 */
class HtmlHelper
{
    /**
     * Generate method class name for CSS styling
     * 
     * @param string $method
     * @return string
     */
    public function getMethodClass(string $method): string
    {
        return strtolower($method);
    }

    /**
     * Generate required parameter markup
     * 
     * @param bool $required
     * @return string
     */
    public function getRequiredMarkup(bool $required): string
    {
        return $required ? '<span class="required">*</span>' : '';
    }

    /**
     * Generate method icon HTML
     * 
     * @param string $method
     * @return string
     */
    public function generateMethodIcon(string $method): string
    {
        $methodClass = $this->getMethodClass($method);
        return "<span class=\"method-icon method-{$methodClass}\">{$method}</span>";
    }

    /**
     * Generate method badge HTML
     * 
     * @param string $method
     * @return string
     */
    public function generateMethodBadge(string $method): string
    {
        $methodClass = $this->getMethodClass($method);
        return "<span class=\"method method-{$methodClass}\">{$method}</span>";
    }

    /**
     * Generate endpoint header HTML
     * 
     * @param string $method
     * @param string $url
     * @return string
     */
    public function generateEndpointHeader(string $method, string $url): string
    {
        $methodBadge = $this->generateMethodBadge($method);
        return <<<HTML
            <div class="endpoint-header">
                {$methodBadge}
                <span class="url">{$url}</span>
            </div>
        HTML;
    }

    /**
     * Generate service navigation item HTML
     * 
     * @param string $serviceName
     * @param string $methodsHtml
     * @return string
     */
    public function generateServiceNavigation(string $serviceName, string $methodsHtml): string
    {
        return <<<HTML
            <div class="tree-item">
                <div class="service-name" onclick="toggleService(this)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                    {$serviceName}
                </div>
                <div class="service-methods" style="display: none;">
                    {$methodsHtml}
                </div>
            </div>
        HTML;
    }

    /**
     * Generate method navigation item HTML
     * 
     * @param string $serviceName
     * @param string $methodName
     * @param string $method
     * @return string
     */
    public function generateMethodNavigation(string $serviceName, string $methodName, string $method): string
    {
        $methodClass = $this->getMethodClass($method);
        $methodIcon = $this->generateMethodIcon($method);
        
        return <<<HTML
            <div class="method-item" onclick="showEndpoint('{$serviceName}', '{$methodName}')">
                {$methodIcon}
                {$methodName}
            </div>
        HTML;
    }

    /**
     * Generate parameter table row HTML
     * 
     * @param string $name
     * @param string $type
     * @param bool $required
     * @return string
     */
    public function generateParameterRow(string $name, string $type, bool $required): string
    {
        $requiredMarkup = $this->getRequiredMarkup($required);
        $requiredText = $required ? 'Yes' : 'No';
        
        return sprintf(
            '<tr><td>%s%s</td><td>%s</td><td>%s</td></tr>',
            htmlspecialchars($name),
            $requiredMarkup,
            htmlspecialchars($type),
            $requiredText
        );
    }

    /**
     * Generate table header HTML
     * 
     * @return string
     */
    public function generateTableHeader(): string
    {
        return '<tr><th>Parameter</th><th>Type</th><th>Required</th></tr>';
    }

    /**
     * Generate table wrapper HTML
     * 
     * @param string $content
     * @return string
     */
    public function generateTableWrapper(string $content): string
    {
        return "<table class=\"parameter-table\">{$content}</table>";
    }

    /**
     * Generate section header HTML
     * 
     * @param string $title
     * @param int $level
     * @return string
     */
    public function generateSectionHeader(string $title, int $level = 4): string
    {
        $tag = "h{$level}";
        return "<{$tag}>{$title}</{$tag}>";
    }

    /**
     * Generate description HTML
     * 
     * @param string $description
     * @return string
     */
    public function generateDescription(string $description): string
    {
        if (empty(trim($description))) {
            return '<div class="endpoint-description" style="display: none;"></div>';
        }
        
        return <<<HTML
            <div class="endpoint-description">
                {$description}
            </div>
        HTML;
    }

    /**
     * Generate example HTML
     * 
     * @param string $example
     * @return string
     */
    public function generateExample(string $example): string
    {
        if (empty(trim($example))) {
            $example = 'No example documented by developer';
        }
        
        return <<<HTML
            <div class="endpoint-example">
                <strong>Example:</strong> <code>{$example}</code>
            </div>
        HTML;
    }

    /**
     * Generate response section HTML
     * 
     * @param string $response
     * @return string
     */
    public function generateResponseSection(string $response): string
    {
        return <<<HTML
            <div class="response-section">
                <div class="response-code">
                    <pre><code>{$response}</code></pre>
                </div>
            </div>
        HTML;
    }

    /**
     * Generate content wrapper HTML
     * 
     * @param string $mainContent
     * @param string $parameters
     * @return string
     */
    public function generateContentWrapper(string $mainContent, string $parameters): string
    {
        return <<<HTML
            <div class="content-wrapper">
                <div class="main-content">
                    {$mainContent}
                </div>
                <div class="parameters">
                    <h3>Parameters</h3>
                    {$parameters}
                </div>
            </div>
        HTML;
    }
}
