<?php

namespace React\MySQL\Commands;

use React\MySQL\Io\Query;

/**
 * @internal
 */
class QueryCommand extends AbstractCommand
{
    public $query;
    public $fields;
    public $insertId;
    public $affectedRows;
    public $warningCount;

    public function getId()
    {
        return self::QUERY;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery($query)
    {
        if ($query instanceof Query) {
            $this->query = $query;
        } elseif (is_string($query)) {
            $this->query = new Query($query);
        } else {
            throw new \InvalidArgumentException('Invalid argument type of query specified.');
        }
    }

    public function getSql()
    {
        $query = $this->query;

        if ($query instanceof Query) {
            return $query->getSql();
        }

        return $query;
    }
}
