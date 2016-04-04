<?php

namespace Coyote\Elasticsearch\Aggs\Job;

use Coyote\Elasticsearch\Aggs;
use Coyote\Elasticsearch\DslInterface;
use Coyote\Elasticsearch\QueryBuilderInterface;

class Remote extends Aggs\Terms implements DslInterface
{
    use Job;

    public function __construct()
    {
        parent::__construct('remote', 'is_remote');
    }

    /**
     * @param QueryBuilderInterface $queryBuilder
     * @return array
     */
    public function apply(QueryBuilderInterface $queryBuilder)
    {
        return $this->buildGlobal(parent::apply($queryBuilder));
    }
}