<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $connection = 'config';
    protected $table = 'config_clientes';
    
    protected $fillable = [
        'id',
        'mail',
        'nombre',
        'nomemp',
        'activo',
        'vto',
        'code',
        'base',
        'pack'
    ];

    protected $hidden = ['code'];

    public function getTablePrefix()
    {
        if($this->pack == 'GE3') {
            $prefix = 'ge_';
        } else {
            $prefix = 're_';
        }
        //$prefix = strtolower($this->pack) == 'RE3' ? 're_' : 'ge_';
        return $prefix . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function esRestaurante()
    {
        return strtolower($this->pack) == 'RE3';
    }

    public function esPyme()
    {
        return strtolower($this->pack) == 'GE3';
    }
}