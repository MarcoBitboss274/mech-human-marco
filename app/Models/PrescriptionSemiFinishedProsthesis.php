<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PrescriptionSemiFinishedProsthesis extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * The table associated with the model.
     */
    protected $table = 'prescription_semi_finished_prostheses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prescription_id',
        'typology',
        'crowns_and_bridges_details',
        'full_bridge_details',
        'odontogram',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'odontogram' => 'array',
        ];
    }

    /**
     * Prescription this semi-finished prosthesis details belongs to.
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('progetto_in_stl_da_fresare_oppure_scansione_digitale_completa')
            ->singleFile();

        $this->addMediaCollection('scansione_del_provvisorio')
            ->singleFile();
    }
}
