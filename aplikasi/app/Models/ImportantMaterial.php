<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportantMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'button_name',
        'file_path',
    ];
}
