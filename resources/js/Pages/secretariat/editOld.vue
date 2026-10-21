<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import MazBtn from "maz-ui/components/MazBtn";
import { onMounted, ref, computed, watch, watchEffect } from 'vue';
import axios from "axios";  
import { toast } from "@/Components/AppToast";  
import AppCodeAccee from '@/Components/AppCodeAccee.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import MazRadio from 'maz-ui/components/MazRadio';
import DefaultLayout from "@/Layouts/DefaultLayout.vue";  
 
defineOptions({ layout: DefaultLayout }); 

// ✅ Props
const props = defineProps({
    terrain: {
        type: Object,
        default: () => ({})
    },
    regions: {
        type: Array,
        default: () => []
    },
    departements: {
        type: Array,
        default: () => []
    },
    arrondissements: {
        type: Array,
        default: () => []
    },
    communes: {
        type: Array,
        default: () => []
    },
    lotissements: {
        type: Array,
        default: () => []
    }
});

// ✅ États
const formVisible = ref(false);
const showModal = ref(true);     
const isLoading = ref(false);  
const maxOccupants = 25;
const maxOccupantsBP = 4;
const maxOccupantsCA = 3;
const maxOccupantsCL = 3;
const maxOccupantsAP = 3;  
const showSectionPP = ref(false);
const showSectionPM = ref(false);
const showSectionPA = ref(false);
const errorMessage = ref("");
const errorMessageNumSection = ref("");
const errorMessageNumParcelle = ref(""); 
const activeTab = ref('terrain'); 
const fichierPDF = ref(null);
const fichierPDFExistant = ref(null);
const isNewFileSelected = ref(false);

const setActiveTab = (tab) => {
    activeTab.value = tab;
};
 

// ✅ Catégories
const categories = {
    'Maison individuelle': {
        '1': 205891, '2': 190883, '3': 180937, '4': 158411,
        '5': 119942, '6': 88201, '7': 58801, '8': 40663,
        '9': 11117, '10': 10000, '11': 8000,
    },
    'Immeuble collectif': {
        'A': 244257, 'B': 226543, 'C': 204069, 'D': 177171,
        'E': 142293, 'F': 123552, 'G': 93878, 'H': 61602,
        'I': 53793, 'J': 25000, 'K': 22000, 'L': 20000, 'M': 8000,
    }
};

// ✅ Fonctions utilitaires
function arrondirDeuxDecimales(valeur) {
    const num = parseFloat(valeur);
    if (isNaN(num)) return 0;
    return Math.round((num + Number.EPSILON) * 100) / 100;
}

function formatOccupants(references_usages) {
    if (Array.isArray(references_usages)) {
        return references_usages.map(o => formatOccupant(o));
    }
    if (references_usages) {
        return [formatOccupant(references_usages)];
    }
    return [formatOccupant()];
}

function formatOccupant(o = {}) {
    return {
        txt_nomOccupantTG: o.txt_nomOccupantTG || '',
        txt_numAppartementTG: o.txt_numAppartementTG || '',
        txt_activiteTG: o.txt_activiteTG || '',
        txt_nineaTG: o.txt_nineaTG || '',
        tel_telephoneTG: o.tel_telephoneTG || '',
        nbr_montantLoyerTG: o.nbr_montantLoyerTG || 0,
        txt_dateLieuNaissanceTG: o.txt_dateLieuNaissanceTG || '',
        txt_cniPasseportTG: o.txt_cniPasseportTG || '',
        dt_dateDelivranceTG: o.dt_dateDelivranceTG || ''
    };
}

function formatOccupantsBP(evaluations_batis) {
    if (Array.isArray(evaluations_batis)) {
        return evaluations_batis.map(o => formatOccupantBP(o));
    }
    if (evaluations_batis) {
        return [formatOccupantBP(evaluations_batis)];
    }
    return [formatOccupantBP()];
}

function formatOccupantBP(o = {}) { 
    return { 
        slt_dependant_domaineTG: o.slt_dependant_domaineTG || '',
        slt_categorieTG: o.slt_categorieTG || '',
        nbr_prix_metre_carreTG: o.nbr_prix_metre_carreTG || '',
        nbr_surface_bati_solTG: o.nbr_surface_bati_solTG || '',
        nbr_niveauTG: o.nbr_niveauTG || '',
        nbr_surface_utileTG: o.nbr_surface_utileTG || '',
        slt_coeffTG: o.slt_coeffTG || '',
        nbr_surface_corrigerTG: o.nbr_surface_corrigerTG || '',
        nbr_valeurTG: o.nbr_valeurTG || '', 
    };
}

function formatOccupantsCA(evaluations_cours_amenagees) {
    if (Array.isArray(evaluations_cours_amenagees)) {
        return evaluations_cours_amenagees.map(o => formatOccupantCA(o));
    }
    if (evaluations_cours_amenagees) {
        return [formatOccupantCA(evaluations_cours_amenagees)];
    }
    return [formatOccupantCA()];
}

function formatOccupantCA(o = {}) {  
    return {    
        slt_cours_amenager_totale: o.slt_cours_amenager_totale || '',
        nbr_surface_ca_total: o.nbr_surface_ca_total || '',
        slt_categorie_ca_total: o.slt_categorie_ca_total || '',
        nbr_prix_metre_carre_ca_total: o.nbr_prix_metre_carre_ca_total || '',
        nbr_coefficient_ca_total: o.nbr_coefficient_ca_total || '',
        nbr_valeur_ca_total: o.nbr_valeur_ca_total || '',
    };
}

function formatOccupantsCL(evaluations_clotures) {
    if (Array.isArray(evaluations_clotures)) {
        return evaluations_clotures.map(o => formatOccupantCL(o));
    }
    if (evaluations_clotures) {
        return [formatOccupantCL(evaluations_clotures)];
    }
    return [formatOccupantCL()];
}

function formatOccupantCL(o = {}) {  
    return {    
        slt_cours_amenager_clo: o.slt_cours_amenager_clo || '',
        nbr_longueur_avant_clo: o.nbr_longueur_avant_clo || '',
        slt_categorie_clo: o.slt_categorie_clo || '',
        nbr_prix_metre_carre_clo: o.nbr_prix_metre_carre_clo || '',
        nbr_coefficient_clo: o.nbr_coefficient_clo || '',
        nbr_valeur_clo: o.nbr_valeur_clo || '', 
    };
}

function formatOccupantsAP(evaluations_amenagements) {
    if (Array.isArray(evaluations_amenagements)) {
        return evaluations_amenagements.map(o => formatOccupantAP(o));
    }
    if (evaluations_amenagements) {
        return [formatOccupantAP(evaluations_amenagements)];
    }
    return [formatOccupantAP()];
}

function formatOccupantAP(o = {}) {  
    return {     
        txt_designation_am: o.txt_designation_am || '',
        nbr_valeur_unitaire_am: o.nbr_valeur_unitaire_am || '',
        nbr_quantile_am: o.nbr_quantile_am || '',
        slt_coeficien_am: o.slt_coeficien_am || '',
        nbr_valeur_am: o.nbr_valeur_am || '',
    };
}

// ✅ Fonction pour formater la date
const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    } catch {
        return '';
    }
};

 // Fonction pour formater la date pour input
const formatDateForInput = (dateString) => {
    if (!dateString) return '';

    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    } catch {
        return '';
    }
};

const terrain = props.terrain ?? {};

// Formulaire
const form = useForm({
    // dossier
    txt_num_dossier: terrain.dossier?.txt_num_dossier ?? '',
    slt_service_rendu: terrain.dossier?.slt_service_rendu ?? '',
    txt_etat_cession: terrain.dossier?.txt_etat_cession ?? '',
    txt_cession_definitive: terrain.dossier?.txt_cession_definitive ?? '',
    dt_date_creation: formatDateForInput(terrain.dossier?.dt_date_creation),

    slt_region: terrain.region?.id ?? '',
    slt_departement: terrain.departement?.id ?? '',
    slt_arrondissement: terrain.arrondissement?.id ?? '',
    slt_commune: terrain.commune?.id ?? '',

    // terrain
    txt_lotissement: terrain.terrains?.txt_lotissement ?? '',
    txt_HorsLotissement: terrain.terrains?.txt_HorsLotissement ?? '',
    txt_num_lotissement: terrain.terrains?.txt_num_lotissement ?? '',
    txt_num_section: terrain.terrains?.txt_num_section ?? '',
    txt_num_parcelle: terrain.terrains?.txt_num_parcelle ?? '',
    nbr_surface: terrain.terrains?.nbr_surface ?? '',
    txt_num_titre: terrain.terrains?.txt_num_titre ?? '',
    txt_titre_mere: terrain.terrains?.txt_titre_mere ?? '',
    txt_appartement: terrain.terrains?.txt_appartement ?? '',
    slt_document_admin: terrain.terrains?.slt_document_admin ?? '',
    txt_num_deliberation: terrain.terrains?.txt_num_deliberation ?? '',
    dt_date_deliberation: formatDateForInput(terrain.terrains?.dt_date_deliberation),

    // Reference cadastrale
    rd_immatriculation_terrain: terrain.references_cadastrales?.rd_immatriculation_terrain ?? '',
    slt_dependant_domaine: terrain.references_cadastrales?.slt_dependant_domaine ?? '',
    ussu_bornage: terrain.references_cadastrales?.ussu_bornage ?? '',
    slt_lf: terrain.references_cadastrales?.slt_lf ?? '',
    txt_num_requisition: terrain.references_cadastrales?.txt_num_requisition ?? '',
    txt_surface_bornage: terrain.references_cadastrales?.txt_surface_bornage ?? '',
    dt_date_bornage: formatDateForInput(terrain.references_cadastrales?.dt_date_bornage),
    txt_nom_geometre: terrain.references_cadastrales?.txt_nom_geometre ?? '',

    // titulaire
    slt_titulaire: terrain.titulaires?.slt_titulaire ?? '',
    txt_nationalite: terrain.titulaires?.txt_nationalite ?? '',
    slt_civilite: terrain.titulaires?.slt_civilite ?? '',
    txt_prenom: terrain.titulaires?.txt_prenom ?? '',
    txt_nom: terrain.titulaires?.txt_nom ?? '',
    slt_piece: terrain.titulaires?.slt_piece ?? '',
    txt_numPiece: terrain.titulaires?.txt_numPiece ?? '',
    dt_date_delivrance: formatDateForInput(terrain.titulaires?.dt_date_delivrance),
    dt_date_naissance: formatDateForInput(terrain.titulaires?.dt_date_naissance),
    txt_lieu_naissance: terrain.titulaires?.txt_lieu_naissance ?? '',
    txt_adresse: terrain.titulaires?.txt_adresse ?? '',
    tel_telephone: terrain.titulaires?.tel_telephone ?? '',
    eml_email: terrain.titulaires?.eml_email ?? '',
    txt_representant: terrain.titulaires?.txt_representant ?? '',
    tel_telRepresentant: terrain.titulaires?.tel_telRepresentant ?? '',
    slt_categoriePM: terrain.titulaires?.slt_categoriePM ?? '',
    txt_formJuridiquePM: terrain.titulaires?.txt_formJuridiquePM ?? '',
    txt_denominationPM: terrain.titulaires?.txt_denominationPM ?? '',
    txt_nineaPM: terrain.titulaires?.txt_nineaPM ?? '',
    txt_perssonneRepresentantPM: terrain.titulaires?.txt_perssonneRepresentantPM ?? '',
    txt_fonctionPM: terrain.titulaires?.txt_fonctionPM ?? '',
    txt_telephonePM: terrain.titulaires?.txt_telephonePM ?? '',
    txt_emailPM: terrain.titulaires?.txt_emailPM ?? '',
    txt_adressePM: terrain.titulaires?.txt_adressePM ?? '',
    slt_etablissementPA: terrain.titulaires?.slt_etablissementPA ?? '',
    txt_personneResponsablePA: terrain.titulaires?.txt_personneResponsablePA ?? '',
    txt_fonctionResponsablePA: terrain.titulaires?.txt_fonctionResponsablePA ?? '',
    txt_telephonePA: terrain.titulaires?.txt_telephonePA ?? '',
    txt_emailPA: terrain.titulaires?.txt_emailPA ?? '',
    txt_ministèreTutelePA: terrain.titulaires?.txt_ministèreTutelePA ?? '',
    fichierPDF: terrain.titulaires?.fichierPDF ?? '',

    slt_usage: terrain.references_usages?.[0]?.slt_usage ?? '',
    slt_residence: terrain.references_usages?.[0]?.slt_residence ?? '',
    nbr_montantLoyerTotal: terrain.references_usages?.[0]?.nbr_montantLoyerTotal ?? '',
    nbr_TVATotal: terrain.references_usages?.[0]?.nbr_TVATotal ?? '',

    occupants: terrain.references_usages?.length
        ? terrain.references_usages.map(o => ({ ...o }))
        : [{
            txt_nomOccupantTG: '',
            txt_numAppartementTG: '',
            txt_activiteTG: '',
            txt_nineaTG: '',
            tel_telephoneTG: '',
            nbr_montantLoyerTG: 0,
            txt_dateLieuNaissanceTG: '',
            txt_cniPasseportTG: '',
            dt_dateDelivranceTG: ''
        }],

    txt_superficie_totale: terrain.evaluations_terrains?.txt_superficie_totale ?? '',
    txt_superficie_bati_sol: terrain.evaluations_terrains?.txt_superficie_bati_sol ?? '',
    slt_secteur: terrain.evaluations_terrains?.slt_secteur ?? '',
    nbr_prix_metre_carre: terrain.evaluations_terrains?.nbr_prix_metre_carre ?? '',
    nbr_valeur_terrain: terrain.evaluations_terrains?.nbr_valeur_terrain ?? '',
    nbr_valeurVenaleLimeuble: terrain.evaluations_terrains?.nbr_valeurVenaleLimeuble ?? '',
    nbr_valeurLocative: terrain.evaluations_terrains?.nbr_valeurLocative ?? '',
    dt_dateEvaluation: formatDateForInput(terrain.evaluations_terrains?.dt_dateEvaluation),

    txt_dependant_domainePR: terrain.evaluations_batis?.txt_dependant_domainePR ?? '',
    slt_categoriePR: terrain.evaluations_batis?.slt_categoriePR ?? '',
    nbr_prix_metre_carrePR: terrain.evaluations_batis?.nbr_prix_metre_carrePR ?? '',
    nbr_surface_bati_solPR: terrain.evaluations_batis?.nbr_surface_bati_solPR ?? '',
    nbr_niveauPR: terrain.evaluations_batis?.nbr_niveauPR ?? '',
    nbr_surface_utilePR: terrain.evaluations_batis?.nbr_surface_utilePR ?? '',
    slt_coeffPR: terrain.evaluations_batis?.slt_coeffPR ?? '',
    nbr_surface_corrigerPR: terrain.evaluations_batis?.nbr_surface_corrigerPR ?? '',
    nbr_valeurPR: terrain.evaluations_batis?.nbr_valeurPR ?? '',
    currentCat: terrain.evaluations_batis?.currentCat ?? '',
    txt_valeur_terrain_bati: terrain.evaluations_batis?.txt_valeur_terrain_bati ?? '',

    occupantsBP: terrain.evaluations_batis?.length
        ? terrain.evaluations_batis.map(o => ({ ...o }))
        : [{
            slt_dependant_domaineTG: '',
            slt_categorieTG: '',
            nbr_prix_metre_carreTG: '',
            nbr_surface_bati_solTG: '',
            nbr_niveauTG: '',
            nbr_surface_utileTG: '',
            slt_coeffTG: '',
            nbr_surface_corrigerTG: '',
            nbr_valeurTG: '',
        }],

    occupantsCA: terrain.evaluations_cours_amenagees?.length
        ? terrain.evaluations_cours_amenagees.map(o => ({ ...o }))
        : [{
            slt_cours_amenager_totale: '',
            nbr_surface_ca_total: '',
            slt_categorie_ca_total: '',
            nbr_prix_metre_carre_ca_total: '',
            nbr_coefficient_ca_total: '',
            nbr_valeur_ca_total: '',
        }],

    nbr_valeur_total_ca: terrain.evaluations_cours_amenagees?.nbr_valeur_total_ca ?? '',

    occupantsCL: terrain.evaluations_clotures?.length
        ? terrain.evaluations_clotures.map(o => ({ ...o }))
        : [{
            slt_cours_amenager_clo: '',
            nbr_longueur_avant_clo: '',
            slt_categorie_clo: '',
            nbr_prix_metre_carre_clo: '',
            nbr_coefficient_clo: '',
            nbr_valeur_clo: '',
        }],

    nbr_valeur_total_clotur: terrain.evaluations_clotures?.nbr_valeur_total_clotur ?? '',

    occupantsAP: terrain.evaluations_amenagements?.length
        ? terrain.evaluations_amenagements.map(o => ({ ...o }))
        : [{
            txt_designation_am: '',
            nbr_valeur_unitaire_am: '',
            nbr_quantile_am: '',
            slt_coeficien_am: '',
            nbr_valeur_am: '',
        }],

    nbr_valeur_totale_ap: terrain.evaluations_amenagements?.nbr_valeur_totale_ap ?? '',
});

// ✅ UN SEUL WATCHER pour props.terrain
watch(
    () => props.terrain,
    (newTerrain) => {
        if (newTerrain && newTerrain.id) {
            form.txt_num_dossier = newTerrain.dossier?.txt_num_dossier || '';
            form.slt_region = newTerrain.region?.id || '';
            form.slt_departement = newTerrain.departement?.id || '';
            form.slt_arrondissement = newTerrain.arrondissement?.id || '';
            form.slt_commune = newTerrain.commune?.id || '';
            form.dt_date_creation = formatDate(newTerrain.dossier?.dt_date_creation);
            form.txt_nicad = newTerrain.txt_nicad || '';
            form.txt_num_section = newTerrain.txt_num_section || '';
            form.txt_num_parcelle = newTerrain.txt_num_parcelle || '';
            form.txt_appartement = newTerrain.references_cadastrales?.txt_appartement || '';
            form.rd_immatriculation_terrain = newTerrain.references_cadastrales?.rd_immatriculation_terrain || '';
            form.slt_titulaire = newTerrain.titulaire?.slt_titulaire || '';
        }
    },
    { deep: true, immediate: true }
);

// ✅ Watcher pour les erreurs de validation
watch(
    () => form.errors,
    (errors) => {
        if (errors && Object.keys(errors).length > 0) {
            Object.values(errors).forEach(message => {
                if (message && typeof message === 'string' && message.trim()) {
                    toast.error(message);
                }
            });
        }
    },
    { deep: true, immediate: true }
);
 
// ✅ NICAD - Calcul automatique
const txt_nicad_calcule = computed(() => { 
    const codes = {
        1: "13110100",
        2: "13120101",
        3: "13120102",
        4: "13120103",
        5: "13120104",
        6: "13120202",
        7: "13120201",
        8: "13210100",
        9: "13220101",
        10: "13220102",
        11: "13220201",
        12: "13220202",
        13: "13220103",
        14: "13310100",
        15: "13320102",
        16: "13320101",
        17: "13320203",
        18: "13320202",
        19: "13320201"
    };
    
    const prefix = codes[form.slt_commune] || '';
    const section = form.txt_num_section?.toString().trim() || '';
    const parcelle = form.txt_num_parcelle?.toString().trim() || '';
    const appartement = form.txt_appartement?.toString().trim() || '';

    // ✅ Nettoyer le résultat
    const value = `${prefix}${section}${parcelle}${appartement}`.replace(/\s/g, '').replace(/\r?\n/g, '');

    if (value.length <= 8) return '';

    return value;
});

// ✅ Watcher corrigé
watch(
    txt_nicad_calcule,
    (newValue) => {
        if (newValue && newValue.length > 8) {
            form.txt_nicad = newValue;
        }
    },
    { immediate: true }
);

// ✅ Watcher pour les champs qui influencent le NICAD
watch(
    [() => form.slt_commune, () => form.txt_num_section, () => form.txt_num_parcelle, () => form.txt_appartement],
    () => {
        const newNicad = txt_nicad_calcule.value;
        if (!form.txt_nicad && newNicad) {
            form.txt_nicad = newNicad;
        }
    },
    { immediate: true }
);

// ✅ Validations
const validateNumericField = (field, maxLength, errorRef) => {
    let value = (form[field] ?? "").toString().replace(/\D/g, "");

    if (value.length > maxLength) {
        value = value.slice(0, maxLength);
    }

    form[field] = value;

    if (value.length < maxLength) {
        errorRef.value = `❌ ${maxLength} chiffres MAX.`;
    } else {
        errorRef.value = "";
    }
};

const validateInputApartement = () => {
    validateNumericField("txt_appartement", 3, errorMessage);
};

const validateInputNumSection = () => {
    validateNumericField("txt_num_section", 3, errorMessageNumSection);
}; 

const validateInputNumParcelle = () => {
    validateNumericField("txt_num_parcelle", 5, errorMessageNumParcelle);
};
 
// ✅ Computed pour l'affichage
const showImmatricule = computed(() => {
    return (form.rd_immatriculation_terrain || '').trim().toLowerCase() === 'terrain immatriculé';
});

const showNonImmatricule = computed(() => {
    return (form.rd_immatriculation_terrain || 'Terrain Non Immatriculé').trim().toLowerCase() === 'terrain non immatriculé';
});

// ✅ Ussu Bornage
const showMorcellement = ref(false);
const handleSelectChange = () => {
    showMorcellement.value = form.ussu_bornage === "Morcellement de Copropriété";
};

watch(() => form.ussu_bornage, () => {
    handleSelectChange();
});

// ✅ Titulaire
let hasShownError = false;
const handleSelectChangeTitulaire = () => {
    showSectionPP.value = false;
    showSectionPM.value = false;
    showSectionPA.value = false;

    switch (form.slt_titulaire) {
        case "Personne Physique":
            showSectionPP.value = true;
            hasShownError = false;
            break;
        case "Personne Morale":
            showSectionPM.value = true;
            hasShownError = false;
            break;
        case "Etat":
            showSectionPA.value = true;
            hasShownError = false;
            break;
        default:
            if (!hasShownError){
                hasShownError = true;
                toast.error("Veuillez choisir un titulaire");
            }  
    }
};
    
watch(() => form.slt_titulaire, () => { 
    handleSelectChangeTitulaire();  
});

// ✅ Occupants - Ajouter/Retirer
function addBlock() {
    if (form.occupants.length < maxOccupants) {
        form.occupants.push({
            id: Date.now() + Math.random(),
            txt_nomOccupantTG: '',
            txt_numAppartementTG: '',
            txt_activiteTG: '',
            txt_nineaTG: '',
            tel_telephoneTG: '',
            nbr_montantLoyerTG: 0,
            txt_dateLieuNaissanceTG: '',
            txt_cniPasseportTG: '',
            dt_dateDelivranceTG: ''
        });
    }
}

function removeBlock() {
    if (form.occupants.length > 1) {
        form.occupants.pop();
    }
}

function addBlockBP() {
    if (form.occupantsBP.length < maxOccupantsBP) {
        form.occupantsBP.push({
            id: Date.now() + Math.random(), 
            slt_dependant_domaineTG: '',
            slt_categorieTG: '',
            nbr_prix_metre_carreTG: '',
            nbr_surface_bati_solTG: '',
            nbr_niveauTG: '',
            nbr_surface_utileTG: '',
            slt_coeffTG: '',
            nbr_surface_corrigerTG: '',
            nbr_valeurTG: '', 
        });                
    } else {
        toast.error('Impossible d\'ajouter plus de 5 occupants.');
    }
}

function removeBlockBP() {
    if (form.occupantsBP.length > 1) {
        form.occupantsBP.pop();
    }
}

function addBlockCA() {
    if (form.occupantsCA.length < maxOccupantsCA) {
        form.occupantsCA.push({
            id: Date.now() + Math.random(),
            slt_cours_amenager_totale:'',
            nbr_surface_ca_total:'', 
            slt_categorie_ca_total:'', 
            nbr_prix_metre_carre_ca_total:'', 
            nbr_coefficient_ca_total:'',
            nbr_valeur_ca_total:'',
        });                
    } else {
        toast.error('Impossible d\'ajouter plus de 3 occupants.');
    }
}

function removeBlockCA() {
    if (form.occupantsCA.length > 1) {
        form.occupantsCA.pop();
    }
}

function addBlockCL() {
    if (form.occupantsCL.length < maxOccupantsCL) {
        form.occupantsCL.push({
            id: Date.now() + Math.random(),
            slt_cours_amenager_clo:'',  
            nbr_longueur_avant_clo:'', 
            slt_categorie_clo:'', 
            nbr_prix_metre_carre_clo:'', 
            nbr_coefficient_clo:'', 
            nbr_valeur_clo:'',
        });                
    } else {
        toast.error('Impossible d\'ajouter plus de 3 occupants.');
    }
}

function removeBlockCL() {
    if (form.occupantsCL.length > 1) {
        form.occupantsCL.pop();
    }
}

function addBlockAP() {
    if (form.occupantsAP.length < maxOccupantsAP) {
        form.occupantsAP.push({
            id: Date.now() + Math.random(),
            txt_designation_am:'', 
            nbr_valeur_unitaire_am:'', 
            nbr_quantile_am:'', 
            slt_coeficien_am:'', 
            nbr_valeur_am:'',
        });                
    } else {
        toast.error('Impossible d\'ajouter plus de 3 occupants.');
    }
}

function removeBlockAP() {
    if (form.occupantsAP.length > 1) {
        form.occupantsAP.pop();
    }
}

// ✅ Code d'accès
const codeDaccees = async (code) => { 
    if (!code) {
        toast.error("Veuillez entrer un code d'accès.");
        return;
    }

    isLoading.value = true;

    try {
        const { data } = await axios.post(route('code.verify'), { code });

        if (data.exists) {
            showModal.value = false;
            formVisible.value = true;
            toast.success("Modification autorisée !");
        } else {
            toast.error("Vérifie ton code d'accès !");
            formVisible.value = false;
        }
    } catch (err) {
        formVisible.value = false;

        if (err.response?.status === 422 && err.response.data.errors) {
            Object.values(err.response.data.errors).forEach(msg => toast.error(msg));
        } else {
            toast.error("Une erreur est survenue lors de la vérification.");
        }
    } finally {
        isLoading.value = false;
    }
};
 
//  


// ✅ Initialisation lors du montage
onMounted(() => {
    toast.info("🔔 Page de modification chargée");
    handleSelectChange();
    handleSelectChangeTitulaire();
    
    // ✅ Vérifier les valeurs chargées
    console.log('🔍 Valeurs chargées au montage:', {
        txt_num_dossier: form.txt_num_dossier,
        slt_region: form.slt_region,
        slt_departement: form.slt_departement,
        slt_arrondissement: form.slt_arrondissement,
        slt_commune: form.slt_commune,
        dt_date_creation: form.dt_date_creation,
        txt_nicad: form.txt_nicad,
        rd_immatriculation_terrain: form.rd_immatriculation_terrain,
        slt_titulaire: form.slt_titulaire,
    });
    
    // ✅ Si des champs sont vides, afficher un avertissement
    const emptyFields = [];
    if (!form.txt_num_dossier) emptyFields.push('Numéro de dossier');
    if (!form.slt_region) emptyFields.push('Région');
    if (!form.slt_departement) emptyFields.push('Département');
    if (!form.slt_arrondissement) emptyFields.push('Arrondissement');
    if (!form.slt_commune) emptyFields.push('Commune');
    if (!form.dt_date_creation) emptyFields.push('Date de création');
    if (!form.txt_nicad) emptyFields.push('NICAD');
    if (!form.rd_immatriculation_terrain) emptyFields.push('Immatriculation');
    if (!form.slt_titulaire) emptyFields.push('Titulaire');
    
    if (emptyFields.length > 0) {
        console.warn('⚠️ Champs obligatoires vides:', emptyFields);
        toast.warning(`Veuillez remplir: ${emptyFields.join(', ')}`);
    }
});

// ✅ Watcher pour props.terrain
watch(
    () => props.terrain,
    (newTerrain) => {
        if (newTerrain && newTerrain.id) {
            // ✅ Mettre à jour tous les champs avec des valeurs par défaut
            form.txt_num_dossier = newTerrain.dossier?.txt_num_dossier || '';
            form.slt_region = newTerrain.region?.id || '';
            form.slt_departement = newTerrain.departement?.id || '';
            form.slt_arrondissement = newTerrain.arrondissement?.id || '';
            form.slt_commune = newTerrain.commune?.id || '';
            form.dt_date_creation = formatDate(newTerrain.dossier?.dt_date_creation);
            form.txt_nicad = newTerrain.txt_nicad || '';
            form.txt_num_section = newTerrain.txt_num_section || '';
            form.txt_num_parcelle = newTerrain.txt_num_parcelle || '';
            form.txt_appartement = newTerrain.references_cadastrales?.txt_appartement || '';
            form.rd_immatriculation_terrain = newTerrain.references_cadastrales?.rd_immatriculation_terrain || '';
            form.slt_titulaire = newTerrain.titulaire?.slt_titulaire || '';
            
            // ✅ Ajouter les autres champs
            form.txt_lotissement = newTerrain.txt_lotissement || '';  // ✅ 
            form.txt_HorsLotissement = newTerrain.txt_HorsLotissement || '';
            form.txt_num_lotissement = newTerrain.txt_num_lotissement || '';
            form.nbr_surface = newTerrain.nbr_surface || '';
            form.slt_document_admin = newTerrain.slt_document_admin || '';
            form.txt_num_deliberation = newTerrain.txt_num_deliberation || '';
            form.dt_date_deliberation = formatDate(newTerrain.dt_date_deliberation);
            
            // ✅ Log pour débogage
            console.log('📥 Données chargées:', {
                txt_num_dossier: form.txt_num_dossier,
                slt_region: form.slt_region,
                slt_departement: form.slt_departement,
                slt_arrondissement: form.slt_arrondissement,
                slt_commune: form.slt_commune,
                dt_date_creation: form.dt_date_creation,
                txt_nicad: form.txt_nicad,
                rd_immatriculation_terrain: form.rd_immatriculation_terrain,
                slt_titulaire: form.slt_titulaire,
            });
        } else {
            console.warn('⚠️ Aucun terrain chargé');
        }
    },
    { deep: true, immediate: true }
);
 

// ✅ Charger le PDF existant
onMounted(() => {
    if (props.terrain?.titulaire?.fichierPDF) {
        fichierPDFExistant.value = props.terrain.titulaire.fichierPDF;
        console.log('📄 PDF existant:', fichierPDFExistant.value);
    }
});

// ✅ FONCTION CORRECTE POUR LE PDF
function handleFileUpload(event) {
    const file = event.target.files[0];
    
    console.log('📄 Fichier sélectionné:', file);
    
    if (!file) {
        fichierPDF.value = null;
        isNewFileSelected.value = false;
        return;
    }
    
    // Vérifier le type
    if (file.type !== 'application/pdf') {
        toast.error('❌ Veuillez sélectionner un fichier PDF valide');
        event.target.value = '';
        fichierPDF.value = null;
        isNewFileSelected.value = false;
        return;
    }
    
    // Vérifier la taille (max 10MB)
    if (file.size > 10 * 1024 * 1024) {
        toast.error('❌ Le fichier ne doit pas dépasser 10 Mo');
        event.target.value = '';
        fichierPDF.value = null;
        isNewFileSelected.value = false;
        return;
    }
    
    // ✅ STOCKER LE FICHIER
    fichierPDF.value = file;
    isNewFileSelected.value = true;
    
    console.log('✅ Nouveau PDF chargé:', {
        name: file.name,
        size: file.size,
        type: file.type
    });
    
    toast.success('✅ Fichier PDF chargé avec succès');
}


// ✅ SUBMIT

onMounted(() => {
    if (props.terrain?.titulaires?.fichierPDF) {
        fichierPDFExistant.value = props.terrain.titulaires.fichierPDF;
        console.log("PDF existant :", fichierPDFExistant.value);
    }
});

const submit = () => {
    if (!props.terrain?.id) {
        toast.error("Aucun terrain à modifier");
        return;
    }

    const required = {
        txt_num_dossier: 'Numéro de dossier',
        slt_region: 'Région',
        slt_departement: 'Département',
        slt_arrondissement: 'Arrondissement',
        slt_commune: 'Commune',
        dt_date_creation: 'Date de création',
        rd_immatriculation_terrain: 'Immatriculation du terrain',
        slt_titulaire: 'Titulaire'
    };

    const errors = [];

    for (const [field, label] of Object.entries(required)) {
        const value = form[field];

        if (value === '' || value === null || value === undefined) {
            errors.push(`"${label}" est obligatoire`);
        }
    }

    if (errors.length > 0) {
        errors.forEach(msg => toast.error(msg));
        return;
    }

    const data = {
        txt_num_dossier: (form.txt_num_dossier || '').toString().trim(),
        slt_region: (form.slt_region || '').toString(),
        slt_departement: (form.slt_departement || '').toString(),
        slt_arrondissement: (form.slt_arrondissement || '').toString(),
        slt_commune: (form.slt_commune || '').toString(),
        dt_date_creation: (form.dt_date_creation || '').toString(),

        txt_nicad: (form.txt_nicad || '').toString().trim().replace(/[\r\n]+/g, ''),
        rd_immatriculation_terrain: (form.rd_immatriculation_terrain || '').toString(),
        slt_titulaire: (form.slt_titulaire || '').toString(),

        txt_lotissement: (form.txt_lotissement || '').toString().trim(), 

        txt_HorsLotissement: (form.txt_HorsLotissement || '').toString().trim(),
        txt_num_lotissement: (form.txt_num_lotissement || '').toString().trim(),
        txt_num_section: (form.txt_num_section || '').toString().trim(),
        txt_num_parcelle: (form.txt_num_parcelle || '').toString().trim(),
        nbr_surface: form.nbr_surface || 0,

        slt_document_admin: (form.slt_document_admin || '').toString(),
        txt_num_deliberation: (form.txt_num_deliberation || '').toString().trim(),
        dt_date_deliberation: (form.dt_date_deliberation || '').toString(),

        slt_service_rendu: (form.slt_service_rendu || '').toString(),
        txt_etat_cession: (form.txt_etat_cession || '').toString().trim(),
        txt_cession_definitive: (form.txt_cession_definitive || '').toString().trim(),

        slt_dependant_domaine: (form.slt_dependant_domaine || '').toString(),
        ussu_bornage: (form.ussu_bornage || '').toString(),
        txt_titre_mere: (form.txt_titre_mere || '').toString().trim(),
        txt_num_titre: (form.txt_num_titre || '').toString().trim(),
        txt_appartement: (form.txt_appartement || '').toString().trim(),
        slt_lf: (form.slt_lf || '').toString(),
        txt_num_requisition: (form.txt_num_requisition || '').toString().trim(),
        txt_surface_bornage: (form.txt_surface_bornage || '').toString().trim(),
        dt_date_bornage: (form.dt_date_bornage || '').toString(),
        txt_nom_geometre: (form.txt_nom_geometre || '').toString().trim(),

        txt_nationalite: (form.txt_nationalite || '').toString().trim(),
        slt_civilite: (form.slt_civilite || '').toString(),
        txt_prenom: (form.txt_prenom || '').toString().trim(),
        txt_nom: (form.txt_nom || '').toString().trim(),
        slt_piece: (form.slt_piece || '').toString(),
        txt_numPiece: (form.txt_numPiece || '').toString().trim(),
        dt_date_delivrance: (form.dt_date_delivrance || '').toString(),
        dt_date_naissance: (form.dt_date_naissance || '').toString(),
        txt_lieu_naissance: (form.txt_lieu_naissance || '').toString().trim(),
        txt_adresse: (form.txt_adresse || '').toString().trim(),
        tel_telephone: (form.tel_telephone || '').toString().trim(),
        txt_ninea: (form.txt_ninea || '').toString().trim(),
        eml_email: (form.eml_email || '').toString().trim(),

        occupants: form.occupants || [],
        occupantsBP: form.occupantsBP || [],
        occupantsCA: form.occupantsCA || [],
        occupantsCL: form.occupantsCL || [],
        occupantsAP: form.occupantsAP || [],

        _method: 'PUT'
    };

    toast.info("Enregistrement en cours...");

    router.post(route('secretariat.update', props.terrain.id), data, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Modification réussie !");
            setTimeout(() => router.visit(route('donnees.create')), 1500);
        },
        onError: (errors) => {
            console.error('Erreurs de validation:', errors);

            Object.values(errors || {}).forEach((msg) => {
                if (typeof msg === 'string' && msg.trim()) {
                    toast.error(msg);
                }
            });
        }
    });
};
 
 
// ✅ onMounted
onMounted(() => {
    toast.info("🔔 Page de modification chargée");
    handleSelectChange();
    handleSelectChangeTitulaire();
});
</script>

<template>
  <Head title="Modifier Terrain" > 
      <meta name="csrf-token" content="{{ csrf_token() }}">
  </Head>

  <AuthenticatedLayout>
    <template #header>
        <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Modification du Terrain
        </h2>
    </template>

    <div class="py-12"> 
        <div class="w-full max-w-8xl">   
            <AppCodeAccee
                v-model="showModal"
                :loading="isLoading"
                @confirm="codeDaccees"
            />
        </div>

        <div class="flex justify-center">  
            <div class="w-full max-w-8xl"> 
                    <div v-if="formVisible" class="w-full">
                        <div class="bg-white shadow-md rounded-xl">
                            <div class="p-4 border-b bg-primary rounded-t-xl">
                                <h1 class="text-2xl text-white font-semibold">Pages de Modification !</h1>
                            </div>
                            <div class="p-6">  
                                <template v-if="terrain">
                                    <div class="flex justify-center"> 
                                    <h1 class="text-2xl text-gray-400 font-bold italic">
                                        Modifier la parcelle de numéro dossier : {{ terrain.dossier.txt_num_dossier }}
                                    </h1> 
                                    </div>

                                    <form @submit.prevent="submit" class="space-y-6"> 
                                        <div class="p-6"> 
                                            <div class="sm:col-span-12">

                                                <h5 class="text-lg text-primary-txt font-bold">Reference Dossier</h5><br>

                                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"> 
                                                <BaseInput
                                                    class="sm:col-span-2"
                                                    name="txt_num_dossier" 
                                                    v-model="form.txt_num_dossier"
                                                    id="txt_num_dossier"
                                                    label="Numéro de dossier"
                                                    :error="form.errors.txt_num_dossier"
                                                    fontSize="14px"
                                                />

                                                <BaseSelect 
                                                    name="slt_service_rendu"
                                                    v-model="form.slt_service_rendu"
                                                    id="slt_service_rendu"
                                                    label="Service Rendu"
                                                    :error="form.errors.slt_service_rendu"
                                                    :options="[
                                                        {value:'Delimitation', label:'Delimitation'},
                                                        {value:'Extrait de plan', label:'Extrait de plan'},
                                                        {value:'Implantation', label:'Implantation'},
                                                        {value:'Plan de situation', label:'Plan de situation'},
                                                        {value:'Division', label:'Division'},
                                                        {value:'Reconstitution des limites', label:'Reconstitution des limites'},
                                                        {value:'Etat des lieux', label:'Etat des lieux'},
                                                        {value:'Expertise immobilière', label:'Expertise immobilière'},
                                                        {value:'Enquête cadastrale', label:'Enquête cadastrale'},
                                                    ]" 
                                                />
                                                
                                                <BaseInput
                                                    name="txt_etat_cession" 
                                                    v-model="form.txt_etat_cession"
                                                    id="txt_etat_cession"
                                                    label="Etat Cession"
                                                    :error="form.errors.txt_etat_cession"
                                                    fontSize="14px"
                                                /> 

                                                <BaseInput
                                                    name="txt_cession_definitive" 
                                                    v-model="form.txt_cession_definitive"
                                                    id="txt_cession_definitive"
                                                    label="Cession Definitive"
                                                    :error="form.errors.txt_cession_definitive"
                                                    fontSize="14px"
                                                /> 
                                                
                                                <BaseInput
                                                    type="date"
                                                    name="dt_date_creation" 
                                                    v-model="form.dt_date_creation"
                                                    id="dt_date_creation"
                                                    label="Date creation"
                                                    :error="form.errors.dt_date_creation"
                                                    fontSize="14px"
                                                />
                                                </div><br> 
                                        
                                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                <BaseSelect
                                                    name="slt_region"
                                                    v-model="form.slt_region"
                                                    id="slt_region"
                                                    label="Région"
                                                    :error="form.errors.slt_region"
                                                    :options="regions.map(r => ({ value: r.id, label: r.slt_region }))"
                                                />
                                                
                                                <BaseSelect
                                                    name="slt_departement"
                                                    v-model="form.slt_departement"
                                                    id="slt_departement"
                                                    label="Département"
                                                    :error="form.errors.slt_departement"
                                                    :options="departements.map(d => ({ value: d.id, label: d.slt_departement }))"
                                                />
                                                
                                                <BaseSelect
                                                    name="slt_arrondissement"
                                                    v-model="form.slt_arrondissement"
                                                    id="slt_arrondissement"
                                                    label="Arrondissement"
                                                    :error="form.errors.slt_arrondissement"
                                                    :options="arrondissements.map(a => ({ value: a.id, label: a.slt_arrondissement }))"
                                                />
                                                
                                                <BaseSelect
                                                    name="slt_commune"
                                                    v-model="form.slt_commune"
                                                    id="slt_commune"
                                                    label="Commune"
                                                    :error="form.errors.slt_commune"
                                                    :options="communes.map(c => ({ value: c.id, label: c.slt_commune }))"
                                                />

                                                <BaseSelect
                                                    label="Lotissement/Quartier"
                                                    :options="lotissements"
                                                    optionLabel="txt_denomination"
                                                    optionValue="txt_denomination"  
                                                    v-model="form.txt_lotissement" 
                                                    :error="form.errors.txt_lotissement"
                                                    fontSize="14px"
                                                />
                                                    
                                                <BaseInput
                                                    name="txt_HorsLotissement" 
                                                    v-model="form.txt_HorsLotissement"
                                                    id="txt_HorsLotissement"
                                                    label="Hors Lotissement"
                                                    :error="form.errors.txt_HorsLotissement"
                                                    fontSize="14px"
                                                /> 
                                                    
                                                <BaseInput
                                                    name="txt_num_lotissement" 
                                                    v-model="form.txt_num_lotissement"
                                                    id="txt_num_lotissement"
                                                    label="N° Lot"
                                                    :error="form.errors.txt_num_lotissement"
                                                    fontSize="14px"
                                                /> 
                                                    
                                                <BaseInput
                                                    name="nbr_surface" 
                                                    v-model="form.nbr_surface"
                                                    id="nbr_surface"
                                                    label="Surface"
                                                    :error="form.errors.nbr_surface"
                                                    fontSize="14px"
                                                    maxlength="5"
                                                    minlength="5"
                                                /> 
                                                    
                                                <BaseSelect
                                                    label="Doc.Adminitratif"
                                                    name="slt_document_admin"
                                                    :options="[
                                                        {value:'Lettre D\'atribution', label:'Lettre D\'atribution'},
                                                        {value:'Extrait de plan', label:'Extrait de plan'},
                                                        {value:'Notification', label:'Notification'},
                                                        {value:'Bail', label:'Bail'},
                                                        {value:'Acte adminitratif', label:'Acte adminitratif'},
                                                        {value:'Permis d\'occupation', label:'Permis d\'occupation'},
                                                        {value:'Déliberation', label:'Déliberation'},
                                                        {value:'Atestation Foncière', label:'Atestation Foncière'},
                                                        {value:'Acte de vente', label:'Acte de vente'},
                                                        {value:'Sans Papier', label:'Sans Papier'}, 
                                                        {value:'TF', label:'TF'}, 
                                                    ]"
                                                    v-model="form.slt_document_admin"
                                                    fontSize="14px"
                                                />  

                                                <BaseInput
                                                    name="txt_num_deliberation" 
                                                    v-model="form.txt_num_deliberation"
                                                    id="txt_num_deliberation"
                                                    label="N° Déliberation"
                                                    fontSize="14px"
                                                /> 
                                            
                                                <BaseInput
                                                    name="dt_date_deliberation" 
                                                    v-model="form.dt_date_deliberation"
                                                    id="dt_date_deliberation"
                                                    label="Date Déliberation"
                                                    type="date"
                                                    fontSize="14px"
                                                /> 
                        
                                                <BaseInput
                                                    name="txt_num_section" 
                                                    v-model="form.txt_num_section"
                                                    id="txt_num_section"
                                                    label="N° Section" 
                                                    :customError="errorMessageNumSection"
                                                    @input="validateInputNumSection"
                                                    :maxlength="3"
                                                    :minlength="3"
                                                    fontSize="14px"
                                                /> 

                                                <BaseInput
                                                    name="txt_num_parcelle" 
                                                    v-model="form.txt_num_parcelle"
                                                    id="txt_num_parcelle"
                                                    label="N° Parcelle" 
                                                    :customError="errorMessageNumParcelle"
                                                    @input="validateInputNumParcelle"
                                                    :maxlength="5"
                                                    :minlength="5"
                                                    fontSize="14px"
                                                /> 
                                                    
                                                <BaseInput
                                                    class="sm:col-span-2"
                                                    name="txt_nicad" 
                                                    v-model="form.txt_nicad"
                                                    id="txt_nicad"
                                                    label="NICAD" 
                                                    :readonly="!!form.txt_nicad"
                                                    fontSize="14px"
                                                />   
                                                </div><br>

                                                <h5 class="text-lg text-primary-txt font-bold">Reference Cadastrale</h5><br>
                                                
                                                <div class="maz-py-4"> 
                                                    <MazRadio
                                                        v-model="form.rd_immatriculation_terrain" 
                                                        class="text-primary-txt"
                                                        value="Terrain Non Immatriculé"
                                                        label="Terrain Non Immatriculé"
                                                    />
                                                    <MazRadio
                                                        v-model="form.rd_immatriculation_terrain" 
                                                        class="text-primary-txt"
                                                        value="Terrain Immatriculé"
                                                        label="Terrain Immatriculé"
                                                    />
                                                    <div v-if="form.errors.rd_immatriculation_terrain" class="text-red-500">{{ form.errors.rd_immatriculation_terrain }}</div>
                                                </div>
                                                
                                                <div v-if="showNonImmatricule" class="maz-py-4"> 
                                                    <div>
                                                        <br/> 
                                                        <div class="sm:col-span-12">
                                                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"> 
                                                                <BaseSelect
                                                                    class="sm:col-span-2"
                                                                    name="slt_categorie" 
                                                                    v-model="form.slt_dependant_domaine" 
                                                                    id="slt_categorie"
                                                                    label="Catégorie"
                                                                    :error="form.errors.slt_dependant_domaine"
                                                                    :options="[
                                                                        {value:'Domaine National', label:'Domaine National'},
                                                                        {value:'Domaine Public', label:'Domaine Public'},
                                                                        {value:'Domaine Fluvial', label:'Domaine Fluvial'},
                                                                        {value:'Domaine Maritime', label:'Domaine Maritime'},
                                                                        {value:'Autres', label:'Autres'},
                                                                    ]"
                                                                    fontSize="14px"
                                                                /> 
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            
                                                <div v-if="showImmatricule" class="maz-py-4">
                                                    <br>
                                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                        <BaseSelect
                                                            name="ussu_bornage"
                                                            v-model="form.ussu_bornage"
                                                            id="ussu_bornage"
                                                            label="Ussu de bornage"
                                                            :options="[
                                                                {value:'Immatriculation', label:'Immatriculation'},
                                                                {value:'Morcellement', label:'Morcellement'},
                                                                {value:'Rectificatif de Limite', label:'Rectificatif de Limite'},
                                                                {value:'Fusion', label:'Fusion'},
                                                                {value:'Morcellement de Copropriété', label:'Morcellement de Copropriété'},
                                                            ]"
                                                            @change="handleSelectChange"
                                                            fontSize="14px"
                                                        />
                                                    </div>
                                                
                                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-4">
                                                        <BaseInput
                                                            name="txt_titre_mere"
                                                            v-model="form.txt_titre_mere"
                                                            id="Titre_mere"
                                                            label="Titre Mere"
                                                            fontSize="14px"
                                                        />

                                                        <BaseInput
                                                            name="txt_num_titre"
                                                            v-model="form.txt_num_titre"
                                                            id="numTitre"
                                                            label="N° Titre"
                                                            fontSize="14px"
                                                        />
                                                        
                                                        <BaseSelect
                                                            name="slt_lf"
                                                            v-model="form.slt_lf"
                                                            id="slt_lf"
                                                            label="LF"
                                                            :options="[
                                                                {value:'NO', label:'NO'},
                                                                {value:'KG', label:'KG'},
                                                                {value:'SM', label:'SM'},
                                                                {value:'SR', label:'SR'},
                                                            ]"
                                                            fontSize="14px"
                                                        />

                                                        <BaseInput
                                                            name="txt_num_requisition"
                                                            v-model="form.txt_num_requisition"
                                                            id="Num_requisition"
                                                            label="N° Requisition"
                                                            fontSize="14px"
                                                        />

                                                        <BaseInput
                                                            name="txt_surface_bornage"
                                                            v-model="form.txt_surface_bornage"
                                                            id="Surface_bornage"
                                                            label="Surface au bornage"
                                                            fontSize="14px"
                                                        />

                                                        <BaseInput
                                                            type="date"
                                                            name="dt_date_bornage"
                                                            v-model="form.dt_date_bornage"
                                                            id="Date_bornage"
                                                            label="Date Bornage"
                                                            fontSize="14px"
                                                        />
                                                        
                                                        <BaseInput
                                                            v-if="showMorcellement"
                                                            name="txt_appartement"
                                                            v-model="form.txt_appartement"
                                                            id="txt_appartement"
                                                            label="Appartement"
                                                            :customError="errorMessage"
                                                            @input="validateInputApartement"
                                                            maxlength="3"
                                                            minlength="3"
                                                            fontSize="14px"
                                                        />

                                                        <BaseInput
                                                            name="txt_nom_geometre"
                                                            v-model="form.txt_nom_geometre"
                                                            id="Nom_geometre"
                                                            label="Nom Geometre"
                                                            fontSize="14px"
                                                        />
                                                    </div>
                                                </div>
                                                
                                                <br>
                                            
                                                <h5 class="text-lg font-bold">Identité Titulaire de droit</h5><br>

                                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">  
                                                    <div class="sm:col-span-2">
                                                        <BaseSelect
                                                            name="slt_titulaire"
                                                            v-model="form.slt_titulaire"
                                                            id="slt_titulaire"
                                                            label="Titulaire"
                                                            :error="form.errors.slt_titulaire"
                                                            :options="[
                                                                {value:'Personne Physique', label:'Personne Physique'},
                                                                {value:'Personne Morale', label:'Personne Morale'},
                                                                {value:'Etat', label:'Etat'},
                                                            ]"
                                                            @change="handleSelectChangeTitulaire"
                                                            fontSize="14px"
                                                        />
                                                    </div>
                                                
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="txt_nationalite"
                                                        v-model="form.txt_nationalite"
                                                        id="txt_nationalite"
                                                        label="Nationalite"
                                                        :error="form.errors.txt_nationalite"
                                                        fontSize="14px"
                                                    />
                                                
                                                    <BaseSelect
                                                        v-if="showSectionPP"
                                                        name="slt_civilite"
                                                        v-model="form.slt_civilite"
                                                        id="slt_civilite"
                                                        label="Civilite"
                                                        :options="[
                                                            {value:'Mr', label:'Mr'},
                                                            {value:'Mme', label:'Mme'},
                                                        ]"
                                                        :error="form.errors.slt_civilite"
                                                        fontSize="14px"
                                                    />
                                                
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="txt_prenom"
                                                        v-model="form.txt_prenom"
                                                        id="txt_prenom"
                                                        label="Prénom"
                                                        :error="form.errors.txt_prenom"
                                                        fontSize="14px"
                                                    />
                                                                                
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="txt_nom"
                                                        v-model="form.txt_nom"
                                                        id="txt_nom"
                                                        label="Nom"
                                                        :error="form.errors.txt_nom"
                                                        fontSize="14px"
                                                    />
                                                                                
                                                    <BaseSelect
                                                        v-if="showSectionPP"
                                                        name="slt_piece"
                                                        v-model="form.slt_piece"
                                                        id="slt_piece"
                                                        label="Pièce"
                                                        :error="form.errors.slt_piece"
                                                        :options="[
                                                            {value:'CNI', label:'CNI'},
                                                            {value:'Passeport', label:'Passeport'},
                                                            {value:'Etat', label:'Etat'},
                                                        ]"
                                                        fontSize="14px"
                                                    />
                                                                                
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="txt_numPiece"
                                                        v-model="form.txt_numPiece"
                                                        id="txt_numPiece"
                                                        label="N° Pièce"
                                                        :error="form.errors.txt_numPiece"
                                                        fontSize="14px"
                                                    />
                                                                                
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        type="date"
                                                        name="dt_date_delivrance"
                                                        v-model="form.dt_date_delivrance"
                                                        id="dt_date_delivrance"
                                                        label="Date Délivrance"
                                                        :error="form.errors.dt_date_delivrance"
                                                        fontSize="14px"
                                                    />
                                                                                
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        type="date"
                                                        name="dt_date_naissance"
                                                        v-model="form.dt_date_naissance"
                                                        id="dt_date_naissance"
                                                        label="Date Naissance"
                                                        :error="form.errors.dt_date_naissance"
                                                        fontSize="14px"
                                                    />
                                                                                
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="txt_lieu_naissance"
                                                        v-model="form.txt_lieu_naissance"
                                                        id="txt_lieu_naissance"
                                                        label="Lieu Naissance"
                                                        :error="form.errors.txt_lieu_naissance"
                                                        fontSize="14px"
                                                    />
                                                                                
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="txt_adresse"
                                                        v-model="form.txt_adresse"
                                                        id="txt_adresse"
                                                        label="Adresse"
                                                        :error="form.errors.txt_adresse"
                                                        fontSize="14px"
                                                    />
                                                                                                            
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="tel_telephone"
                                                        v-model="form.tel_telephone"
                                                        id="tel_telephone"
                                                        label="Telephone"
                                                        :error="form.errors.tel_telephone"
                                                        fontSize="14px"
                                                    />
                                                                                                            
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="eml_email"
                                                        v-model="form.eml_email"
                                                        id="eml_email"
                                                        label="Email"
                                                        :error="form.errors.eml_email"
                                                        fontSize="14px"
                                                    />
                                                                                                            
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="txt_representant"
                                                        v-model="form.txt_representant"
                                                        id="txt_representant"
                                                        label="Representant"
                                                        :error="form.errors.txt_representant"
                                                        fontSize="14px"
                                                    />
                                                                                                            
                                                    <BaseInput
                                                        v-if="showSectionPP"
                                                        name="tel_telRepresentant"
                                                        v-model="form.tel_telRepresentant"
                                                        id="tel_telRepresentant"
                                                        label="Tel Representant"
                                                        :error="form.errors.tel_telRepresentant"
                                                        fontSize="14px"
                                                    />
                                                
                                                    <!-- Personne Morale -->
                                                    <BaseSelect
                                                        v-if="showSectionPM"
                                                        name="slt_categoriePM"
                                                        v-model="form.slt_categoriePM"
                                                        id="slt_categoriePM"
                                                        label="Catégorie"
                                                        :options="[
                                                            {value:'Station', label:'Station'},
                                                            {value:'Industrue', label:'Industrue'},
                                                            {value:'Restaurant', label:'Restaurant'},
                                                            {value:'Hotel', label:'Hotel'},
                                                            {value:'Hoberge', label:'Hoberge'},
                                                            {value:'Elevage', label:'Elevage'},
                                                            {value:'Agrobusness', label:'Agrobusness'},
                                                            {value:'Epicerie', label:'Epicerie'},
                                                            {value:'Alimentation Générale', label:'Alimentation Générale'},
                                                            {value:'Qincaillerie', label:'Qincaillerie'},
                                                            {value:'Transport', label:'Transport'},
                                                            {value:'Coopérative Habitat', label:'Coopérative Habitat'},
                                                            {value:'BTP', label:'BTP'},
                                                            {value:'Organisation Religieux', label:'Organisation Religieux'},
                                                            {value:'Securité', label:'Securité'},
                                                            {value:'Santé', label:'Santé'},
                                                            {value:'Mine', label:'Mine'},
                                                            {value:'Etablissement Financier', label:'Etablissement Financier'},
                                                            {value:'Autres', label:'Autres'},
                                                        ]"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPM"
                                                        name="txt_formJuridiquePM"
                                                        v-model="form.txt_formJuridiquePM"
                                                        id="txt_formJuridiquePM"
                                                        label="Forme Juridique"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPM"
                                                        name="txt_denominationPM"
                                                        v-model="form.txt_denominationPM"
                                                        id="txt_denominationPM"
                                                        label="Dénomination"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPM"
                                                        name="txt_nineaPM"
                                                        v-model="form.txt_nineaPM"
                                                        id="txt_nineaPM"
                                                        label="Ninea"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPM"
                                                        name="txt_perssonneRepresentantPM"
                                                        v-model="form.txt_perssonneRepresentantPM"
                                                        id="txt_perssonneRepresentantPM"
                                                        label="Nom Personne Responsable"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPM"
                                                        name="txt_fonctionPM"
                                                        v-model="form.txt_fonctionPM"
                                                        id="txt_fonctionPM"
                                                        label="Fonction Responsable"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPM"
                                                        name="txt_telephonePM"
                                                        v-model="form.txt_telephonePM"
                                                        id="txt_telephonePM"
                                                        label="Téléphone Responsable"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPM"
                                                        name="txt_emailPM"
                                                        v-model="form.txt_emailPM"
                                                        id="txt_emailPM"
                                                        label="Email"
                                                        type="email"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPM"
                                                        name="txt_adressePM"
                                                        v-model="form.txt_adressePM"
                                                        id="txt_adressePM"
                                                        label="Adresse Sciège"
                                                        fontSize="14px"
                                                    />

                                                    <!-- Etat -->
                                                    <BaseSelect
                                                        v-if="showSectionPA"
                                                        name="slt_etablissementPA"
                                                        v-model="form.slt_etablissementPA"
                                                        id="slt_etablissementPA"
                                                        label="Etablissement"
                                                        :options="[
                                                            {value:'Sanitaire', label:'Sanitaire'},
                                                            {value:'Scolaire', label:'Scolaire'},
                                                            {value:'Millitaire', label:'Millitaire'},
                                                            {value:'Administratif', label:'Administratif'},
                                                            {value:'Aéroportuaire', label:'Aéroportuaire'},
                                                            {value:'Sportif', label:'Sportif'},
                                                            {value:'Universitaire', label:'Universitaire'},
                                                            {value:'Autres', label:'Autres'},
                                                        ]"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPA"
                                                        name="txt_personneResponsablePA"
                                                        v-model="form.txt_personneResponsablePA"
                                                        id="txt_personneResponsablePA"
                                                        label="Personne Responsable"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPA"
                                                        name="txt_fonctionResponsablePA"
                                                        v-model="form.txt_fonctionResponsablePA"
                                                        id="txt_fonctionResponsablePA"
                                                        label="Fonction Responsable"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPA"
                                                        name="txt_telephonePA"
                                                        v-model="form.txt_telephonePA"
                                                        id="txt_telephonePA"
                                                        label="Téléphone"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPA"
                                                        name="txt_emailPA"
                                                        v-model="form.txt_emailPA"
                                                        id="txt_emailPA"
                                                        label="Email"
                                                        type="email"
                                                        fontSize="14px"
                                                    />
                                                    
                                                    <BaseInput
                                                        v-if="showSectionPA"
                                                        name="txt_ministèreTutelePA"
                                                        v-model="form.txt_ministèreTutelePA"
                                                        id="txt_ministèreTutelePA"
                                                        label="Ministère Tutelle"
                                                        fontSize="14px"
                                                    />
                                                </div>

                                                <!-- References usages -->
                                                <h5 class="text-lg font-bold mt-8">Réferences Usage</h5><br>

                                                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4"> 
                                                    <BaseSelect
                                                        name="slt_usage"
                                                        v-model="form.slt_usage"
                                                        id="slt_usage"
                                                        label="Usage"
                                                        :error="form.errors?.slt_usage"
                                                        :options="[
                                                            {value:'Habitation', label:'Habitation'},
                                                            {value:'Commercial', label:'Commercial'},
                                                            {value:'Agricole', label:'Agricole'},
                                                            {value:'Industriel', label:'Industriel'},
                                                            {value:'Professionnelle', label:'Professionnelle'},
                                                            {value:'Culte', label:'Culte'},
                                                            {value:'Administratif', label:'Administratif'},
                                                        ]"
                                                        fontSize="14px"
                                                    />

                                                    <BaseSelect
                                                        name="slt_residence"
                                                        v-model="form.slt_residence"
                                                        id="slt_residence"
                                                        label="Type de résidence"
                                                        :error="form.errors?.slt_residence"
                                                        :options="[
                                                            {value:'Residence Principal', label:'Residence Principal'},
                                                            {value:'Residence Secondaire', label:'Residence Secondaire'},
                                                        ]"
                                                        fontSize="14px"
                                                    />
                                                </div><br>
                    
                                                <div class="w-full flex flex-wrap justify-between gap-4 mt-4"> 
                                                    <TransitionGroup name="slide-fade" v-if="form.occupants.length" tag="div">
                                                        <div 
                                                            v-for="(occupant, i) in form.occupants" 
                                                            :key="i" 
                                                            class="sm:col-span-8"
                                                        > 
                                                            <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                                <BaseInput
                                                                    class="w-28"
                                                                    :name="'txt_nomOccupantTG_' + i"
                                                                    v-model="occupant.txt_nomOccupantTG"
                                                                    :id="'txt_nomOccupantTG_' + i"
                                                                    :label="'Nom Occupant ' + i"
                                                                    fontSize="14px"
                                                                />

                                                                <BaseInput
                                                                    class="w-20"
                                                                    :name="'txt_numAppartementTG_' + i"
                                                                    v-model="occupant.txt_numAppartementTG"
                                                                    :id="'txt_numAppartementTG_' + i"
                                                                    label="N° Appart"
                                                                    fontSize="14px"
                                                                />

                                                                <BaseInput
                                                                    class="w-20"
                                                                    :name="'txt_activiteTG_' + i"
                                                                    v-model="occupant.txt_activiteTG"
                                                                    :id="'txt_activiteTG_' + i"
                                                                    label="Activite"
                                                                    fontSize="14px"
                                                                />

                                                                <BaseInput
                                                                    class="w-20"
                                                                    :name="'txt_nineaTG_' + i"
                                                                    v-model="occupant.txt_nineaTG"
                                                                    :id="'txt_nineaTG_' + i"
                                                                    label="Ninea"
                                                                    fontSize="14px"
                                                                />

                                                                <BaseInput
                                                                    class="w-20"
                                                                    :name="'tel_telephoneTG_' + i"
                                                                    v-model="occupant.tel_telephoneTG"
                                                                    :id="'tel_telephoneTG_' + i"
                                                                    label="Téléphone"
                                                                    fontSize="14px"
                                                                />

                                                                <BaseInput
                                                                    class="w-20"
                                                                    type="number"
                                                                    :name="'nbr_montantLoyerTG_' + i"
                                                                    v-model="occupant.nbr_montantLoyerTG"
                                                                    :id="'nbr_montantLoyerTG_' + i"
                                                                    label="Mnt Loyer"
                                                                    fontSize="14px"
                                                                />

                                                                <BaseInput
                                                                    class="w-20"
                                                                    :name="'txt_dateLieuNaissanceTG_' + i"
                                                                    v-model="occupant.txt_dateLieuNaissanceTG"
                                                                    :id="'txt_dateLieuNaissanceTG_' + i"
                                                                    label="Date-lieu Nais"
                                                                    fontSize="14px"
                                                                />

                                                                <BaseInput
                                                                    class="w-20"
                                                                    :name="'txt_cniPasseportTG_' + i"
                                                                    v-model="occupant.txt_cniPasseportTG"
                                                                    :id="'txt_cniPasseportTG_' + i"
                                                                    label="CNI/Pasprt"
                                                                    fontSize="14px"
                                                                />

                                                                <BaseInput
                                                                    class="w-20"
                                                                    type="date"
                                                                    :name="'dt_dateDelivranceTG_' + i"
                                                                    v-model="occupant.dt_dateDelivranceTG"
                                                                    :id="'dt_dateDelivranceTG_' + i"
                                                                    label="Date Déliv"
                                                                    fontSize="14px"
                                                                />
                                                            </div>
                                                        </div>
                                                    </TransitionGroup> 

                                                    <div class="flex gap-4 mt-6">
                                                        <button @click="addBlock" type="button"
                                                            class="h-8 text-white bg-primary hover:bg-primary-dark font-medium rounded-lg text-sm px-5 py-2.5">
                                                            Ajouter
                                                        </button>

                                                        <button @click="removeBlock" type="button"
                                                            class="h-8 text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-2.5">
                                                            Retirer
                                                        </button>
                                                    </div>
                                                </div><br><br>
                                                
                                                <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                    <BaseInput
                                                        class="w-64"
                                                        type="number"
                                                        name="nbr_montantLoyerTotal"
                                                        v-model="form.nbr_montantLoyerTotal"
                                                        id="MontantLoyerTotal"
                                                        label="Montant Loyer Total"
                                                        :readonly="true"
                                                        fontSize="14px"
                                                    />

                                                    <BaseInput
                                                        class="w-64"
                                                        type="number"
                                                        name="nbr_TVATotal"
                                                        :value="form.nbr_TVATotal"
                                                        id="TVATotal"
                                                        label="TVA Totale"
                                                        :readonly="true"
                                                        fontSize="14px"
                                                    />
                                                </div>
                    
                                            </div>

                                        <h5 class="text-lg font-bold mt-8">Evaluation de l'immeuble</h5><br>

                                        <div class="mb-6">
                                            <div class="border-b">
                                                <ul class="flex justify-center text-sm font-medium text-gray-500 divide-x divide-gray-200 dark:divide-gray-700">
                                                    <li class="w-1/5 text-center">
                                                        <button
                                                            type="button"
                                                            @click="setActiveTab('terrain')"
                                                            :class="{
                                                                'text-primary border-b-8 border border-primary': activeTab === 'terrain',
                                                                'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'terrain'
                                                            }"
                                                            class="inline-block w-full p-4"
                                                        >
                                                            Terrain
                                                        </button>
                                                    </li>

                                                    <li class="w-1/5 text-center">
                                                        <button
                                                            type="button"
                                                            @click="setActiveTab('bati')"
                                                            :class="{
                                                                'text-primary border-b-8 border border-primary': activeTab === 'bati',
                                                                'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'bati'
                                                            }"
                                                            class="inline-block w-full p-4"
                                                        >
                                                            Bâti
                                                        </button>
                                                    </li>

                                                    <li class="w-1/5 text-center">
                                                        <button
                                                            type="button"
                                                            @click="setActiveTab('Cours_amenagees')"
                                                            :class="{
                                                                'text-primary border-b-8 border border-primary': activeTab === 'Cours_amenagees',
                                                                'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'Cours_amenagees'
                                                            }"
                                                            class="inline-block w-full p-4"
                                                        >
                                                            Cours aménagées
                                                        </button>
                                                    </li>

                                                    <li class="w-1/5 text-center">
                                                        <button
                                                            type="button"
                                                            @click="setActiveTab('cloture')"
                                                            :class="{
                                                                'text-primary border-b-8 border border-primary': activeTab === 'cloture',
                                                                'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'cloture'
                                                            }"
                                                            class="inline-block w-full p-4"
                                                        >
                                                            Clôtures
                                                        </button>
                                                    </li>
                                                    
                                                    <li class="w-1/5 text-center">
                                                        <button
                                                            type="button"
                                                            @click="setActiveTab('amenagement')"
                                                            :class="{
                                                                'text-primary border-b-8 border border-primary': activeTab === 'amenagement',
                                                                'hover:text-lg text-primary-txt hover:border-primary': activeTab !== 'amenagement'
                                                            }"
                                                            class="inline-block w-full p-4"
                                                        >
                                                            Aménagements
                                                        </button>
                                                    </li> 
                                                </ul>
                                            </div>

                                            <!-- TERRAIN -->
                                            <div class="p-6">
                                                <div v-show="activeTab === 'terrain'" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                    <div class="sm:col-span-12">
                                                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                            <BaseInput
                                                                class="sm:col-span-2"
                                                                type="number"
                                                                name="txt_superficie_totale"
                                                                v-model="form.txt_superficie_totale"
                                                                id="Superficie_totale"
                                                                label="Superficie Totale Terrain"
                                                                :readonly="true"
                                                                fontSize="14px"
                                                            />

                                                            <BaseInput
                                                                class="sm:col-span-2"
                                                                type="number"
                                                                name="txt_superficie_bati_sol"
                                                                v-model="form.txt_superficie_bati_sol"
                                                                id="Superficie_bati_sol"
                                                                label="Superficie Bâti au sol"
                                                                :min="0"
                                                                :max="form.nbr_surface"
                                                                fontSize="14px"
                                                            />

                                                            <BaseInput
                                                                class="sm:col-span-2"
                                                                type="number"
                                                                name="txt_superficie_non_bati"
                                                                v-model="form.txt_superficie_non_bati"
                                                                id="Superficie_non_bati"
                                                                label="Superficie Non Bâti"
                                                                :readonly="true"
                                                                :min="0"
                                                                fontSize="14px"
                                                            />

                                                            <BaseSelect
                                                                class="sm:col-span-2"
                                                                name="slt_secteur"
                                                                v-model="form.slt_secteur"
                                                                id="slt_secteur"
                                                                label="Secteur"
                                                                :options="[
                                                                    {value:1, label:'1'},
                                                                    {value:2, label:'2'},
                                                                    {value:3, label:'3'},
                                                                ]"
                                                                fontSize="14px"
                                                            />

                                                            <BaseInput
                                                                class="sm:col-span-2"
                                                                type="number"
                                                                name="nbr_prix_metre_carre"
                                                                v-model="form.nbr_prix_metre_carre"
                                                                id="nbr_prix_metre_carre"
                                                                label="Prix mètre Carré"
                                                                :readonly="true"
                                                                fontSize="14px"
                                                            />

                                                            <BaseInput
                                                                class="sm:col-span-2"
                                                                type="number"
                                                                name="nbr_valeur_terrain"
                                                                v-model="form.nbr_valeur_terrain"
                                                                id="Valeur_terrain"
                                                                label="Valeur du Terrain"
                                                                :readonly="true"
                                                                step="0.01"
                                                                @blur="arrondirDeuxDecimales(nbr_valeur_terrain)"
                                                                fontSize="14px"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- BATI -->
                                                <div v-show="activeTab === 'bati'" class="space-y-4">
                                                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-8 items-center">
                                                        <div class="sm:col-span-2">
                                                            <div class="h-10 flex ps-8 border border-primary-only rounded dark:border-primary">
                                                                <MazRadio v-model="form.currentCat" 
                                                                    class="block font-medium text-primary-txt"
                                                                    value="Maison individuelle" 
                                                                    label="Maison individuelle" />
                                                            </div>
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <div class="h-10 flex ps-8 border border-primary-only rounded dark:border-primary">
                                                                <MazRadio v-model="form.currentCat" 
                                                                    class="block font-medium text-primary-txt"
                                                                    value="Immeuble collectif" 
                                                                    label="Immeuble collectif" />
                                                            </div>
                                                        </div>
                                                    </div><br>

                                                    <h6 class="text-lg text-primary-txt text-center font-bold">Bâtiment(s) Principal(aux)</h6>

                                                    <div class="w-full flex flex-wrap justify-between gap-4 mt-8">
                                                        <TransitionGroup>
                                                            <div class="sm:col-span-12">
                                                                <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                                                    <BaseInput
                                                                        class="w-36"
                                                                        name="txt_dependant_domainePR"
                                                                        v-model="form.txt_dependant_domainePR"
                                                                        id="Dependant_domaine"
                                                                        label="Bâtiment"
                                                                        :readonly="true"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        v-if="form.currentCat === 'Maison individuelle'"
                                                                        class="w-28"
                                                                        name="slt_categoriePR"
                                                                        v-model="form.slt_categoriePR"
                                                                        id="slt_categoriePR"
                                                                        label="CAT"
                                                                        :options="[
                                                                            {value:'1', label:'1'},
                                                                            {value:'2', label:'2'},
                                                                            {value:'3', label:'3'},
                                                                            {value:'4', label:'4'},
                                                                            {value:'5', label:'5'},
                                                                            {value:'6', label:'6'},
                                                                            {value:'7', label:'7'},
                                                                            {value:'8', label:'8'},
                                                                            {value:'9', label:'9'},
                                                                            {value:'10', label:'10'},
                                                                            {value:'11', label:'11'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        v-else-if="form.currentCat === 'Immeuble collectif'"
                                                                        class="w-28"
                                                                        name="slt_categoriePR"
                                                                        v-model="form.slt_categoriePR"
                                                                        id="slt_categoriePR"
                                                                        label="CAT"
                                                                        :options="[
                                                                            {value:'A', label:'A'},
                                                                            {value:'B', label:'B'},
                                                                            {value:'C', label:'C'},
                                                                            {value:'D', label:'D'},
                                                                            {value:'E', label:'E'},
                                                                            {value:'F', label:'F'},
                                                                            {value:'G', label:'G'},
                                                                            {value:'H', label:'H'},
                                                                            {value:'I', label:'I'},
                                                                            {value:'J', label:'J'},
                                                                            {value:'K', label:'K'},
                                                                            {value:'L', label:'L'},
                                                                            {value:'M', label:'M'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_prix_metre_carrePR"
                                                                        v-model="form.nbr_prix_metre_carrePR"
                                                                        id="Prix_mettre_carre"
                                                                        label="Prix m²"
                                                                        :readonly="true"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_surface_bati_solPR"
                                                                        v-model="form.nbr_surface_bati_solPR"
                                                                        id="Surface_bati_sol"
                                                                        label="Surf B S"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_niveauPR"
                                                                        v-model="form.nbr_niveauPR"
                                                                        id="nbr_niveauPR"
                                                                        label="Niveau"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_surface_utilePR"
                                                                        v-model="form.nbr_surface_utilePR"
                                                                        id="Surface_utile"
                                                                        label="Surf Utile"
                                                                        :readonly="true"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        class="w-28"
                                                                        name="slt_coeffPR"
                                                                        v-model="form.slt_coeffPR"
                                                                        id="slt_coeffPR"
                                                                        label="Coeff"
                                                                        :options="[
                                                                            {value:'0.10', label:'0.1'},
                                                                            {value:'0.20', label:'0.2'},
                                                                            {value:'0.30', label:'0.3'},
                                                                            {value:'0.40', label:'0.4'},
                                                                            {value:'0.50', label:'0.5'},
                                                                            {value:'0.60', label:'0.6'},
                                                                            {value:'0.70', label:'0.7'},
                                                                            {value:'0.80', label:'0.8'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_surface_corrigerPR"
                                                                        v-model="form.nbr_surface_corrigerPR"
                                                                        id="Surface_corriger"
                                                                        label="S.Corrigee"
                                                                        :readonly="true"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_valeurPR"
                                                                        v-model="form.nbr_valeurPR"
                                                                        id="Valeur"
                                                                        label="Valeur"
                                                                        :readonly="true"
                                                                        step="0.01"
                                                                        @blur="() => arrondirDeuxDecimales(nbr_valeurPR)"
                                                                        fontSize="14px"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </TransitionGroup>
                                                    </div>

                                                    <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                                        <TransitionGroup name="slide-fade" tag="div">
                                                            <div 
                                                                v-for="(occupant, i) in form.occupantsBP" 
                                                                :key="occupant.id" 
                                                                class="sm:col-span-8"
                                                            >
                                                                <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                                                                    <BaseSelect
                                                                        class="w-36"
                                                                        name="slt_dependant_domaineTG"
                                                                        v-model="occupant.slt_dependant_domaineTG"
                                                                        id="slt_dependant_domaineTG"
                                                                        label="Bâtiment"
                                                                        :options="[
                                                                            {value:'Secondaire', label:'Secondaire'},
                                                                            {value:'Tertiaire', label:'Tertiaire'},
                                                                            {value:'Dépendance', label:'Dépendance'},
                                                                            {value:'Partie Saillante', label:'Partie Saillante'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        v-if="form.currentCat === 'Maison individuelle'"
                                                                        class="w-28"
                                                                        name="slt_categorieTG"
                                                                        v-model="occupant.slt_categorieTG"
                                                                        id="slt_categorieTG"
                                                                        label="CAT"
                                                                        :options="[
                                                                            {value:'1', label:'1'},
                                                                            {value:'2', label:'2'},
                                                                            {value:'3', label:'3'},
                                                                            {value:'4', label:'4'},
                                                                            {value:'5', label:'5'},
                                                                            {value:'6', label:'6'},
                                                                            {value:'7', label:'7'},
                                                                            {value:'8', label:'8'},
                                                                            {value:'9', label:'9'},
                                                                            {value:'10', label:'10'},
                                                                            {value:'11', label:'11'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        v-else-if="form.currentCat === 'Immeuble collectif'"
                                                                        class="w-28"
                                                                        name="slt_categorieTG"
                                                                        v-model="occupant.slt_categorieTG"
                                                                        id="slt_categorieTG"
                                                                        label="CAT"
                                                                        :options="[
                                                                            {value:'A', label:'A'},
                                                                            {value:'B', label:'B'},
                                                                            {value:'C', label:'C'},
                                                                            {value:'D', label:'D'},
                                                                            {value:'E', label:'E'},
                                                                            {value:'F', label:'F'},
                                                                            {value:'G', label:'G'},
                                                                            {value:'H', label:'H'},
                                                                            {value:'I', label:'I'},
                                                                            {value:'J', label:'J'},
                                                                            {value:'K', label:'K'},
                                                                            {value:'L', label:'L'},
                                                                            {value:'M', label:'M'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_prix_metre_carreTG"
                                                                        v-model="occupant.nbr_prix_metre_carreTG"
                                                                        label="Prix m²"
                                                                        :readonly="true"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_surface_bati_solTG"
                                                                        v-model="occupant.nbr_surface_bati_solTG"
                                                                        label="Surf B S"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_niveauTG"
                                                                        v-model="occupant.nbr_niveauTG"
                                                                        label="Niveau"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_surface_utileTG"
                                                                        v-model="occupant.nbr_surface_utileTG"
                                                                        label="Surf Utile"
                                                                        :readonly="true"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        class="w-28"
                                                                        name="slt_coeffTG"
                                                                        v-model="occupant.slt_coeffTG"
                                                                        label="Coeff"
                                                                        :options="[
                                                                            {value:'0.10', label:'0.1'},
                                                                            {value:'0.20', label:'0.2'},
                                                                            {value:'0.30', label:'0.3'},
                                                                            {value:'0.40', label:'0.4'},
                                                                            {value:'0.50', label:'0.5'},
                                                                            {value:'0.60', label:'0.6'},
                                                                            {value:'0.70', label:'0.7'},
                                                                            {value:'0.80', label:'0.8'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_surface_corrigerTG"
                                                                        v-model="occupant.nbr_surface_corrigerTG"
                                                                        label="S.Corrigee"
                                                                        :readonly="true"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_valeurTG"
                                                                        v-model="occupant.nbr_valeurTG"
                                                                        label="Valeur"
                                                                        :readonly="true"
                                                                        step="0.01"
                                                                        @blur="arrondirOccupantValeur(occupant)"
                                                                        fontSize="14px"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </TransitionGroup>

                                                        <div class="flex mt-6 gap-2">
                                                            <button  
                                                                @click="addBlockBP"
                                                                type="button"
                                                                class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center"
                                                            >
                                                                Ajouter
                                                            </button>
                                                            <button 
                                                                @click="removeBlockBP"
                                                                type="button"
                                                                class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center"
                                                            >
                                                                Retirer
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-center gap-6 mb-8">
                                                        <BaseInput
                                                            class="w-64"
                                                            type="number"
                                                            name="txt_valeur_terrain_bati"
                                                            :value="form.txt_valeur_terrain_bati"
                                                            id="Valeur_terrain_bati"
                                                            label="Valeur(s) bâtiment (s)"
                                                            :readonly="true"
                                                            step="0.01"
                                                            @blur="() => arrondirDeuxDecimales(txt_valeur_terrain_bati)"
                                                            fontSize="14px"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Cours AMENAGEES -->
                                                <div v-show="activeTab === 'Cours_amenagees'" class="space-y-4">
                                                    <div class="w-full flex flex-wrap justify-between gap-4 mt-4"> 
                                                        <TransitionGroup name="slide-fade" tag="div"> 
                                                            <div 
                                                                v-for="(occupant, i) in form.occupantsCA" 
                                                                :key="occupant.id" 
                                                                class="sm:col-span-8"
                                                            > 
                                                                <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                                    <BaseSelect
                                                                        class="w-34"
                                                                        name="slt_cours_amenager_totale"
                                                                        v-model="occupant.slt_cours_amenager_totale"
                                                                        id="slt_cours_amenager_totale"
                                                                        label="Cours Aménagées Totale"
                                                                        :options="[
                                                                            {value:'Cours Amenagées Totale', label:'Cours Amenagées Totale'},
                                                                            {value:'Cours Avant', label:'Cours Avant'},
                                                                            {value:'Cours Arrière', label:'Cours Arrière'},
                                                                            {value:'Cours Latérale Droite', label:'Cours Latérale Droite'},
                                                                            {value:'Cours Latérale Gauche', label:'Cours Latérale Gauche'},
                                                                            {value:'Cours Restante', label:'Cours Restante'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_surface_ca_total"
                                                                        v-model="occupant.nbr_surface_ca_total"
                                                                        id="nbr_surface_ca_total"
                                                                        label="Surface"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        class="w-28"
                                                                        name="slt_categorie_ca_total"
                                                                        v-model="occupant.slt_categorie_ca_total"
                                                                        id="slt_categorie_ca_total"
                                                                        label="Catégorie"
                                                                        :options="[
                                                                            {value:1, label:'1'},
                                                                            {value:2, label:'2'},
                                                                            {value:3, label:'3'},
                                                                            {value:4, label:'4'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_prix_metre_carre_ca_total"
                                                                        v-model="occupant.nbr_prix_metre_carre_ca_total"
                                                                        id="nbr_prix_metre_carre_ca_total"
                                                                        label="P/m²"
                                                                        :readonly="true"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        class="w-28"
                                                                        name="nbr_coefficient_ca_total"
                                                                        v-model="occupant.nbr_coefficient_ca_total"
                                                                        id="nbr_coefficient_ca_total"
                                                                        label="Coefficient"
                                                                        :options="[
                                                                            {value:'0.10', label:'0.1'},
                                                                            {value:'0.20', label:'0.2'},
                                                                            {value:'0.30', label:'0.3'},
                                                                            {value:'0.40', label:'0.4'},
                                                                            {value:'0.50', label:'0.5'},
                                                                            {value:'0.60', label:'0.6'},
                                                                            {value:'0.70', label:'0.7'},
                                                                            {value:'0.80', label:'0.8'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_valeur_ca_total"
                                                                        v-model="occupant.nbr_valeur_ca_total"
                                                                        id="Valeur_ca_total"
                                                                        label="Valeur"
                                                                        :readonly="true"
                                                                        step="0.01"
                                                                        @blur="() => arrondirDeuxDecimales(occupant)"
                                                                        fontSize="14px"
                                                                    />
                                                                </div>
                                                            </div> 
                                                        </TransitionGroup>

                                                        <div class="flex gap-4 mt-6">
                                                            <button  
                                                                @click="addBlockCA"
                                                                type="button"
                                                                class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center"
                                                            >
                                                                Ajouter
                                                            </button>
                                                            <button 
                                                                @click="removeBlockCA"
                                                                type="button"
                                                                class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center"
                                                            >
                                                                Retirer
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-center gap-6 mb-8">
                                                        <BaseInput
                                                            class="w-64"
                                                            type="number"
                                                            name="nbr_valeur_total_ca"
                                                            v-model="form.nbr_valeur_total_ca"
                                                            id="nbr_valeur_total_ca"
                                                            label="Valeur Total des Cours"
                                                            :readonly="true"
                                                            step="0.01"
                                                            @blur="() => arrondirDeuxDecimales(nbr_valeur_total_ca)"
                                                            fontSize="14px"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- CLOTURES -->
                                                <div v-show="activeTab === 'cloture'" class="space-y-4">
                                                    <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                                        <TransitionGroup name="slide-fade" tag="div"> 
                                                            <div 
                                                                v-for="(occupant, i) in form.occupantsCL" 
                                                                :key="occupant.id" 
                                                                class="sm:col-span-8"
                                                            > 
                                                                <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                                    <BaseSelect
                                                                        class="w-34"
                                                                        name="slt_cours_amenager_clo"
                                                                        v-model="occupant.slt_cours_amenager_clo"
                                                                        id="slt_cours_amenager_clo"
                                                                        label="Clôtures"
                                                                        :options="[
                                                                            {value:'Longueur Totale', label:'Longueur Totale'},
                                                                            {value:'Longueur Avant', label:'Longueur Avant'},
                                                                            {value:'Longueur Arrière', label:'Longueur Arrière'},
                                                                            {value:'Longueur Latérale Droite', label:'Longueur Latérale Droite'},
                                                                            {value:'Longueur Latérale Gauche', label:'Longueur Latérale Gauche'},
                                                                            {value:'Longueur Restante', label:'Longueur Restante'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_longueur_avant_clo"
                                                                        v-model="occupant.nbr_longueur_avant_clo"
                                                                        id="nbr_longueur_avant_clo"
                                                                        label="Linéaire"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        class="w-28"
                                                                        name="slt_categorie_clo"
                                                                        v-model="occupant.slt_categorie_clo"
                                                                        id="slt_categorie_clo"
                                                                        label="Catégorie"
                                                                        :options="[
                                                                            {value:1, label:'1'},
                                                                            {value:2, label:'2'},
                                                                            {value:3, label:'3'},
                                                                            {value:4, label:'4'},
                                                                            {value:5, label:'5'},
                                                                            {value:6, label:'6'},
                                                                            {value:7, label:'7'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_prix_metre_carre_clo"
                                                                        v-model="occupant.nbr_prix_metre_carre_clo"
                                                                        id="nbr_prix_metre_carre_clo"
                                                                        label="P/mL"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        class="w-28"
                                                                        name="nbr_coefficient_clo"
                                                                        v-model="occupant.nbr_coefficient_clo"
                                                                        id="nbr_coefficient_clo"
                                                                        label="Coefficient"
                                                                        :options="[
                                                                            {value:'0.10', label:'0.1'},
                                                                            {value:'0.20', label:'0.2'},
                                                                            {value:'0.30', label:'0.3'},
                                                                            {value:'0.40', label:'0.4'},
                                                                            {value:'0.50', label:'0.5'},
                                                                            {value:'0.60', label:'0.6'},
                                                                            {value:'0.70', label:'0.7'},
                                                                            {value:'0.80', label:'0.8'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_valeur_clo"
                                                                        v-model="occupant.nbr_valeur_clo"
                                                                        id="nbr_valeur_clo"
                                                                        label="Valeur"
                                                                        step="0.01"
                                                                        fontSize="14px"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </TransitionGroup>

                                                        <div class="flex gap-4 mt-6">
                                                            <button  
                                                                @click="addBlockCL"
                                                                type="button"
                                                                class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center"
                                                            >
                                                                Ajouter
                                                            </button>
                                                            <button 
                                                                @click="removeBlockCL"
                                                                type="button"
                                                                class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center"
                                                            >
                                                                Retirer
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-center gap-6 mb-8">
                                                        <BaseInput
                                                            class="w-64"
                                                            type="number"
                                                            name="nbr_valeur_total_clotur"
                                                            v-model="form.nbr_valeur_total_clotur"
                                                            id="Valeur_total_clotur"
                                                            label="Valeurs Totale des Clôtures"
                                                            :readonly="true"
                                                            step="0.01"
                                                            @blur="() => arrondirDeuxDecimales(form.nbr_valeur_total_clotur)"
                                                            fontSize="14px"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- AMENAGEMENTS PARTICULIERS -->
                                                <div v-show="activeTab === 'amenagement'" class="space-y-4">
                                                    <h6 class="text-lg text-center text-primary-txt font-bold">VALEUR</h6>
                                        
                                                    <div class="w-full flex flex-wrap justify-between gap-4 mt-4">
                                                        <TransitionGroup name="slide-fade" tag="div"> 
                                                            <div 
                                                                v-for="(occupant, i) in form.occupantsAP" 
                                                                :key="occupant.id" 
                                                                class="sm:col-span-8"
                                                            > 
                                                                <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                                    <BaseInput
                                                                        class="w-34"
                                                                        name="txt_designation_am"
                                                                        v-model="occupant.txt_designation_am"
                                                                        id="Designation_1"
                                                                        label="Designation"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-28"
                                                                        type="number"
                                                                        name="nbr_valeur_unitaire_am"
                                                                        v-model="occupant.nbr_valeur_unitaire_am"
                                                                        id="Valeur_unitaire_1"
                                                                        label="Valeur Unitaire"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-34"
                                                                        type="number"
                                                                        name="nbr_quantile_am"
                                                                        v-model="occupant.nbr_quantile_am"
                                                                        id="Quantile_1"
                                                                        label="Quantité"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseSelect
                                                                        class="w-28"
                                                                        name="slt_coeficien_am"
                                                                        v-model="occupant.slt_coeficien_am"
                                                                        id="Coeficien_1"
                                                                        label="Coeficien"
                                                                        :options="[
                                                                            {value:'0.10', label:'0.1'},
                                                                            {value:'0.20', label:'0.2'},
                                                                            {value:'0.30', label:'0.3'},
                                                                            {value:'0.40', label:'0.4'},
                                                                            {value:'0.50', label:'0.5'},
                                                                            {value:'0.60', label:'0.6'},
                                                                            {value:'0.70', label:'0.7'},
                                                                            {value:'0.80', label:'0.8'},
                                                                            {value:'0.90', label:'0.9'},
                                                                            {value:'1.00', label:'1'},
                                                                        ]"
                                                                        fontSize="14px"
                                                                    />

                                                                    <BaseInput
                                                                        class="w-34"
                                                                        type="number"
                                                                        name="nbr_valeur_am"
                                                                        v-model="occupant.nbr_valeur_am"
                                                                        id="Valeur"
                                                                        label="Valeur"
                                                                        step="0.01"
                                                                        inputmode="decimal"
                                                                        fontSize="14px"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </TransitionGroup>

                                                        <div class="flex gap-4 mt-6">
                                                            <button  
                                                                @click="addBlockAP"
                                                                type="button"
                                                                class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center"
                                                            >
                                                                Ajouter
                                                            </button>
                                                            <button 
                                                                @click="removeBlockAP"
                                                                type="button"
                                                                class="h-8 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                                to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                                focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                                dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                                py-2.5 text-center"
                                                            >
                                                                Retirer
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-center gap-6 mb-8">
                                                        <BaseInput
                                                            class="w-64"
                                                            type="number"
                                                            name="nbr_valeur_totale_ap"
                                                            v-model="form.nbr_valeur_totale_ap"
                                                            id="valeur_totale_ap"
                                                            label="Valeur Totale des A.P"
                                                            :readonly="true"
                                                            step="0.01"
                                                            @blur="() => arrondirDeuxDecimales(form.nbr_valeur_totale_ap)"
                                                            fontSize="14px"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-6">
                                            <div class="border-b">  
                                                <div class="mb-4">
                                                    <div class="flex grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                                                        <BaseInput
                                                            class="w-64"
                                                            type="number"
                                                            name="nbr_valeurVenaleLimeuble"
                                                            v-model="form.nbr_valeurVenaleLimeuble"
                                                            id="nbr_valeurVenaleLimeuble"
                                                            label="Valeur Vénale de l'immeuble"
                                                            :readonly="true"
                                                            step="0.01"
                                                            @blur="() => arrondirDeuxDecimales(form.nbr_valeurVenaleLimeuble)"
                                                            fontSize="14px"
                                                        />

                                                        <BaseInput
                                                            class="w-64"
                                                            type="number"
                                                            name="nbr_valeurLocative"
                                                            v-model="form.nbr_valeurLocative"
                                                            id="nbr_valeurLocative"
                                                            label="Valeur Locative"
                                                            :readonly="true"
                                                            step="0.01"
                                                            @blur="() => arrondirDeuxDecimales(form.nbr_valeurLocative)"
                                                            fontSize="14px"
                                                        />

                                                        <BaseInput
                                                            class="w-64"
                                                            type="text"
                                                            name="dt_dateEvaluation"
                                                            v-model="form.dt_dateEvaluation"
                                                            id="dt_dateEvaluation"
                                                            label="Date d'évaluation"
                                                            fontSize="14px"
                                                        />

                                                        <div class="sm:col-span-2">
                                                            <label class="block font-medium text-primary-txt">Fichier PDF</label>
                                                            <input
                                                                type="file"
                                                                accept="application/pdf"
                                                                @change="handleFileUpload"
                                                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none"
                                                            />
                                                            <p class="mt-1 text-xs text-gray-500">Format: PDF, Taille max: 10 Mo</p>
                                                            
                                                            <!-- Afficher le PDF existant -->
                                                            <div v-if="fichierPDFExistant" class="mt-2">
                                                                <p class="text-sm text-gray-600">
                                                                    📄 Fichier actuel : 
                                                                    <a 
                                                                        :href="'/storage/' + fichierPDFExistant" 
                                                                        target="_blank" 
                                                                        class="text-blue-600 underline hover:text-blue-800"
                                                                    >
                                                                        {{ fichierPDFExistant.split('/').pop() }}
                                                                    </a>
                                                                </p>
                                                                <p class="text-xs text-gray-500 mt-1">
                                                                    💡 Sélectionnez un nouveau fichier pour le remplacer
                                                                </p>
                                                            </div>
                                                            <div v-else class="mt-2">
                                                                <p class="text-sm text-gray-500">⚠️ Aucun fichier PDF associé</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="sm:col-span-6 flex justify-center">
                                            <MazBtn 
                                                type="submit"   
                                                class="w-64 h-10 text-white bg-gradient-to-r from-primary via-primary-dark 
                                                    to-primary hover:bg-gradient-to-br focus:ring-4 focus:outline-none 
                                                    focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 
                                                    dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 
                                                    py-2.5 text-center me-2 mb-2"
                                                size="medium"
                                            >
                                                Enregistrer modification
                                            </MazBtn>
                                        </div>
                                    </div>
                                    </form>
                                </template>
                                <template v-else>
                                    <p class="text-red-500">Aucun terrain à modifier n'a été trouvé.</p>
                                </template>  
                            </div>  
                        </div> 
                    </div> 
            </div>
        </div>
    </div>
  </AuthenticatedLayout>
</template>