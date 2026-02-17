<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Categoria extends Model
{
    //use HasFactory;
    protected $fillable = ['categoria'];
    
    public function insumos()
    {
        return $this->hasMany(Insumos::class, 'categoria_id');
    }
}
