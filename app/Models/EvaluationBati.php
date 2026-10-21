<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationBati extends Model
{
    //
    protected $table = 'evaluations_batis';
    protected $fillable = [
        'currentCat', 
        'txt_num_dossier', 
        'txt_nicad', 
        'txt_valeur_terrain_bati', 
        'txt_dependant_domainePR',
        'slt_categoriePR',
        'nbr_prix_metre_carrePR',
        'nbr_surface_bati_solPR',
        'nbr_niveauPR',
        'nbr_surface_utilePR',
        'slt_coeffPR',
        'nbr_surface_corrigerPR',
        'nbr_valeurPR',  
        'slt_dependant_domaineTG', 
        'slt_categorieTG',
        'nbr_prix_metre_carreTG',
        'nbr_surface_bati_solTG',
        'nbr_niveauTG',
        'nbr_surface_utileTG',
        'slt_coeffTG',
        'nbr_surface_corrigerTG',
        'nbr_valeurTG',
    ];
  
    public function terrain() {
        return $this->belongsTo(Terrain::class, 'txt_nicad', 'txt_nicad');
    }

    public function dossier() {
        return $this->belongsTo(Dossier::class, 'txt_num_dossier', 'txt_num_dossier');
    }
}
