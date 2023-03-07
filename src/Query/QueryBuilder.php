<?php
// Copyright © 2018-2023 Тепляшин Сергей Васильевич. Contacts: <sergio.teplyashin@gmail.com>
// License: https://opensource.org/licenses/GPL-3.0

namespace App\Query;

class QueryBuilder
{
    private $fields = [];
    private $conditions = [];
    private $from = null;
    private $fromAlias = null;
    private $offset = null;
    private $limit = null;
    private $orders = [];
    private $joins = [];
    private $unionQuery = null;
    private $withs = [];
    private $groups = [];

    public function __call($name, $args) {
        if ($name == 'leftJoin') {
            switch (count($args)) {
                case 2:
                    return call_user_func_array(array($this, 'leftJoinCondition'), $args);
                case 3:
                    if (gettype($args[0]) == 'object' && get_class($args[0]) == 'App\Query\QueryBuilder' ) {
                        return call_user_func_array(array($this, 'leftJoinQuery'), $args);
                    }
                    else {
                        return call_user_func_array(array($this, 'leftJoinFields'), $args);
                    }
            }
        }
    }

    public function __toString(): string
    {
        return $this->getQueryString();
    }

    public function getAlias()
    {
        return $this->fromAlias;
    }

    public function select(string ...$select): self
    {
        $this->fields = array_merge($this->fields, $select);
        return $this;
    }

    public function selectWithAlias(string ...$select): self
    {
        $this->fields = array_merge($this->fields, array_map(function($x) { return $this->getAlias() . '.' . $x; }, $select));
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
            $this->from = $table;
            $this->fromAlias = $table;
        } else {
            $this->from = "${table} AS ${alias}";
            $this->fromAlias = $alias;
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
            if (\str_contains($arg, '.')) {
                $this->orders[] = $arg;
            }
            else {
                $this->orders[] = "{$this->getAlias()}.$arg";
            }
        }

        return $this;
    }

    public function groupBy(string ...$groups): self
    {
        foreach ($groups as $arg) {
            $this->groups[] = $arg;
        }

        return $this;
    }

    private function leftJoinCondition(string $table, string $cond): self
    {
        $this->joins[] = "LEFT JOIN $table ON ($cond)";
        return $this;
    }

    private function leftJoinFields(string $table, string $left, string $right): self
    {
        $this->joins[] = "LEFT JOIN $table ON ($left = $right)";
        return $this;
    }

    private function leftJoinQuery(QueryBuilder $query, string $alias, string $cond): self
    {
        $this->joins[] = "LEFT JOIN ($query) AS $alias ON ($cond)";
        return $this;
    }

    public function innerJoin(string $table, string $cond): self
    {
        $this->joins[] = 'JOIN ' . $table . ' ON ' . $cond;
        return $this;
    }

    public function unionAll(QueryBuilder $query): self 
    {
        $this->unionQuery = $query;
        return $this;
    }

    public function with(QueryBuilder $query, string $alias, bool $recursive = false): self
    {
        $this->withs[] = array('query' => $query, 'alias' => $alias, 'recursive' => $recursive);
        return $this;
    }

    private function getQueryString() {
        $where = $this->conditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->conditions);
        $off = is_null($this->offset) ? '' : ' OFFSET ' . $this->offset;
        $lim = is_null($this->limit) ? '' : ' LIMIT ' . $this->limit;
        $order = $this->orders === [] ? '' : ' ORDER BY ' . implode(', ', $this->orders);
        $join = $this->joins === [] ? '' : ' ' . implode(' ', $this->joins);
        $union = is_null($this->unionQuery) ? '' : ' UNION ALL ' . $this->unionQuery;
        $groups = $this->groups === [] ? '' : ' GROUP BY ' . implode(', ', $this->groups);

        $with = '';
        if (count($this->withs) > 0) {
            $with = 'WITH';
            foreach ($this->withs as $w) {
                $rec = $w['recursive'] ? ' RECURSIVE ' : ' ';
                $with = $with . $rec . $w['alias'] . ' AS (' . $w['query'] . ')';
            }
        }

        return $with 
            . 'SELECT ' . implode(', ', $this->fields)
            . ' FROM ' . $this->from
            . $join
            . $where
            . $groups
            . $order
            . $off
            . $lim
            . $union;
    }
}

?>