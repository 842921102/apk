<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'cover_image',
    'images',
    'price',
    'original_price',
    'stock',
    'sales_count',
    'description',
    'detail_content',
    'status',
    'sort',
])]
class Product extends Model
{
    use SoftDeletes;

    protected $casts = [
        'images' => 'array',
        'price' => 'integer',
        'original_price' => 'integer',
        'stock' => 'integer',
        'sales_count' => 'integer',
        'sort' => 'integer',
    ];
}
