<?php

namespace App\Models;

use App\Models\Scopes\OrgScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['org_id', 'name', 'max_days'])]
class LeaveType extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new OrgScope());
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
