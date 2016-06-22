<?php

/**
 * This file is part of LazyPDO.
 *
 * (c) Alexey Karapetov <karapetov@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LazyPDO;

use PDOStatement;

class SimplePDOStatementDecorator extends PDOStatementDecorator
{
    /**
     * @var PDOStatement
     */
    private $pdoStatement;

    /**
     * SimplePDOStatementDecorator constructor.
     * @param PDOStatement $pdoStatement
     */
    public function __construct(PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;
    }

    /**
     * @return PDOStatement
     */
    protected function getPDOStatement()
    {
        return $this->pdoStatement;
    }
}
