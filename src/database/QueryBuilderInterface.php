<?php
namespace Gemvc\Database;

interface QueryBuilderInterface
{
    public function run(): mixed;
    public function getError(): null|string;
}
