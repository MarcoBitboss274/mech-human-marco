<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PrescriptionLybraAligner extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * The table associated with the model.
     */
    protected $table = 'prescription_lybra_aligner';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prescription_id',
        'cut_line',
        'note',
    ];

    /**
     * Prescription this lybra aligner details belongs to.
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

        $this->addMediaCollection('foto_del_sorriso_e_morso_del_paziente')
            ->singleFile();

        $this->addMediaCollection('ortopantomografia')
            ->singleFile();

        $this->addMediaCollection('rx_anteroposteriore_delle_ossa_mascellari')
            ->singleFile();
    }
}
