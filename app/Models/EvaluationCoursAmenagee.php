<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationCoursAmenagee extends Model
{
    //
    protected $table = 'evaluations_cours_amenagees'; 
    protected $fillable = [
        'txt_num_dossier',        
        'txt_nicad',
        'nbr_valeur_total_ca',
        'slt_cours_amenager_totale',
        'nbr_surface_ca_total',
        'slt_categorie_ca_total',
        'nbr_prix_metre_carre_ca_total',
        'nbr_coefficient_ca_total',
        'nbr_valeur_ca_total',
    ];

    public function terrain() {
        return $this->belongsTo(Terrain::class, 'txt_nicad', 'txt_nicad');
    }

    public function dossier() {
        return $this->belongsTo(Dossier::class, 'txt_num_dossier', 'txt_num_dossier');
    }
    
}
