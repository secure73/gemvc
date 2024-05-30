<?php
namespace Gemvc\Database;

interface QueryBuilderInterface
{
    public function run(PdoQuery $pdoQuery): mixed;
}
