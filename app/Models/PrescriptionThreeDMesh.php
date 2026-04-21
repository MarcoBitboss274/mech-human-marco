<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PrescriptionThreeDMesh extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * The table associated with the model.
     */
    protected $table = 'prescription_3d_mesh';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prescription_id',
        'dimension',
        'odontogram',
        'outer_finish',
        'inner_finish',
        'pattern',
        'stress_breakers',
        '3d_model',
        'screw_diameter',
        'shared_project_note',
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
            'screw_diameter' => 'float',
        ];
    }

    /**
     * Prescription this 3D mesh details belongs to.
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

        $this->addMediaCollection('cbct_allineabile_con_la_scansione_rilevata')
            ->singleFile();

        $this->addMediaCollection('ceratura_diagnostica')
            ->singleFile();

        $this->addMediaCollection('scansione_facciale_o_foto_del_sorriso')
            ->singleFile();
    }
}