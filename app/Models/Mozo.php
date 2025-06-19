<?php

namespace App\Models;

class Mozo extends BaseClientModel
{
    protected $baseTableName = 'mozos';
    
    protected $fillable = [
        'codigo',
        'nombre',
        'user',
        'pass'
    ];

    protected $hidden = ['pass'];
}