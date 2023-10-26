<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

 namespace GemLibrary\Database\Query;

use GemLibrary\Database\PdoQuery;
use GemLibrary\Database\QueryBuilderInterface;

class Insert implements QueryBuilderInterface
{
    /**
     * @var null|int
     */
    public $result;

    private string $_table;

    private string $_query;

    /**
     * @var array<string>
     */
    private $columns = [];

    /**
     * @var array<string>
     */
    private $binds = [];

    /**
     * @var array<mixed>
     */
    private $values = [];

    /**
     * @var array<mixed>
     */
    private $keyValue = [];

    public function __construct(string $table)
    {
        $this->_table = $table;
        $this->_query = '';
    }

    public function __toString(): string
    {
        $this->_query = 'INSERT INTO ' . $this->_table
            . ' (' . implode(', ', $this->columns) . ') VALUES (' . implode(', ', $this->binds) . ')';

        return $this->_query;
    }

    public function columns(string ...$columns): self
    {
        $this->columns = $columns;
        foreach ($columns as $column) {
            $this->binds[] = ":{$column}";
        }

        return $this;
    }

    public function values(mixed ...$values): self
    {
        foreach ($values as $arg) {
            $this->values[] = $arg;
        }
        if (\count($this->binds) === \count($this->values)) {
            foreach ($this->binds as $key => $item) {
                $this->keyValue[$item] = $this->values[$key];
            }
        }

        return $this;
    }

    public function run(PdoQuery $queryProvider): self
    {
        $this->result = $queryProvider->insertQuery($this->_query, $this->keyValue);

        return $this;
    }
}
