<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationCloture extends Model
{
    //
    protected $table = 'evaluations_clotures';
    protected $fillable = [
        'txt_num_dossier',
        'txt_nicad',
        'slt_cours_amenager_clo',
        'nbr_longueur_avant_clo',
        'slt_categorie_clo',
        'nbr_prix_metre_carre_clo',
        'nbr_coefficient_clo',
        'nbr_valeur_clo',
        'nbr_valeur_total_clotur',
    ]; 

    public function terrain() {
        return $this->belongsTo(Terrain::class, 'txt_nicad', 'txt_nicad');
    }

    public function dossier() {
        return $this->belongsTo(Dossier::class, 'txt_num_dossier', 'txt_num_dossier');
    }

}
