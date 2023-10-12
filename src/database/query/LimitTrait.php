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

trait LimitTrait
{
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function first(int $count = 1, string $orderByColumn = 'id'): self
    {
        $this->orderBy($orderByColumn);
        $this->limit = $count;

        return $this;
    }

    public function last(int $count = 1, string $byColumn = 'id'): self
    {
        $this->orderBy($byColumn, true);
        $this->limit = $count;

        return $this;
    }

    private function limitMaker(): string
    {
        $limitQuery = '';
        if (!$this->offset && $this->limit) {
            $limitQuery = ' LIMIT ' . $this->limit;
        }
        if ($this->offset && $this->limit) {
            $limitQuery = ' LIMIT ' . $this->offset . ',' . $this->limit;
        }

        return $limitQuery;
    }
}
