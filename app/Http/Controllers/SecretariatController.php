<?php

namespace App\Http\Controllers;
 
use App\Models\Region;
use App\Models\Departement;
use App\Models\Arrondissement;
use App\Models\Commune;
use App\Models\Dossier;
use App\Models\EvaluationAmenagement;
use App\Models\EvaluationBati;
use App\Models\EvaluationCloture;
use App\Models\EvaluationCoursAmenagee;
use App\Models\Lotissement;
use App\Models\ReferenceCadastrale;
use App\Models\ReferenceUsage;
use App\Models\Terrain;
use App\Models\Titulaire;
use Illuminate\Http\Request;
use Inertia\Inertia; 
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Validation\ValidationException; 
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecretariatController extends Controller
{

    //
    public function index()
    {
        $terrains = Terrain::with(['region', 'departement', 'arrondissement', 'commune', 'dossier'])->get();
        
        return Inertia::render("secretariat/index",  [
            "terrains" => $terrains,
        ]);
    }

    public function create()
    {
        $regions = Region::all(); 
        $communes = Commune::select('id', 'slt_commune', 'txt_codeCommune')->get();

        return Inertia::render("secretariat/create",  [
            "regions" => $regions,
            "departements" => [], 
            'communes' => $communes,
  
        ]);
    }
 
    // edit terrain
    public function edit($id)
    {
        $terrain = Terrain::with([
            'region',
            'departement',
            'commune',
            'arrondissement',
            'dossier',
            'titulaire',
            'references_cadastrales',
            'references_usages',
            'evaluations_terrains',
            'evaluations_clotures', 
            'evaluations_amenagements', 
            'evaluations_batis', 
            'evaluations_cours_amenagees'
        ])->findOrFail($id);
 
        return Inertia::render('secretariat/edit', [
            'terrain' => $terrain,  
            'regions' => Region::select('id', 'slt_region')->get(),
            'departements' => Departement::select('id', 'slt_departement')->get(),
            'arrondissements' => Arrondissement::select('id', 'slt_arrondissement')->get(),
            'communes' => Commune::select('id', 'slt_commune', 'txt_codeCommune')->get(), 
            'lotissements' => Lotissement::select('id', 'txt_denomination')->get(),        ]);
    }
  

    public function verifyDossier(Request $request)
    {
        $request->validate([
            'slt_region' => 'nullable|exists:regions,id',
            'slt_departement' => 'nullable|exists:departements,id',
            'slt_arrondissement' => 'nullable|exists:arrondissements,id',
            'slt_commune' => 'nullable|exists:communes,id',
            'txt_num_lotissement' => 'required|string',
            'txt_lotissement' => 'required|string',
        ]);

        // 🔍 RECHERCHE DU TERRAIN PAR LOCALISATION
        $query = Terrain::with(['dossier', 'region', 'departement', 'arrondissement', 'commune']);

        if ($request->filled('slt_region')) {
            $query->where('slt_region', $request->slt_region);
        }
        if ($request->filled('slt_departement')) {
            $query->where('slt_departement', $request->slt_departement);
        }
        if ($request->filled('slt_arrondissement')) {
            $query->where('slt_arrondissement', $request->slt_arrondissement);
        }
        if ($request->filled('slt_commune')) {
            $query->where('slt_commune', $request->slt_commune);
        }

        $query->where('txt_num_lotissement', trim($request->txt_num_lotissement))
            ->where('txt_lotissement', trim($request->txt_lotissement));

        $terrain = $query->first();

        // ❌ TERRAIN NON TROUVÉ
        if (!$terrain) {
            return response()->json([
                'exists' => false,
                'terrain_exists' => false,
                'has_dossier' => false,
                'message' => '❌ Terrain introuvable. Veuillez enregistrer le terrain avant la mutation.',
                'action' => 'create_terrain'
            ], 404);
        }

        // ✅ TERRAIN TROUVÉ
        $response = [
            'exists' => true,
            'terrain_exists' => true,
            'terrain' => $terrain,
            'terrain_info' => [
                'id' => $terrain->id,
                'num_lotissement' => $terrain->txt_num_lotissement,
                'lotissement' => $terrain->txt_lotissement,
                'nicad' => $terrain->txt_nicad ?? 'Non renseigné',
                'num_parcelle' => $terrain->txt_num_parcelle ?? 'Non renseigné',
                'surface' => $terrain->nbr_surface ?? 'Non renseignée',
                'region' => $terrain->region ? $terrain->region->nom : null,
                'departement' => $terrain->departement ? $terrain->departement->nom : null,
                'arrondissement' => $terrain->arrondissement ? $terrain->arrondissement->nom : null,
                'commune' => $terrain->commune ? $terrain->commune->nom : null,
            ]
        ];

        // 📋 VÉRIFICATION DU DOSSIER
        if ($terrain->dossier) {
            $response['has_dossier'] = true;
            $response['dossier'] = $terrain->dossier;
            $response['dossier_info'] = [
                'id' => $terrain->dossier->id,
                'num_dossier' => $terrain->dossier->txt_num_dossier,
                'nicad' => $terrain->dossier->txt_nicad ?? 'Non renseigné',
                'proprietaire' => $terrain->dossier->txt_proprietaire ?? 'Non renseigné',
                'telephone' => $terrain->dossier->txt_telephone ?? 'Non renseigné',
                'email' => $terrain->dossier->txt_email ?? 'Non renseigné',
                'date_creation' => $terrain->dossier->created_at ? $terrain->dossier->created_at->format('d/m/Y H:i') : 'Non renseignée',
                'statut' => $terrain->dossier->statut ?? 'Actif',
            ];
            
            $response['message'] = "✅ Terrain existant avec dossier N° {$terrain->dossier->txt_num_dossier}";
            $response['action'] = 'dossier_exists';
            
        } else {
            // 📝 TERRAIN SANS DOSSIER
            $response['has_dossier'] = false;
            $response['message'] = "✅ Terrain existant sans dossier. Prêt à créer un nouveau dossier.";
            $response['action'] = 'create_dossier';
        }

        return response()->json($response, 200);
    }

    // Récupérer les références de lotissement
    public function fetchReferencesLotissement(): JsonResponse
    { 
        // Récupérer tous les lotissement non vide
        $Lotissements = Lotissement::whereNotNull('txt_denomination')
                        ->where('txt_denomination', '!=', '')
                        ->get();
         
        // Extraire les références de lotissement (sans doublons)
        $referencesLot = $Lotissements->pluck('txt_denomination')
                                        ->unique()
                                        ->values()
                                        ->all(); 
    
        return response()->json([
            'referencesLot' => $referencesLot, 
        ]);
    }

    public function dernierOrdre($annee)
    {
        $dernier = Dossier::whereYear('dt_date_creation', $annee)
            ->max('txt_num_dordre');

        return response()->json([
            'dernier' => $dernier ? (int) $dernier : 0
        ]);
    }

    // store
    public function store(Request $request)
    {
        // 🔥 Nettoyage NICAD
        $nicadNettoye = preg_replace('/\s+/', '', $request->txt_nicad ?? '');

        // ⚡ VÉRIFICATION DU NICAD DANS LA TABLE TERRAINS
        $terrainExistant = Terrain::whereRaw("
            REPLACE(REPLACE(REPLACE(txt_nicad,' ',''),'\n',''),'\r','') = ?
        ", [$nicadNettoye])->first();

        // 🚨 Si le NICAD existe déjà dans un terrain
        if ($terrainExistant) {
            // Vérifier si ce terrain a un dossier
            if ($terrainExistant->dossier) {
                // Le terrain a déjà un dossier → on bloque
                throw ValidationException::withMessages([
                    'txt_nicad' => 'Ce NICAD est déjà associé au terrain N° ' . 
                        ($terrainExistant->id ?? 'inconnu') . 
                        ' et au dossier N° ' . 
                        ($terrainExistant->dossier->txt_num_dossier ?? 'inconnu')
                ]);
            } else {
                // Le terrain existe mais sans dossier → on peut le réutiliser
                // On garde une référence pour mise à jour
                $terrainAReutiliser = $terrainExistant;
            }
        }

        // 🔥 Validation (inchangée)
        $validated = $request->validate([
            'txt_num_dossier' => 'required|string|unique:dossiers,txt_num_dossier',
            'slt_service_rendu' => 'nullable|string',
            'txt_etat_cession' => 'nullable|string',
            'txt_cession_definitive' => 'nullable|string',
            'dt_date_creation' => 'required|date',

            'rd_immatriculation_terrain' => 'required|string',
            'slt_dependant_domaine' => 'nullable|string',
            'ussu_bornage' => 'nullable|string',
            'slt_lf' => 'nullable|string',
            'txt_num_requisition' => 'nullable|string',
            'txt_surface_bornage' => 'nullable|string',
            'dt_date_bornage' => 'nullable|date',
            'txt_nom_geometre' => 'nullable|string',

            'slt_titulaire' => 'required|string',
            'txt_nationalite' => 'nullable|string',
            'slt_civilite' => 'nullable|string',
            'txt_prenom' => 'nullable|string',
            'txt_nom' => 'nullable|string',
            'slt_piece' => 'nullable|string',
            'txt_numPiece' => 'nullable|string',
            'dt_date_delivrance' => 'nullable|date|after:dt_date_naissance',
            'dt_date_naissance' => 'nullable|date',
            'txt_lieu_naissance' => 'nullable|string',
            'txt_adresse' => 'nullable|string',
            'tel_telephone' => 'nullable|string',
            'eml_email' => 'nullable|string',
            'txt_representant' => 'nullable|string',
            'tel_telRepresentant' => 'nullable|string',

            'slt_categoriePM' => 'nullable|string',
            'txt_formJuridiquePM' => 'nullable|string',
            'txt_denominationPM' => 'nullable|string',
            'txt_nineaPM' => 'nullable|string',
            'txt_perssonneRepresentantPM' => 'nullable|string',
            'txt_fonctionPM' => 'nullable|string',
            'txt_telephonePM' => 'nullable|string',
            'txt_emailPM' => 'nullable|string',
            'txt_adressePM' => 'nullable|string',

            'slt_etablissementPA' => 'nullable|string',
            'txt_personneResponsablePA' => 'nullable|string',
            'txt_fonctionResponsablePA' => 'nullable|string',
            'txt_telephonePA' => 'nullable|string',
            'txt_emailPA' => 'nullable|string',
            'txt_ministèreTutelePA' => 'nullable|string',

            'fichierPDF' => 'nullable|file|mimes:pdf|max:120400',

            'txt_lotissement' => 'nullable|string',
            'txt_HorsLotissement' => 'nullable|string',
            'txt_num_lotissement' => 'nullable|string',
            'txt_num_section' => 'nullable|string',
            'txt_num_parcelle' => 'nullable|string',
            'nbr_surface' => 'nullable|numeric',
            'txt_num_titre' => 'nullable|string',
            'txt_titre_mere' => 'nullable|string',
            'txt_appartement' => 'nullable|string',
            'slt_document_admin' => 'nullable|string',
            'txt_num_deliberation' => 'nullable|string',
            'dt_date_deliberation' => 'nullable|date',
            'txt_nicad' => 'nullable|string',

            'slt_region' => 'required|exists:regions,id',
            'slt_departement' => 'required|exists:departements,id',
            'slt_arrondissement' => 'required|exists:arrondissements,id',
            'slt_commune' => 'required|exists:communes,id',
        ],
            [
                'txt_num_dossier.required' => 'Le champ "Numéro de dossier" est obligatoire.',
                'slt_region.required' => 'Le champ "Nombre de parcelles" est obligatoire.',
                'slt_departement.required' => 'Le champ "Departement" est obligatoire.',
                'slt_arrondissement.required' => 'Le champ "Arrondissement" est obligatoire.',
                'slt_commune.required' => 'Le champ "Commune" est obligatoire.',
                'txt_surfaceLotissement.required' => 'Le champ "Surface" est obligatoire.',
                'txt_num_dossier.unique' => 'Le numéro de dossier est deja pris.',
                'slt_titulaire.required' => 'Le champ "Titulaire" est obligatoire.',
                'rd_immatriculation_terrain.required' => 'Le champ "Immatriculation terrain" est obligatoire.',
            ]);

        // 📁 fichier PDF
        if ($request->hasFile('fichierPDF')) {
            $validated['fichierPDF'] = $request->file('fichierPDF')
                ->store('numerisation', 'public');
        }
 
        // vérification du couple lotissement et numéro de lotissement pour un terrain existant 
        // Après validation, vérification du couple
        $lotissementNettoye = preg_replace('/\s+/', '', $request->txt_lotissement ?? '');
        $numLotissementNettoye = preg_replace('/\s+/', '', $request->txt_num_lotissement ?? '');

        $terrainExistantCouple = Terrain::where('txt_lotissement', $lotissementNettoye)
            ->where('txt_num_lotissement', $numLotissementNettoye)
            ->first();

        if ($terrainExistantCouple) {
            // Si le terrain a déjà un dossier, on bloque
            if ($terrainExistantCouple->dossier) {
                $dossier = $terrainExistantCouple->dossier;
                $titulaire = $terrainExistantCouple->titulaire;
                $message = 'Ce lotissement et ce numéro de lotissement existent déjà.';
                if ($dossier) {
                    $message .= ' Dossier N° ' . $dossier->txt_num_dossier . '.';
                }
                if ($titulaire) {
                    $message .= ' Propriétaire : ' . ($titulaire->txt_prenom ?? '') . ' ' . ($titulaire->txt_nom ?? '');
                }
                throw ValidationException::withMessages([
                    'txt_lotissement' => $message,
                    'txt_num_lotissement' => 'Ce numéro de lotissement est déjà utilisé pour ce lotissement.'
                ]);
            } else {
                // Terrain sans dossier, on peut le réutiliser
                $terrainAReutiliser = $terrainExistantCouple; // ou fusionner avec le précédent
            }
        }

        return DB::transaction(function () use ($validated) {

            // 🔢 numéro ordre optimisé
            $numeroDordre = (Dossier::max('txt_num_dordre') ?? 0) + 1;

            // 📁 DOSSIER
            $dossier = Dossier::create([
                'txt_num_dossier' => $validated['txt_num_dossier'],
                'txt_num_dordre' => $numeroDordre,
                'slt_service_rendu' => $validated['slt_service_rendu'] ?? null,
                'txt_etat_cession' => $validated['txt_etat_cession'] ?? null,
                'txt_cession_definitive' => $validated['txt_cession_definitive'] ?? null,
                'dt_date_creation' => $validated['dt_date_creation'],
            ]);

            // 📌 REFERENCE CADASTRALE
            $reference = ReferenceCadastrale::create([
                'rd_immatriculation_terrain' => $validated['rd_immatriculation_terrain'],
                'slt_dependant_domaine' => $validated['slt_dependant_domaine'] ?? null,
                'ussu_bornage' => $validated['ussu_bornage'] ?? null,
                'slt_lf' => $validated['slt_lf'] ?? null,
                'txt_num_requisition' => $validated['txt_num_requisition'] ?? null,
                'txt_surface_bornage' => $validated['txt_surface_bornage'] ?? null,
                'dt_date_bornage' => $validated['dt_date_bornage'] ?? null,
                'txt_nom_geometre' => $validated['txt_nom_geometre'] ?? null,
            ]);

            // 👤 TITULAIRE
            $titulaire = Titulaire::create([
                'slt_titulaire' => $validated['slt_titulaire'],
                'txt_nationalite' => $validated['txt_nationalite'] ?? null,
                'slt_civilite' => $validated['slt_civilite'] ?? null,
                'txt_prenom' => $validated['txt_prenom'] ?? null,
                'txt_nom' => $validated['txt_nom'] ?? null,
                'slt_piece' => $validated['slt_piece'] ?? null,
                'txt_numPiece' => $validated['txt_numPiece'] ?? null,
                'dt_date_delivrance' => $validated['dt_date_delivrance'] ?? null,
                'dt_date_naissance' => $validated['dt_date_naissance'] ?? null,
                'txt_lieu_naissance' => $validated['txt_lieu_naissance'] ?? null,
                'txt_adresse' => $validated['txt_adresse'] ?? null,
                'tel_telephone' => $validated['tel_telephone'] ?? null,
                'eml_email' => $validated['eml_email'] ?? null,
                'txt_representant' => $validated['txt_representant'] ?? null,
                'tel_telRepresentant' => $validated['tel_telRepresentant'] ?? null,

                'slt_categoriePM' => $validated['slt_categoriePM'] ?? null,
                'txt_formJuridiquePM' => $validated['txt_formJuridiquePM'] ?? null,
                'txt_denominationPM' => $validated['txt_denominationPM'] ?? null,
                'txt_nineaPM' => $validated['txt_nineaPM'] ?? null,
                'txt_perssonneRepresentantPM' => $validated['txt_perssonneRepresentantPM'] ?? null,
                'txt_fonctionPM' => $validated['txt_fonctionPM'] ?? null,
                'txt_telephonePM' => $validated['txt_telephonePM'] ?? null,
                'txt_emailPM' => $validated['txt_emailPM'] ?? null,
                'txt_adressePM' => $validated['txt_adressePM'] ?? null,

                'slt_etablissementPA' => $validated['slt_etablissementPA'] ?? null,
                'txt_personneResponsablePA' => $validated['txt_personneResponsablePA'] ?? null,
                'txt_fonctionResponsablePA' => $validated['txt_fonctionResponsablePA'] ?? null,
                'txt_telephonePA' => $validated['txt_telephonePA'] ?? null,
                'txt_emailPA' => $validated['txt_emailPA'] ?? null,
                'txt_ministèreTutelePA' => $validated['txt_ministèreTutelePA'] ?? null,

                'fichierPDF' => $validated['fichierPDF'] ?? null,
            ]);

            // 🏡 TERRAIN
            $terrain = Terrain::create([
                'txt_lotissement' => $validated['txt_lotissement'] ?? null,
                'txt_HorsLotissement' => $validated['txt_HorsLotissement'] ?? null,
                'txt_num_lotissement' => $validated['txt_num_lotissement'] ?? null,
                'txt_num_section' => $validated['txt_num_section'] ?? null,
                'txt_num_parcelle' => $validated['txt_num_parcelle'] ?? null,
                'nbr_surface' => $validated['nbr_surface'] ?? null,
                'txt_num_titre' => $validated['txt_num_titre'] ?? null,
                'txt_titre_mere' => $validated['txt_titre_mere'] ?? null,
                'txt_appartement' => $validated['txt_appartement'] ?? null,
                'slt_document_admin' => $validated['slt_document_admin'] ?? null,
                'txt_num_deliberation' => $validated['txt_num_deliberation'] ?? null,
                'dt_date_deliberation' => $validated['dt_date_deliberation'] ?? null,
                'txt_nicad' => $validated['txt_nicad'] ?? null,

                'slt_region' => $validated['slt_region'],
                'slt_departement' => $validated['slt_departement'],
                'slt_arrondissement' => $validated['slt_arrondissement'],
                'slt_commune' => $validated['slt_commune'],

                'txt_num_dossier' => $dossier->id,
                'reference_cadastrale_id' => $reference->id,
                'titulaire_id' => $titulaire->id,
            ]);

            return redirect()->route('secretariat.create')->with([
                'nbr_surface' => $terrain->nbr_surface,
                'txt_nicad' => $terrain->txt_nicad,
                'txt_num_dossier' => $dossier->txt_num_dossier,
            ]);
        });
    }


    // update Upload PDF
    public function uploadPDF(Request $request, Terrain $terrain)
    {
        $request->validate([
            'fichierPDF' => 'required|file|mimes:pdf|max:20480', // 10 Mo max
        ]);

        // Supprimer ancien fichier si existant
        if ($terrain->titulaire->fichierPDF) {
            Storage::disk('public')->delete($terrain->titulaire->fichierPDF);
        }

        $path = $request->file('fichierPDF')->store('numerisation', 'public');

        $terrain->titulaire->update(['fichierPDF' => $path]);

        return response()->json(['success' => true, 'path' => $path]);
    }
 
    // Mise à jour d'un terrain
    public function update(Request $request, $id)
    {
        // dd($request->all());
 
                
        $terrain = Terrain::findOrFail($id); 

        // ✅ 1️⃣ Vérifier le doublon en ignorant les accents et la casse
        $existingTerrain = Terrain::whereRaw('LOWER(txt_nicad) = LOWER(?)', [$request->txt_nicad])
            ->where('id', '!=', $id)
            ->with('dossier')
            ->first();

        if ($existingTerrain) {
            $message = "Le NICAD {$request->txt_nicad} est déjà attribué au dossier {$existingTerrain->dossier->txt_num_dossier}.";
            return back()->withErrors(['txt_nicad' => $message])->withInput();
        }

        // ✅ 2️⃣ Validation
        $request->validate([
            'txt_nicad' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('terrains', 'txt_nicad')->ignore($id, 'id'),
            ],
            // ... autres validations
        ]);
 
        // ✅ Mise à jour du terrain (champs directs)
        $terrain->update($request->only([
            'txt_nicad',
            'slt_region',
            'slt_departement',
            'slt_commune',
            'slt_arrondissement',
            'txt_lotissement',
            'txt_HorsLotissement',
            'txt_num_lotissement',
            'txt_num_section',
            'txt_num_parcelle',
            'txt_titre_mere',
            'txt_num_titre',
            'txt_appartement',
            'nbr_surface',
            'slt_document_admin',
            'txt_num_deliberation',
            'dt_date_deliberation',
        ]));

        // ✅ Mise à jour de la relation Dossier
        if ($terrain->dossier) {
            $terrain->dossier->update($request->only([
                'txt_num_dossier',
                'txt_num_dordre',
                'slt_service_rendu',
                'txt_etat_cession',
                'txt_cession_definitive',
                'dt_date_creation',
            ]));
        }

        // ✅ Mise à jour de la relation Références Cadastrales
        if ($terrain->references_cadastrales) {
            $terrain->references_cadastrales->update($request->only([
                'rd_immatriculation_terrain',
                'slt_dependant_domaine',
                'issu_bornage', 
                'slt_lf',
                'txt_num_requisition',
                'txt_surface_bornage',
                'dt_date_bornage',
                'txt_nom_geometre',
            ]));
        }

        // ✅ Mise à jour de la relation Titulaire
        if ($terrain->titulaire) {

            $titulaireData = $request->only([
                'slt_titulaire',
                'txt_nationalite',
                'slt_civilite',
                'txt_prenom',
                'txt_nom',
                'slt_piece',
                'txt_numPiece',
                'dt_date_delivrance',
                'dt_date_naissance',
                'txt_lieu_naissance',
                'txt_adresse',
                'tel_telephone',
                'txt_ninea',
                'eml_email', 
                'txt_representant',
                'tel_telRepresentant',
                    // Personne physique
                'slt_categoriePM',
                'txt_formJuridiquePM', 
                'txt_denominationPM', 
                'txt_nineaPM', 
                'txt_perssonneRepresentantPM', 
                'txt_fonctionPM', 
                'txt_telephonePM', 
                'txt_emailPM', 
                'txt_adressePM', 
                    // Personne morale
                'slt_etablissementPA',
                'txt_personneResponsablePA',
                'txt_fonctionResponsablePA',
                'txt_telephonePA',
                'txt_emailPA',
                'txt_ministèreTutelePA',

            ]);

            // 📂 Vérifie si un fichier est envoyé
            if ($request->hasFile('fichierPDF') && $request->file('fichierPDF')->isValid()) { 
                
                // Supprimer l'ancien fichier s'il existe
                if ($terrain->titulaire->fichierPDF) {
                    Storage::disk('public')->delete($terrain->titulaire->fichierPDF);
                }
                
                $titulaireData['fichierPDF'] = $request->file('fichierPDF')->store('numerisation', 'public'); 
            } else {
                // 📂 Si pas de fichier envoyé → conserver l’ancien fichier
                $titulaireData['fichierPDF'] = $terrain->titulaire->fichierPDF; 
            }
        
            // ✅ MAJ ici
            $terrain->titulaire->update($titulaireData);

        }
        
        // ✅ Mise à jour de la relation reference_usage
        if ($terrain->references_usages && $request->has('occupants')) {
            foreach ($request->input('occupants') as $occupantData) {
                ReferenceUsage::updateOrCreate(
                    [
                        'id' => $occupantData['id'] ?? null, // ou autre champ unique
                    ],
                    array_merge([
                        'txt_nicad' => $terrain->txt_nicad,
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier,
                        'slt_usage' => $request->input('slt_usage'),
                        'slt_residence' => $request->input('slt_residence'),
                        'nbr_montantLoyerTotal' => $request->input('nbr_montantLoyerTotal') ?? 0,
                        'nbr_TVATotal' => $request->input('nbr_TVATotal') ?? 0,
                    ], $occupantData)
                );
            } 
            
        }

        // ✅ Mise à jour de la relation evaluation_terrains
        if ($terrain->evaluations_terrains) {
            $terrain->evaluations_terrains->update($request->only([
                'nbr_surface',
                'txt_superficie_bati_sol',
                'txt_superficie_totale',
                'slt_secteur',
                'nbr_prix_metre_carre',
                'nbr_valeur_terrain',
                'nbr_valeurVenaleLimeuble',
                'nbr_valeurLocative',
                'dt_dateEvaluation',
            ]));
        }

        // ✅ Mise à jour de la relation evaluations_batis
        if ($terrain->evaluations_batis && $request->has('occupantsBP')) {
            foreach ($request->input('occupantsBP') as $occupantData) {
                EvaluationBati::updateOrCreate(
                    [
                        'txt_nicad' => $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'txt_dependant_domainePR' => $request->input('txt_dependant_domainePR'),
                        'slt_categoriePR' => $request->input('slt_categoriePR'),
                        'nbr_prix_metre_carrePR' => $request->input('nbr_prix_metre_carrePR'),
                        'nbr_surface_bati_solPR' => $request->input('nbr_surface_bati_solPR'),
                        'nbr_niveauPR' => $request->input('nbr_niveauPR'),
                        'nbr_surface_utilePR' => $request->input('nbr_surface_utilePR'),
                        'slt_coeffPR' => $request->input('slt_coeffPR'),
                        'nbr_surface_corrigerPR' => $request->input('nbr_surface_corrigerPR'),
                        'nbr_valeurPR' => $request->input('nbr_valeurPR'),
                        'currentCat' => $request->input('currentCat'),
                        'txt_valeur_terrain_bati' => $request->input('txt_valeur_terrain_bati') ?? 0,
                    ], $occupantData)
                );

            }
        }

        // ✅ Mise à jour de la relation evaluation_cours_amenagees
        if ($terrain->evaluations_cours_amenagees && $request->has('occupantsCA')) {
            foreach ($request->input('occupantsCA') as $occupantData) {
                EvaluationCoursAmenagee::updateOrCreate(
                    [
                        'txt_nicad' => $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'nbr_valeur_total_ca' => $request->input('nbr_valeur_total_ca') ?? 0,
                    ], $occupantData)
                );

            }
        }

        // ✅ Mise à jour de la relation evaluation_clotures
        if ($terrain->evaluations_clotures && $request->has('occupantsCL')) {
            foreach ($request->input('occupantsCL') as $occupantData) {
                EvaluationCloture::updateOrCreate(
                    [
                        'txt_nicad' => $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'nbr_valeur_total_clotur' => $request->input('nbr_valeur_total_clotur') ?? 0,
                    ], $occupantData)
                );

            }
        }
        $txtNicad = $terrain->txt_nicad;
        // ✅ Mise à jour de la relation evaluation_amenagements
        if ($terrain->evaluations_amenagements && $request->has('occupantsAP')) {
            foreach ($request->input('occupantsAP') as $occupantData) {
                EvaluationAmenagement::updateOrCreate(
                    [
                        'txt_nicad' => $txtNicad ?? $terrain->txt_nicad,
                    ],
                    array_merge([
                        'txt_num_dossier' => $terrain->dossier?->txt_num_dossier ?? $terrain->txt_num_dossier,
                        'nbr_valeur_totale_ap' => $request->input('nbr_valeur_totale_ap') ?? 0,
                    ], $occupantData)
                ); 

            }
        }


        return redirect()->route('donnee.create')
            ->with('success', 'Terrain '. $terrain->txt_nicad. ' mis à jour avec succès');     
 
       
    }

}