<?php

namespace App\Services;

use App\Models\Dossier;
use App\Models\Region;
use App\Models\Departement;
use App\Models\Arrondissement;
use App\Models\Commune;
use App\Models\Terrain;
use App\Models\ReferenceCadastrale;
use App\Models\Titulaire;
use Illuminate\Support\Facades\DB;

class DossierService
{
    /**
     * Créer un dossier et ses relations associées.
     *
     * @param array $data
     * @return Dossier
     * @throws \Exception
     */
    public function createDossier(array $data): Dossier
    {
        DB::beginTransaction(); // Gestion de la transaction pour garantir l'intégrité des données

        try {
            // Créer le dossier principal
            $dossier = Dossier::create([
                'txt_num_dossier' => $data['txt_num_dossier'],
                'txt_num_dordre' => $data['txt_num_dordre'],
                'slt_service_dendu' => $data['slt_service_dendu'],
                'txt_etat_cession' => $data['txt_etat_cession'],
                'txt_cession_definitive' => $data['txt_cession_definitive'],
                'dt_date_creation' => $data['dt_date_creation'],
            ]);

            // Créer les relations associées
            $region = Region::create([
                'slt_region' => $data['slt_region'],
            ]);

            $departement = Departement::create([
                'slt_departement' => $data['slt_departement'],
            ]);

            $arrondissement = Arrondissement::create([
                'slt_arrondissement' => $data['slt_arrondissement'],
            ]);

            $commune = Commune::create([
                'slt_commune' => $data['slt_commune'],
            ]);

            $terrain = Terrain::create([
                'txt_lotissement' => $data['txt_lotissement'],
                'txt_num_lotissement' => $data['txt_num_lotissement'],
                'txt_num_section' => $data['txt_num_section'],
                'txt_num_parcelle' => $data['txt_num_parcelle'],
                'txt_num_titre' => $data['txt_num_titre'],
                'nbr_surface' => $data['nbr_surface'],
                'slt_document_admin' => $data['slt_document_admin'],
                'txt_num_deliberation' => $data['txt_num_deliberation'],
                'dt_date_deliberation' => $data['dt_date_deliberation'],
                'txt_nicad' => $data['txt_nicad'],
            ]);

            $referenceCadastrale = ReferenceCadastrale::create([
                'rd_immatricalation_terrain' => $data['rd_immatricalation_terrain'],
                'slt_dependant_domaine' => $data['slt_dependant_domaine'],
                // Ajoutez les autres champs nécessaires ici
            ]);

            $titulaire = Titulaire::create([
                'slt_titulaire' => $data['slt_titulaire'],
                'txt_nationalite' => $data['txt_nationalite'],
                'slt_civilite' => $data['slt_civilite'],
                // Ajoutez les autres champs nécessaires ici
            ]);

            // Associer les relations au dossier principal, si nécessaire
            $dossier->region()->associate($region);
            $dossier->departement()->associate($departement);
            $dossier->arrondissement()->associate($arrondissement);
            $dossier->commune()->associate($commune);
            $dossier->terrain()->associate($terrain);
            $dossier->save();

            DB::commit(); // Valider les changements si tout est correct
            return $dossier;
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler les modifications en cas d'erreur
            throw $e; // Rejeter l'exception pour qu'elle soit gérée ailleurs
        }
    }
}
