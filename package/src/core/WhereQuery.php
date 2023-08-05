<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gemvc\Core;

use Gemvc\Helper\TypeHelper;

class WhereQuery
{
    /**
     * @var array<string,mixed>
     */
    public array $bind;
    public string $whereQuery;
    public string $orderBy;
    public string $limit;
    public ?bool $isCount;
    private object $_model;
    private ?bool $_isAll;

    public function __construct(object $model, ?int $count = null, ?bool $isAll = null)
    {
        if (isset($_POST['count']) || $count) {
            $this->isCount = true;
        }
        $this->_isAll = $isAll;
        $this->_model = $model;
        $this->bind = [];
        $this->whereBuilder();
        $this->_orderByBuilder();
        if (!$this->isCount && !$this->_isAll) {
            $this->_limitBuilder();
        }
    }

    public function conditionTranslator(string $key): string
    {
        return match ($key) {
            'bigger' => ' > ',
            'less' => ' < ',
            'bigger_eq' => ' >= ',
            'less_eq' => ' =< ',
            'not' => ' <> ',
            'null' => ' IS NULL ',
            'notnull' => ' IS NOT NULL ',
            'like' => ' LIKE ',
            'notlike' => ' NOT LIKE ',
            'between' => ' BETWEEN ',
            'notbetween' => ' NOT BETWEEN ',

            default => ' = ',
        };
    }

    public function safeCondition(string $key): bool
    {
        return 'bigger' === $key || 'less' === $key || 'bigger_eq' === $key || 'less_eq' === $key || 'not' === $key || 'null' === $key || 'notnull' === $key
            || 'like' === $key || 'notlike' === $key || 'between' === $key || 'notbetween' === $key || 'offset' === $key || 'limit' === $key || 'low' === $key || 'high' === $key;
    }

    protected function whereBuilder(): void
    {
        foreach ($this->_safeFilter() as $key => $arr_value) {
            $temp = '';
            $bind_key = ':'."{$key}";
            $condition = array_key_first($arr_value);
            if (' LIKE ' === $condition || ' NOT LIKE ' === $condition) {
                $temp = " {$key} {$condition} {$bind_key} ";
                $this->bind[$bind_key] = '%'.$arr_value[$condition].'%';
            } elseif (' BETWEEN ' === $condition || ' NOT BETWEEN ' === $condition) {
                /** @phpstan-ignore-next-line */
                $low = $condition['low'];

                /** @phpstan-ignore-next-line */
                $high = $condition['high'];
                $bind_key_low = ":{$key}".'low';
                $bind_key_high = ":{$key}".'high';
                $temp = " {$key} {$condition} {$bind_key_low} AND {$bind_key_high} ";
                $this->bind[$bind_key_low] = $low;
                $this->bind[$bind_key_high] = $high;
            } else {
                $temp = " {$key} {$condition} {$bind_key} ";
                $this->bind[$bind_key] = $arr_value[$condition];
            }

            ($this->whereQuery) ? $this->whereQuery .= " AND  {$temp} " : $this->whereQuery = $temp;
        }
    }

    private function _orderByBuilder(): void
    {
        if (isset($_POST['orderby']) && \is_array($_POST['orderby'])) {
            foreach ($_POST['orderby'] as $key => $value) {
                if (property_exists($this->_model, $key)) {
                    $value = ($value) ? 'DESC' : 'ASC';
                    ($this->orderBy) ? $this->orderBy .= " , {$key} {$value} " : $this->orderBy = " ORDER BY {$key} {$value}";
                }
            }
        }
        if (isset($_GET['orderby']) && \is_array($_GET['orderby'])) {
            foreach ($_GET['orderby'] as $key => $value) {
                if (property_exists($this->_model, $key)) {
                    $value = ($value) ? 'DESC' : 'ASC';
                    ($this->orderBy) ? $this->orderBy .= " , {$key} {$value} " : $this->orderBy = " ORDER BY {$key} {$value}";
                }
            }
        }
    }

    /**
     * @return array<int|string,array<string,mixed>>
     */
    private function _safeFilter(): array
    {
        $safe = [];
        if (isset($_POST['filter'])) {
            foreach ($_POST['filter'] as $key => $value) {
                if (property_exists($this->_model, $key)) {
                    if (\is_array($value)) {
                        foreach ($_POST['filter'][$key] as $condition => $targetValue) {
                            if ($this->safeCondition($condition)) {
                                $safe[$key][$this->conditionTranslator($condition)] = $targetValue;
                            }
                        }
                    } else {
                        $safe[$key]['='] = $_POST['filter'][$key];
                    }
                }
            }
        }
        if (isset($_GET['search'])) {
            foreach ($_GET['search'] as $key => $value) {
                if (property_exists($this->_model, $key)) {
                    if (\is_array($value)) {
                        foreach ($_GET['search'][$key] as $condition => $targetValue) {
                            if ($this->safeCondition($condition)) {
                                $safe[$key][$this->conditionTranslator($condition)] = $targetValue;
                            }
                        }
                    } else {
                        $safe[$key]['='] = $_GET['search'][$key];
                    }
                }
            }
        }

        return $safe;
    }

    private function _limitBuilder(): void
    {
        $limit = isset($_POST['page_items']) && TypeHelper::justInt($_POST['page_items']) ? $_POST['page_items'] : 10;
        $offset = isset($_GET['page']) && TypeHelper::justInt($_GET['page']) ? $_GET['page'] : 1;
        if (isset($_POST['page']) && TypeHelper::justInt($_POST['page'])) {
            $offset = $_POST['page'];
        }
        $offset = ($offset - 1) * $limit;
        $this->limit = ' LIMIT '.$offset.','.$limit;
    }
}
