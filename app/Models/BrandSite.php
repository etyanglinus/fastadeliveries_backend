<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BrandSite extends Model
{
    protected $table = 'brandsite_sites';

    protected $fillable = ['store_id'];

    protected $casts = ['draft' => 'array', 'revision' => 'integer', 'published_revision' => 'integer'];

    public function recordEvent(string $actorType, int $actorId, string $action, ?int $revision = null): void
    {
        DB::table('brandsite_events')->insert([
            'site_id' => $this->id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'revision' => $revision,
            'created_at' => now(),
        ]);
    }
}
