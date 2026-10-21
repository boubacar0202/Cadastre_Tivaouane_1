<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    dossiers: {
        type: Array,
        default: () => [],
    },
    terrains: {
        type: Array,
        default: () => [],
    },
});

const recherche = ref("");

const terrainsParDossier = computed(() => {
    const index = {};
    props.terrains.forEach((terrain) => {
        const cle = String(terrain.txt_num_dossier ?? "");
        if (!cle) return;
        if (!index[cle]) index[cle] = terrain;
    });
    return index;
});

const normalize = (valeur) =>
    String(valeur ?? "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");

const lignes = computed(() => {
    const terme = normalize(recherche.value);
    return props.dossiers.filter((dossier) => {
        const terrain = terrainsParDossier.value[String(dossier.id)] || {};
        if (!terme) return true;
        return [dossier.txt_num_dossier, dossier.slt_service_rendu, dossier.txt_etat_cession, terrain.txt_nicad, terrain.txt_lotissement]
            .some((valeur) => normalize(valeur).includes(terme));
    });
});

const formatDate = (valeur) => {
    if (!valeur) return "—";
    const date = new Date(valeur);
    if (Number.isNaN(date.getTime())) return valeur;
    return date.toLocaleDateString("fr-FR");
};
</script>

<template>
    <Head title="Suivi géomètre" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                    Suivi géomètre
                </h2>
                <Link
                    :href="route('geometre.create')"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white"
                >
                    Nouvelle enquête
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="flex flex-wrap items-center justify-between gap-3 p-4 border-b bg-primary sm:rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">
                                Dossiers à enquêter ({{ lignes.length }})
                            </h1>
                            <input
                                v-model="recherche"
                                type="search"
                                placeholder="Rechercher un dossier ou un NICAD"
                                class="h-9 w-full max-w-md rounded-lg border border-primary-only px-3 text-sm text-primary-txt"
                            />
                        </div>

                        <div class="p-6">
                            <div class="max-h-[640px] overflow-auto rounded-xl border border-primary-only">
                                <table class="table-auto w-full text-sm text-left text-primary-txt">
                                    <thead class="sticky top-0 z-10 text-center uppercase">
                                        <tr class="h-10">
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">#</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">N° dossier</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">N° d'ordre</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Service rendu</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">État cession</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">NICAD</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Section / parcelle</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!lignes.length">
                                            <td colspan="8" class="px-4 py-8 text-center text-primary-txt/70">
                                                Aucun dossier à afficher.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(dossier, index) in lignes"
                                            :key="dossier.id"
                                            class="h-10 bg-white text-center hover:bg-primary-form"
                                        >
                                            <td class="px-3 py-2 border border-primary-only font-bold">{{ index + 1 }}</td>
                                            <td class="px-3 py-2 border border-primary-only whitespace-nowrap">{{ dossier.txt_num_dossier || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ dossier.txt_num_dordre || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ dossier.slt_service_rendu || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ dossier.txt_etat_cession || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only whitespace-nowrap">
                                                {{ terrainsParDossier[String(dossier.id)]?.txt_nicad || "—" }}
                                            </td>
                                            <td class="px-3 py-2 border border-primary-only whitespace-nowrap">
                                                {{ terrainsParDossier[String(dossier.id)]?.txt_num_section || "—" }}
                                                /
                                                {{ terrainsParDossier[String(dossier.id)]?.txt_num_parcelle || "—" }}
                                            </td>
                                            <td class="px-3 py-2 border border-primary-only whitespace-nowrap">{{ formatDate(dossier.dt_date_creation) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
