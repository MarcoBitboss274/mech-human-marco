<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PrescriptionProsthesis extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * The table associated with the model.
     */
    protected $table = 'prescription_prosthesis';

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
        '3d_normal_model',
        '3d_excellent_model',
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
     * Prescription this prosthesis details belongs to.
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
        $this->addMediaCollection('scansione_intraorale')
            ->singleFile();

        $this->addMediaCollection('articolazione')
            ->singleFile();

        $this->addMediaCollection('foto_con_campione_colore')
            ->singleFile();

        $this->addMediaCollection('foto_del_sorriso')
            ->singleFile();

        $this->addMediaCollection('scansione_e_foto_del_provvisorio')
            ->singleFile();
    }
}
