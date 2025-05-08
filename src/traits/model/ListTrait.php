<?php
namespace Gemvc\Traits\Model;
use Gemvc\Http\Request;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;
trait ListTrait
{
    /**
     * Define which fields are allowed for searching
     * @var array<string>
     */
    private array $searchable = [];

    /**
     * Define which fields are allowed for exact matching
     * @var array<string>
     */
    private array $filterable = [];

    /**
     * Define which fields are allowed for between queries
     * @var array<string>
     */
    private array $rangeable = [];

    /**
     * Define which fields are allowed for ordering
     * @var array<string>
     */
    private array $orderable = [];

    /**
     * Maximum allowed page size
     */
    private int $maxPageSize = 100;

    /**
     * Default page size if not specified
     */
    private int $defaultPageSize = 20;

    /**
     * Summary of list
     * @param \Gemvc\Http\Request $request
     * @return array<static>
     */
    public function list(Request $request):array
    {
        try {
            // Validate required properties
            $this->validateRequiredProperties();

            // Set default limit if not set
            if (!$this->getLimit()) {
                $this->setLimit($this->defaultPageSize);
            }

            // Handle pagination with validation
            $this->handlePagination($request);

            // Handle ordering with validation
            $this->handleOrdering($request);

            // Handle filters with validation
            $this->handleFilters($request);

            // Execute query and return response
            if ($this->getError()) {
                Response::badRequest($this->getError())->show();
                die();
            }
            return $this->select()->run();

        } catch (\Exception $e) {
            Response::internalError('An error occurred while processing the list request: ' . $e->getMessage())->show();
            die();
        }
    }

    /**
     * Validates that required properties are set
     * @throws \RuntimeException
     */
    protected function validateRequiredProperties(): void
    {
        if (!method_exists($this, 'getTable') || empty($this->getTable())) {
            throw new \RuntimeException('Table name must be defined in the model');
        }
    }

    /**
     * Handles pagination parameters
     */
    protected function handlePagination(Request $request): void
    {
        // Handle page number
        $page = isset($request->get['page']) ? (int)$request->get['page'] : 1;
        $page = max(1, $page); // Ensure page is at least 1
        $this->setPage($page);

        // Handle page size
        if (isset($request->get['per_page'])) {
            $perPage = (int)$request->get['per_page'];
            $perPage = min($perPage, $this->maxPageSize); // Limit maximum page size
            $perPage = max(1, $perPage); // Ensure at least 1
            $this->limit($perPage);
        }
    }

    /**
     * Handles ordering parameters
     */
    protected function handleOrdering(Request $request): void
    {
        if (!isset($request->get['orderby'])) {
            return;
        }

        $orderParts = explode(',', $request->get['orderby']);
        foreach ($orderParts as $order) {
            $parts = explode(':', $order);
            $column = trim($parts[0]);
            
            if (!$this->isFieldAllowed($column, 'order')) {
                continue;
            }

            // Sanitize direction
            $direction = isset($parts[1]) ? strtolower(trim($parts[1])) : 'desc';
            $this->orderBy($column, $direction === 'asc');
        }
    }

    /**
     * Handles all filter types (search, where, between)
     */
    protected function handleFilters(Request $request): void
    {
        // Handle search filters
        if (isset($request->get['search'])) {
            foreach ($request->get['search'] as $column => $value) {
                if (!$this->isFieldAllowed($column, 'search')) {
                    continue;
                }
                $this->whereLike($column, $this->sanitizeInput($value));
            }
        }

        // Handle between filters
        if (isset($request->get['between'])) {
            foreach ($request->get['between'] as $column => $range) {
                if (!$this->isFieldAllowed($column, 'range')) {
                    continue;
                }
                $values = explode(',', $range);
                if (count($values) === 2) {
                    $this->whereBetween(
                        $column,
                        $this->sanitizeInput($values[0]),
                        $this->sanitizeInput($values[1])
                    );
                }
            }
        }

        // Handle exact match filters
        if (isset($request->get['where'])) {
            foreach ($request->get['where'] as $column => $value) {
                if (!$this->isFieldAllowed($column, 'filter')) {
                    continue;
                }
                
                $value = $this->sanitizeInput($value);
                match($value) {
                    'null' => $this->whereNull($column),
                    'not_null' => $this->whereNotNull($column),
                    default => $this->where($column, $value)
                };
            }
        }
    }

    /**
     * Checks if a field is allowed for a specific operation type
     */
    protected function isFieldAllowed(string $field, string $type): bool
    {
        return match ($type) {
            'search' => in_array($field, $this->searchable),
            'filter' => in_array($field, $this->filterable),
            'range' => in_array($field, $this->rangeable),
            'order' => in_array($field, $this->orderable),
            default => false
        };
    }

    /**
     * Basic input sanitization
     */
    protected function sanitizeInput(mixed $input): mixed
    {
        if (is_string($input)) {
            // Remove any null bytes
            $input = str_replace(chr(0), '', $input);
            // Convert special characters to HTML entities
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }
}
