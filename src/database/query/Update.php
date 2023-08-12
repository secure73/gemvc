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

use Gemvc\Database\QueryBuilderInterface;
use Gemvc\Database\QueryProvider;

class Update extends QueryProvider implements QueryBuilderInterface
{
    use WhereTrait;

    public ?int $result;

    /**
     * @var array<mixed>
     */
    public array $values = [];

    /**
     * @var array<mixed>
     */
    public array $arrayBindValues = [];

    private string $table;

    private string $_query;

    /**
     * @var array<string>
     */
    private $columns = [];

    /**
     * @var array<string>
     */
    private array $whereConditions = [];

    public function __construct(string $table, string $connection = null)
    {
        $this->table = $table;
        parent::__construct($connection);
    }

    public function __toString(): string
    {
        $this->_query = 'UPDATE '.$this->table.' SET '.implode(', ', $this->columns)
        .([] === $this->whereConditions ? '' : ' WHERE '.implode(' AND ', $this->whereConditions));

        return $this->_query;
    }

    public function set(string $column, mixed $value): self
    {
        $colToUpdate = ':'.$column.'ToUpdate';
        $this->columns[] = "{$column} = {$colToUpdate}";
        $this->arrayBindValues[$colToUpdate] = $value;

        return $this;
    }

    public function run(): self
    {
        $this->result = $this->updateQuery($this->_query, $this->arrayBindValues);

        return $this;
    }
}