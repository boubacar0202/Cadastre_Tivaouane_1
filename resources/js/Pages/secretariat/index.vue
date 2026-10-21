<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    terrains: {
        type: Array,
        default: () => [],
    },
});

const recherche = ref("");

const normalize = (valeur) =>
    String(valeur ?? "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");

const lignes = computed(() => {
    const terme = normalize(recherche.value);
    return props.terrains.filter((terrain) => {
        if (!terme) return true;
        const dossier = terrain.dossier?.txt_num_dossier;
        const commune = terrain.commune?.slt_commune;
        return [dossier, terrain.txt_nicad, terrain.txt_num_section, terrain.txt_num_parcelle, terrain.txt_lotissement, commune]
            .some((valeur) => normalize(valeur).includes(terme));
    });
});
</script>

<template>
    <Head title="Liste des dossiers" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                Liste des dossiers
            </h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="flex flex-wrap items-center justify-between gap-3 p-4 border-b bg-primary sm:rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">
                                Dossiers cadastraux ({{ lignes.length }})
                            </h1>
                            <input
                                v-model="recherche"
                                type="search"
                                placeholder="Rechercher un dossier, un NICAD, une commune"
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
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">NICAD</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Section</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Parcelle</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Lotissement</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Commune</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Surface</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!lignes.length">
                                            <td colspan="9" class="px-4 py-8 text-center text-primary-txt/70">
                                                Aucun dossier enregistré.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(terrain, index) in lignes"
                                            :key="terrain.id"
                                            class="h-10 bg-white text-center hover:bg-primary-form"
                                        >
                                            <td class="px-3 py-2 border border-primary-only font-bold">{{ index + 1 }}</td>
                                            <td class="px-3 py-2 border border-primary-only whitespace-nowrap">{{ terrain.dossier?.txt_num_dossier || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only whitespace-nowrap">{{ terrain.txt_nicad || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ terrain.txt_num_section || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ terrain.txt_num_parcelle || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ terrain.txt_lotissement || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ terrain.commune?.slt_commune || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ terrain.nbr_surface || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">
                                                <Link
                                                    :href="route('secretariat.edit', terrain.id)"
                                                    class="font-semibold text-primary underline"
                                                >
                                                    Ouvrir
                                                </Link>
                                            </td>
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
