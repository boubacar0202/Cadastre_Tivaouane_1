<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    services: {
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
    return [...props.services]
        .filter((service) => {
            if (!terme) return true;
            return [service.txt_numeroNote, service.txt_typeNote, service.txt_titreNote]
                .some((valeur) => normalize(valeur).includes(terme));
        })
        .sort((a, b) => String(a.txt_numeroNote).localeCompare(String(b.txt_numeroNote), "fr", { numeric: true }));
});
</script>

<template>
    <Head title="Liste des textes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                    Liste des textes
                </h2>
                <Link
                    :href="route('parametre.service.create')"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white"
                >
                    Nouveau texte
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl px-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="flex flex-wrap items-center justify-between gap-3 p-4 border-b bg-primary sm:rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">
                                Recueil de texte ({{ lignes.length }})
                            </h1>
                            <input
                                v-model="recherche"
                                type="search"
                                placeholder="Rechercher un numéro, un type ou un titre"
                                class="h-9 w-full max-w-md rounded-lg border border-primary-only px-3 text-sm text-primary-txt"
                            />
                        </div>

                        <div class="p-6">
                            <div class="max-h-[640px] overflow-auto rounded-xl border border-primary-only">
                                <table class="table-auto w-full text-sm text-left text-primary-txt">
                                    <thead class="sticky top-0 z-10 text-center uppercase">
                                        <tr class="h-10">
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">#</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Type</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Numéro</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Titre</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">PDF</th>
                                            <th class="px-3 py-2 text-xs text-white border bg-primary">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!lignes.length">
                                            <td colspan="6" class="px-4 py-8 text-center text-primary-txt/70">
                                                Aucun texte enregistré.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(service, index) in lignes"
                                            :key="service.id"
                                            class="h-10 bg-white text-center hover:bg-primary-form"
                                        >
                                            <td class="px-3 py-2 border border-primary-only font-bold">{{ index + 1 }}</td>
                                            <td class="px-3 py-2 border border-primary-only">{{ service.txt_typeNote || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only whitespace-nowrap">{{ service.txt_numeroNote || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only text-left">{{ service.txt_titreNote || "—" }}</td>
                                            <td class="px-3 py-2 border border-primary-only">
                                                <a
                                                    v-if="service.fichierPDF"
                                                    :href="`/storage/${service.fichierPDF}`"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="font-semibold text-primary underline"
                                                >
                                                    PDF
                                                </a>
                                                <span v-else>—</span>
                                            </td>
                                            <td class="px-3 py-2 border border-primary-only whitespace-nowrap">
                                                <Link :href="route('parametre.service.show', service.id)" class="mr-3 font-semibold text-primary underline">
                                                    Voir
                                                </Link>
                                                <Link :href="route('parametre.service.edit', service.id)" class="font-semibold text-primary underline">
                                                    Modifier
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
