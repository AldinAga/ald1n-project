<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerCrmNote extends Model
{
    protected $table = 'customer_crm_notes';

    protected $fillable = [
        'user_id', 'author_user_id', 'body',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'author_user_id' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
