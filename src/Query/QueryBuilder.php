<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Query;

class QueryBuilder
{
    private $fields = [];
    private $conditions = [];
    private $from = [];
    private $offset = null;
    private $limit = null;
    private $orders = [];

    public function __toString(): string
    {
        $where = $this->conditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->conditions);
        $off = is_null($this->offset) ? '' : ' OFFSET ' . $this->offset;
        $lim = is_null($this->limit) ? '' : ' LIMIT ' . $this->limit;
        $order = $this->orders === [] ? '' : ' ORDER BY ' . implode(', ', $this->orders);
        return 'SELECT ' . implode(', ', $this->fields)
            . ' FROM ' . implode(', ', $this->from)
            . $where
            . $order
            . $off
            . $lim;
    }

    public function select(string ...$select): self
    {
        $this->fields = $select;
        return $this;
    }

    public function where(string ...$where): self
    {
        foreach ($where as $arg) {
            $this->conditions[] = $arg;
        }

        return $this;
    }

    public function from(string $table, ?string $alias = null): self
    {
        if ($alias === null) {
            $this->from[] = $table;
        } else {
            $this->from[] = "${table} AS ${alias}";
        }

        return $this;
    }

    public function offset(int $offsetValue): self
    {
        $this->offset = $offsetValue;
        return $this;
    }

    public function limit(int $limitValue): self
    {
        $this->limit = $limitValue;
        return $this;
    }

    public function orderBy(string ...$order): self
    {
        foreach ($order as $arg) {
            $this->orders[] = $arg;
        }

        return $this;
    }
}

?>