<?php

namespace App\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\PostgresGrammar;

class NonTransactionalPostgresGrammar extends PostgresGrammar
{
    protected $transactions = false;
}
