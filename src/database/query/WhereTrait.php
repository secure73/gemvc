<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gemvc\Database\Query;

/**
 * Enhanced WHERE clause trait with parameter validation and consistent naming
 */
trait WhereTrait
{
    /**
     * Add WHERE column = value condition
     * 
     * @param string $columnName Column name
     * @param int|float|string $value Value to match
     * @return self For method chaining
     */
    public function whereEqual(string $columnName, int|float|string $value): self
    {
        if (empty(trim($columnName))) {
            return $this; // Skip invalid column names silently to maintain chain
        }
        
        $paramName = ':' . str_replace('.', '_', $columnName); // Handle table.column syntax
        $this->whereConditions[] = $columnName . ' = ' . $paramName;
        $this->arrayBindValues[$paramName] = $value;

        return $this;
    }

    /**
     * Add WHERE column IS NULL condition
     * 
     * @param string $columnName Column name
     * @return self For method chaining
     */
    public function whereNull(string $columnName): self
    {
        if (empty(trim($columnName))) {
            return $this;
        }
        
        $this->whereConditions[] = $columnName . ' IS NULL ';

        return $this;
    }

    /**
     * Add WHERE column IS NOT NULL condition
     * 
     * @param string $columnName Column name
     * @return self For method chaining
     */
    public function whereNotNull(string $columnName): self
    {
        if (empty(trim($columnName))) {
            return $this;
        }
        
        $this->whereConditions[] = $columnName . ' IS NOT NULL ';

        return $this;
    }

    /**
     * Add WHERE column LIKE %value% condition
     * 
     * @param string $columnName Column name
     * @param string $value Value to search for (will be wrapped with %)
     * @return self For method chaining
     */
    public function whereLike(string $columnName, string $value): self
    {
        if (empty(trim($columnName))) {
            return $this;
        }
        
        $paramName = ':' . str_replace('.', '_', $columnName);
        $this->whereConditions[] = $columnName . ' LIKE ' . $paramName;
        $this->arrayBindValues[$paramName] = '%' . $value . '%';

        return $this;
    }

    /**
     * Add WHERE column < value condition
     * 
     * @param string $columnName Column name
     * @param string|int|float $value Value to compare
     * @return self For method chaining
     */
    public function whereLess(string $columnName, string|int|float $value): self
    {
        if (empty(trim($columnName))) {
            return $this;
        }
        
        $paramName = ':' . str_replace('.', '_', $columnName);
        $this->whereConditions[] = $columnName . ' < ' . $paramName;
        $this->arrayBindValues[$paramName] = $value;

        return $this;
    }

    /**
     * Add WHERE column <= value condition
     * 
     * @param string $columnName Column name
     * @param string|int|float $value Value to compare
     * @return self For method chaining
     */
    public function whereLessEqual(string $columnName, string|int|float $value): self
    {
        if (empty(trim($columnName))) {
            return $this;
        }
        
        $paramName = ':' . str_replace('.', '_', $columnName);
        $this->whereConditions[] = $columnName . ' <= ' . $paramName;
        $this->arrayBindValues[$paramName] = $value;

        return $this;
    }

    /**
     * Add WHERE column > value condition
     * 
     * @param string $columnName Column name
     * @param string|int|float $value Value to compare
     * @return self For method chaining
     */
    public function whereBigger(string $columnName, string|int|float $value): self
    {
        if (empty(trim($columnName))) {
            return $this;
        }
        
        $paramName = ':' . str_replace('.', '_', $columnName);
        $this->whereConditions[] = $columnName . ' > ' . $paramName;
        $this->arrayBindValues[$paramName] = $value;

        return $this;
    }

    /**
     * Add WHERE column >= value condition
     * 
     * @param string $columnName Column name
     * @param string|int|float $value Value to compare
     * @return self For method chaining
     */
    public function whereBiggerEqual(string $columnName, string|int|float $value): self
    {
        if (empty(trim($columnName))) {
            return $this;
        }
        
        $paramName = ':' . str_replace('.', '_', $columnName);
        $this->whereConditions[] = $columnName . ' >= ' . $paramName;
        $this->arrayBindValues[$paramName] = $value;

        return $this;
    }

    /**
     * Add WHERE column BETWEEN lower AND upper condition
     * 
     * @param string $columnName Column name
     * @param int|string|float $lowerBand Lower bound value
     * @param int|string|float $higherBand Upper bound value (FIXED TYPO)
     * @return self For method chaining
     */
    public function whereBetween(string $columnName, int|string|float $lowerBand, int|string|float $higherBand): self
    {
        if (empty(trim($columnName))) {
            return $this;
        }
        
        $colLower = ':' . str_replace('.', '_', $columnName) . '_lowerBand';
        $colHigher = ':' . str_replace('.', '_', $columnName) . '_higherBand'; // FIXED: was "higerBand"

        $this->whereConditions[] = " {$columnName} BETWEEN {$colLower} AND {$colHigher} ";
        $this->arrayBindValues[$colLower] = $lowerBand;
        $this->arrayBindValues[$colHigher] = $higherBand;

        return $this;
    }

    /**
     * Add WHERE column IN (value1, value2, ...) condition
     * 
     * @param string $columnName Column name
     * @param array<mixed> $values Array of values for IN clause
     * @return self For method chaining
     */
    public function whereIn(string $columnName, array $values): self
    {
        if (empty(trim($columnName)) || empty($values)) {
            return $this;
        }
        
        $placeholders = [];
        foreach ($values as $index => $value) {
            $paramName = ':' . str_replace('.', '_', $columnName) . '_in_' . $index;
            $placeholders[] = $paramName;
            $this->arrayBindValues[$paramName] = $value;
        }
        
        $this->whereConditions[] = $columnName . ' IN (' . implode(', ', $placeholders) . ')';

        return $this;
    }

    /**
     * Add WHERE column NOT IN (value1, value2, ...) condition
     * 
     * @param string $columnName Column name
     * @param array<mixed> $values Array of values for NOT IN clause
     * @return self For method chaining
     */
    public function whereNotIn(string $columnName, array $values): self
    {
        if (empty(trim($columnName)) || empty($values)) {
            return $this;
        }
        
        $placeholders = [];
        foreach ($values as $index => $value) {
            $paramName = ':' . str_replace('.', '_', $columnName) . '_not_in_' . $index;
            $placeholders[] = $paramName;
            $this->arrayBindValues[$paramName] = $value;
        }
        
        $this->whereConditions[] = $columnName . ' NOT IN (' . implode(', ', $placeholders) . ')';

        return $this;
    }
}
