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

class Update  implements QueryBuilderInterface
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

    private string $_table;

    private string $_query;

    /**
     * @var array<string>
     */
    private $columns = [];

    /**
     * @var array<string>
     */
    private array $whereConditions = [];

    public function __construct(string $table)
    {
        $this->_table = $table;
    }

    public function __toString(): string
    {
        $this->_query = 'UPDATE ' . $this->_table . ' SET ' . implode(', ', $this->columns)
            . ([] === $this->whereConditions ? '' : ' WHERE ' . implode(' AND ', $this->whereConditions));

        return $this->_query;
    }

    public function set(string $column, mixed $value): self
    {
        $colToUpdate = ':' . $column . 'ToUpdate';
        $this->columns[] = "{$column} = {$colToUpdate}";
        $this->arrayBindValues[$colToUpdate] = $value;

        return $this;
    }

    public function run(PdoQuery $pdoQuery): int|false
    {
        $query = $this->__toString();
        $result = $pdoQuery->updateQuery($query, $this->arrayBindValues);
        if(!$result) {
            return false;
        }
        return $result;
    }
}
