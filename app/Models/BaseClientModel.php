<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseClientModel extends Model
{
    protected $connection = 'client_db';
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (session('client_table_prefix')) {
            $this->table = session('client_table_prefix') . $this->getBaseTableName();
        }
    }

    /**
     * Get the base table name for the client model.
     *
     * @return string
     */
    abstract protected function getBaseTableName(): string;
}