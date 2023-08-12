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

class Select extends QueryProvider implements QueryBuilderInterface
{
    use LimitTrait;
    use WhereTrait;

    public mixed $result = null;

    public ?string $json = null;

    /**
     * @var array<object>
     */
    public array $object = [];

    public string $query = "";

    /**
     * @var array<mixed>
     */
    public array $arrayBindValues = [];

    /**
     * @var array<mixed>
     */
    private array $fields = [];

    /**
     * @var array<string>
     */
    private array $whereConditions = [];

    /**
     * @var array<string>
     */
    private array $order = [];

    /**
     * @var array<string>
     */
    private array $from = [];

    /**
     * @var array<string>
     */
    private array $innerJoin = [];

    /**
     * @var array<string>
     */
    private array $leftJoin = [];

    private ?int $limit = null;

    private ?int $offset = null;

    /**
     * @param array<mixed> $select
     */
    public function __construct(array $select, string $connection = null)
    {
        $this->fields = $select;
        parent::__construct($connection);
    }

    public function __toString(): string
    {
        $this->query = $this->selectMaker().implode(', ', $this->from)
            .([] === $this->leftJoin ? '' : ' LEFT JOIN '.implode(' LEFT JOIN ', $this->leftJoin))
            .([] === $this->innerJoin ? '' : ' INNER JOIN '.implode(' INNER JOIN ', $this->innerJoin))
            .([] === $this->whereConditions ? '' : ' WHERE '.implode(' AND ', $this->whereConditions))
            .([] === $this->order ? '' : ' ORDER BY '.implode(', ', $this->order))
            .$this->limitMaker();
        // echo $this->query;
        return $this->query;
    }

    public function select(string ...$select): self
    {
        foreach ($select as $arg) {
            $this->fields[] = $arg;
        }

        return $this;
    }

    public function from(string $table, ?string $alias = null): self
    {
        $this->from[] = null === $alias ? $table : "{$table} AS {$alias}";

        return $this;
    }

    public function orderBy(string $columnName, ?bool $descending = null): self
    {
        if ($descending) {
            $this->order[] = $columnName.' '.\SqlEnumCondition::Descending->value.' ';
        } else {
            $this->order[] = $columnName;
        }

        return $this;
    }

    public function innerJoin(string ...$join): self
    {
        $this->leftJoin = [];
        foreach ($join as $arg) {
            $this->innerJoin[] = $arg;
        }

        return $this;
    }

    public function leftJoin(string ...$join): self
    {
        $this->innerJoin = [];
        foreach ($join as $arg) {
            $this->leftJoin[] = $arg;
        }

        return $this;
    }

    public function run(): self
    {
        $this->result = $this->selectQuery($this->query, $this->arrayBindValues);

        return $this;
    }

    public function count(): self
    {
        $this->result = $this->countQuery($this->query, $this->arrayBindValues);

        return $this;
    }

    public function json(): self
    {
        $array = [];
        $result = $this->selectQuery($this->query, $this->arrayBindValues);
        if (\is_array($result)) {
            foreach ($result as $item) {
                $encoded = json_encode($item, JSON_PRETTY_PRINT);
                if ($encoded) {
                    $array[] = json_decode($encoded);
                }
            }
        }
        $result = json_encode($array, JSON_PRETTY_PRINT);
        if ($result) {
            $this->json = $result;
        }

        return $this;
    }

    /**
     * @param object $object
     *                       retrun array of Objects
     */
    public function object(object $object): self
    {
        $class = $object::class;
        $result = $this->selectQuery($this->query, $this->arrayBindValues);
        if (\is_array($result)) {
            foreach ($result as $item) {
                if (\is_array($item)) {
                    $temp_obj = new $class();
                    foreach ($item as $key => $value) {
                        if (property_exists($object, $key)) {
                            $temp_obj->{$key} = $value;
                        }
                    }
                    $this->object[] = $temp_obj;
                }
            }
        }

        return $this;
    }

    private function selectMaker(): string
    {
        if (null !== $this->fields && \count($this->fields)) {
            return 'SELECT '.implode(', ', $this->fields).' FROM ';
        }

        return 'SELECT * FROM ';
    }
}