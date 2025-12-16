<?php

namespace App\Models;

class Mozo extends BaseClientModel
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
        return 'mozos';
    }
}