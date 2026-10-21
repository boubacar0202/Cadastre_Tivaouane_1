<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"; 
import { Link, Head } from '@inertiajs/vue3'; 
import MazBtn from "maz-ui/components/MazBtn"; 
import { ref } from "vue";
import axios from "axios";
import { toast } from "@/Components/AppToast";  
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import { Inertia } from '@inertiajs/inertia';
import DeleteModal from "@/Components/DeleteModal.vue";
 
defineOptions({ layout: DefaultLayout });

const loadingDelete = ref(false);
const showDeleteUtilisateur = ref(false);
const selectedUtilisateur = ref(null);

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    }
});

const libelleRole = (role) => {
    if (role === "admin") return "Administrateur";
    if (role === "user") return "Utilisateur";
    return role || "-";
};

const supprimerUtilisateur = (user) => {
    if (!user?.id) return;
    selectedUtilisateur.value = user;
    showDeleteUtilisateur.value = true;
};

const confirmerSuppressionUtilisateur = async ({ adminCode } = {}) => {
    if (!selectedUtilisateur.value?.id || loadingDelete.value) return;

    if (!adminCode || String(adminCode).trim() === "") {
        toast.error("Veuillez entrer le code administrateur.");
        return;
    }

    try {
        loadingDelete.value = true;
        const url = route('parametre.admin.destroy', selectedUtilisateur.value.id);
        const { data } = await axios.delete(url, {
            data: { code: String(adminCode).trim() }
        });

        if (data.success) {
            showDeleteUtilisateur.value = false;
            toast.success(data.message);
            Inertia.reload({ preserveState: false });
        } else {
            toast.error(data.message);
        }
    } catch (error) {
        if (error.response?.status === 403) {
            toast.error(error.response.data.message || "Code invalide, veuillez contacter l'administrateur !");
        } else {
            toast.error("Impossible de supprimer l'utilisateur !");
        }
    } finally {
        loadingDelete.value = false;
    }
};

</script>

<template>
    <Head title="Administrateurs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-primary-txt">Liste des administrateurs</h2>
        </template>

        <div class="py-12">
            <div class="flex justify-center">
                <div class="w-full max-w-8xl">
                    <div class="bg-white shadow-md rounded-xl">
                        <DeleteModal
                            :show="showDeleteUtilisateur"
                            :itemName="selectedUtilisateur?.name || ''"
                            :loading="loadingDelete"
                            @update:show="showDeleteUtilisateur = $event"
                            @confirm="confirmerSuppressionUtilisateur"
                        />

                        <div class="p-4 border-b bg-primary flex items-center justify-between rounded-t-xl gap-2">
                            <h2 class="text-xl font-semibold leading-tight text-white">Liste des administrateurs</h2>
                        </div>

                        <div class="p-6">
                            <div class="max-h-[600px] overflow-x-auto shadow-md rounded-xl mt-4">
                                <table class="table table-auto w-full text-base text-left text-primary-txt">
                                    <thead class="sticky top-0 z-10 text-center uppercase whitespace-nowrap">
                                        <tr class="h-10 text-sm text-center font-semibold">
                                            <th class="px-2 py-2 border bg-primary-only font-bold">N°</th>
                                            <th class="px-2 py-2 border bg-primary-only font-bold">Prénom & Nom</th>
                                            <th class="px-2 py-2 border bg-primary-only font-bold">Email</th>
                                            <th class="px-2 py-2 border bg-primary-only font-bold">Rôle</th>
                                            <th class="px-2 py-2 border bg-primary-only font-bold">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(user, index) in props.users"
                                            :key="user?.id ?? index"
                                            class="h-8 bg-white text-sm text-primary-txt text-center hover:bg-primary-form"
                                        >
                                            <td class="sticky left-0 z-0 border bg-white px-6 py-4">{{ index + 1 }}</td>
                                            <td class="px-2 py-1 border whitespace-nowrap">{{ user.name || '-' }}</td>
                                            <td class="px-2 py-1 border whitespace-nowrap">{{ user.email || '-' }}</td>
                                            <td class="px-2 py-1 border whitespace-nowrap bg-primary-only">{{ libelleRole(user.txt_roleUser || user.role) }}</td>
                                            <td class="px-2 py-1 border">
                                                <div class="flex justify-center gap-2">
                                                    <Link :href="route('parametre.admin.edit', user.id)">
                                                        <MazBtn no-shadow no-hover-effect size="large"
                                                            class="h-9 w-8 text-white bg-gradient-to-r from-green-500 via-green-600
                                                            to-green-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                                            ✏️
                                                        </MazBtn>
                                                    </Link>
                                                    <MazBtn size="large" color="danger" @click="() => supprimerUtilisateur(user)" :disabled="loadingDelete"
                                                        class="h-9 w-8 text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                                        🗑
                                                    </MazBtn>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!props.users || props.users.length === 0">
                                            <td class="px-6 py-4 text-center text-primary-txt/60 italic" colspan="5">Aucun administrateur trouvé.</td>
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

