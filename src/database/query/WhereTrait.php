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

trait WhereTrait
{
    public function whereEqual(string $columnName, int|float|string $value): self
    {
        $dotColumnName = ':' . $columnName;
        $this->whereConditions[] = $columnName . ' = ' . $dotColumnName;
        $this->arrayBindValues[$dotColumnName] = $value;

        return $this;
    }

    public function whereNull(string $columnName): self
    {
        $this->whereConditions[] = $columnName . ' IS NULL ';

        return $this;
    }

    public function whereNotNull(string $columnName): self
    {
        $this->whereConditions[] = $columnName . ' IS NOT NULL ';

        return $this;
    }

    public function whereLike(string $columnName, string $value): self
    {
        $this->whereConditions[] = $columnName . ' LIKE ' . ':' . $columnName;
        $this->arrayBindValues[':' . $columnName] = '%' . $value . '%';

        return $this;
    }

    public function whereLess(string $columnName, string $value): self
    {
        $this->whereConditions[] = $columnName . ' < ' . ':' . $columnName;
        $this->arrayBindValues[':' . $columnName] = $value;

        return $this;
    }

    public function whereLessEqual(string $columnName, string $value): self
    {
        $this->whereConditions[] = $columnName . ' =< ' . ':' . $columnName;
        $this->arrayBindValues[':' . $columnName] = $value;

        return $this;
    }

    public function whereBigger(string $columnName, string|int $value): self
    {
        $this->whereConditions[] = $columnName . ' > ' . ':' . $columnName;
        $this->arrayBindValues[':' . $columnName] = $value;

        return $this;
    }

    public function whereBiggerEqual(string $columnName, string $value): self
    {
        $this->whereConditions[] = $columnName . ' >= ' . ':' . $columnName;
        $this->arrayBindValues[':' . $columnName] = $value;

        return $this;
    }

    public function whereBetween(string $columnName, int|string|float $lowerBand, int|string|float $higherBand): self
    {
        $colLower = ':' . $columnName . 'lowerBand';
        $colHigher = ':' . $columnName . 'higerBand';

        $this->whereConditions[] = " {$columnName} BETWEEN {$colLower} AND {$colHigher} ";
        $this->arrayBindValues[$colLower] = $lowerBand;
        $this->arrayBindValues[$colHigher] = $higherBand;

        return $this;
    }
}
