<?php

namespace App\Models;

use App\Models\Scopes\OrgScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['org_id', 'name'])]
class Designation extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new OrgScope());
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
