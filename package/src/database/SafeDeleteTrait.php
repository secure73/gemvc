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

/**
 * @requires deleted_at in Database table also ClassTable
 */
trait SafeDeleteTrait
{
    public function safeDelete(int $id = null): int|null
    {
        if (!$id) {
            $id = $this->id;
        }
        $table = $this->getTable();
        $query = "UPDATE {$table} SET deleted_at = NOW() WHERE id = :id";

        return $this->updateQuery($query, [':id' => $id]);
    }

    public function restore(int $id = null): int|null
    {
        if (!$id) {
            $id = $this->id;
        }
        $query = "UPDATE {$this->table} SET deleted_at = NULL WHERE id = :id";

        return $this->updateQuery($query, []);
    }
}
