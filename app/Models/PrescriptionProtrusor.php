<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PrescriptionProtrusor extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * The table associated with the model.
     */
    protected $table = 'prescription_protrusor';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prescription_id',
        'protrusor_typology',
        'odontogram',
        'jig',
        'remaining_upper_teeth',
        'remaining_lower_teeth',
        'transpalatal_arch',
        'mandibular_advancement',
        'mandibular_advancement_2',
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
            'mandibular_advancement' => 'float',
            'mandibular_advancement_2' => 'float',
        ];
    }

    /**
     * Prescription this protrusor details belongs to.
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

        $this->addMediaCollection('rilevazione_dell_avanzamento_mandibolare_con_occlusione')
            ->singleFile();
    }
}
