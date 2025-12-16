<?php

namespace App\Models;

class Vendedor extends BaseClientModel
{
    protected $fillable = [
        'codigo',
        'nombre',
        'user',
        'pass'
    ];

    protected $hidden = ['pass'];

    protected function getBaseTableName(): string
    {
        return 'vendedores';
    }
}