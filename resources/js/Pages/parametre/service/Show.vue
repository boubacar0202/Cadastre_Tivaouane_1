<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

defineOptions({ layout: DefaultLayout });

defineProps({
    service: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="`Texte ${service.txt_numeroNote || ''}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-primary-txt">
                    Fiche du texte
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('parametre.service.index')" class="rounded-lg border border-primary-only px-4 py-2 text-sm font-semibold text-primary-txt">
                        Retour à la liste
                    </Link>
                    <Link :href="route('parametre.service.edit', service.id)" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">
                        Modifier
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-5xl px-2">
                    <div class="bg-white shadow-md rounded-xl">
                        <div class="p-4 border-b bg-primary sm:rounded-t-xl">
                            <h1 class="text-lg text-white font-semibold">
                                {{ service.txt_numeroNote || "Texte" }}
                            </h1>
                        </div>

                        <div class="grid gap-4 p-6 md:grid-cols-3">
                            <div>
                                <p class="text-xs font-bold uppercase text-primary-txt/60">Type</p>
                                <p class="mt-1 font-semibold text-primary-txt">{{ service.txt_typeNote || "—" }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase text-primary-txt/60">Numéro</p>
                                <p class="mt-1 font-semibold text-primary-txt">{{ service.txt_numeroNote || "—" }}</p>
                            </div>
                            <div class="md:col-span-3">
                                <p class="text-xs font-bold uppercase text-primary-txt/60">Titre</p>
                                <p class="mt-1 font-semibold text-primary-txt">{{ service.txt_titreNote || "—" }}</p>
                            </div>
                        </div>

                        <div class="px-6 pb-6">
                            <iframe
                                v-if="service.fichierPDF"
                                :src="`/storage/${service.fichierPDF}`"
                                class="h-[720px] w-full rounded-xl border border-primary-only"
                                title="Document PDF"
                            />
                            <p v-else class="rounded-xl border border-dashed border-primary-only p-8 text-center text-primary-txt/70">
                                Aucun PDF n'est joint à ce texte.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
