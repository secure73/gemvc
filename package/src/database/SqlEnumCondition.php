<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

enum SqlEnumCondition: string
{
    case Equal = ' = ';

    case Bigger = ' > ';

    case Less = ' < ';

    case BiggerEqual = ' >= ';

    case LessEqual = ' =< ';

    case IsNull = ' IS NULL ';

    case NotNull = 'IS NOT NULL';

    case Not = ' != ';

    case Like = ' LIKE ';

    case Descending = ' DESC ';

    case Ascending = ' ASC ';

    case Between = ' BETWEEN ';
}
