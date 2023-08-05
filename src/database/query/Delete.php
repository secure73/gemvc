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

class Delete extends QueryProvider implements QueryBuilderInterface
{
    use WhereTrait;

    public ?int $result;

    public string $query;

    /**
     * @var array<mixed>
     */
    public $arrayBindValues = [];

    private string $table;

    /**
     * @var array<string>
     */
    private $whereConditions = [];

    private string $_query;

    public function __construct(string $table, string $connection = null)
    {
        $this->table = $table;
        parent::__construct($connection);
    }

    public function __toString(): string
    {
        $this->_query = 'DELETE FROM '.$this->table.' WHERE '.implode(' AND ', $this->whereConditions);

        return $this->_query;
    }

    public function run(): self
    {
        $this->result = $this->deleteQuery($this->_query, $this->arrayBindValues);

        return $this;
    }
}
