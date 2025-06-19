<?php

namespace App\Models;

class Vendedor extends BaseClientModel
{
    protected $baseTableName = 'vendedores';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'user',
        'pass'
    ];

    protected $hidden = ['pass'];
}