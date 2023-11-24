<?php
namespace GemLibrary\Database;

interface QueryBuilderInterface
{
    public function run(PdoQuery $pdoQuery): mixed;
}
