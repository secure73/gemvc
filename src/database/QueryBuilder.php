<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gemvc\Database;

use Gemvc\Database\Query\Delete;
use Gemvc\Database\Query\Insert;
use Gemvc\Database\Query\Select;
use Gemvc\Database\Query\Update;

class QueryBuilder
{
    public string $connectionName;

    public function __construct(?string $connectionName = null)
    {
        if ($connectionName) {
            $this->connectionName = $connectionName;
        } else {
            $this->connectionName = DEFAULT_CONNECTION_NAME;
        }
    }

    public static function select(string ...$select): Select
    {
        return new Select($select);
    }

    /**
     * @param string $intoTable Table Name
     */
    public static function insert(string $intoTable , string $connectionName): Insert
    {
        return new Insert($intoTable , $connectionName);
    }

    public static function update(string $table): Update
    {
        return new Update($table);
    }

    public static function delete(string $table): Delete
    {
        return new Delete($table);
    }
}
