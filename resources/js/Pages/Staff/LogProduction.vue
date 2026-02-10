<script setup>
import { computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';


const props = defineProps({
    eggProducts: Array,
    recentLogs: Array,
});

// Initialize form only if eggProducts are available to prevent errors
const form = useForm({
    collection_date: new Date().toISOString().slice(0, 10),
    quantities: (props.eggProducts || []).reduce((acc, product) => {
        // Exclude any product with "damage" in the name from the grid (it has its own separate field)
        const productNameLower = product.name.toLowerCase();
        if (!productNameLower.includes('damage')) {
            acc[product.id] = null;
        }
        return acc;
    }, {}),
    broken_quantity: null,
    notes: '',
});

const totalInBatch = computed(() => {
    const gridTotal = Object.values(form.quantities).reduce((sum, qty) => sum + (Number(qty) || 0), 0);
    const brokenTotal = Number(form.broken_quantity) || 0;
    return gridTotal + brokenTotal;
});

const submit = () => {
    // Check if all quantities are empty
    const quantitiesEmpty = Object.values(form.quantities).every(qty => !qty);
    const brokenEmpty = !form.broken_quantity;

    if (quantitiesEmpty && brokenEmpty) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please enter at least one quantity before logging production!',
        });
        return; // Stop form submission
    }

    form.post(route('production.logs.store'), {
        onSuccess: () => {
            form.reset();
            router.reload({ only: ['recentLogs'] });
            
            Swal.fire({
                icon: 'success',
                title: 'Production Logged!',
                text: 'Egg production has been successfully logged.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (errors) => {
            const errorMessage = errors.collection_date 
                ? (Array.isArray(errors.collection_date) ? errors.collection_date[0] : errors.collection_date)
                : 'An error occurred while logging production. Please try again.';
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage,
                confirmButtonColor: '#3085d6',
            });
        }
    });
};

</script>

<template>
    <Head title="Record Egg Production" />

    <StaffLayout>
        <template #header>Record Egg Production</template>

        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            <span>{{ $page.props.flash.success }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Form Area -->
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="collection_date" class="block text-sm font-medium text-gray-700">Collection Date</label>
                        <input 
                            v-model="form.collection_date" 
                            type="date" 
                            id="collection_date" 
                            :max="new Date().toISOString().slice(0, 10)"
                            :min="new Date().toISOString().slice(0, 10)"
                            readonly
                            class="mt-1 block w-full md:w-1/2 rounded-md border-gray-300 bg-gray-100 cursor-not-allowed"
                        >
                        <p class="mt-1 text-xs text-gray-500">Only today's date can be logged</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Quantity Collected (in pieces)</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-2">
                            <!-- THIS IS THE FIX for the 'product' warning -->
                            <template v-for="p in eggProducts" :key="p.id">
                                <div v-if="!p.name.toLowerCase().includes('damage')">
                                    <label :for="'qty_' + p.id" class="block text-xs text-gray-600">{{ p.name }}</label>
                                    <input v-model="form.quantities[p.id]" type="number" :id="'qty_' + p.id" placeholder="0" class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="border-t pt-6">
                        <label for="broken_quantity" class="block text-lg font-bold text-red-700">Damaged Eggs</label>
                        <input v-model="form.broken_quantity" type="number" id="broken_quantity" placeholder="0" class="mt-2 block w-full md:w-1/2 rounded-md border-gray-300">
                    </div>
                    <div class="p-4 rounded-lg bg-green-100 text-center">
                        <p class="text-sm font-medium text-green-800">Total Eggs in this Batch</p>
                        <p class="text-4xl font-bold text-green-700 mt-2">{{ totalInBatch.toLocaleString() }}</p>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                        <textarea v-model="form.notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                    </div>
                    <div>
                        <PrimaryButton :disabled="form.processing" class="w-full text-lg">Log Production</PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Recent Logs (With Guaranteed Safe Rendering) -->
             <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Recent Production Logs</h2>
                <table class="min-w-full">
                    <tbody>
                        <tr v-for="log in recentLogs" :key="log.id" class="border-b">
                            <td class="py-3 text-sm text-gray-500">{{ new Date(log.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit'}) }}</td>
                            <td class="py-3 text-sm font-semibold text-gray-800">
                                {{ log.quantity }} pcs of <span class="font-bold">{{ log.egg_product.name }}</span>
                            </td>
                            <td class="py-3 text-xs text-gray-500 text-right">
                                by <span class="font-medium">{{ log.user.name }}</span>
                            </td>
                        </tr>
                        <tr v-if="!recentLogs || recentLogs.length === 0">
                            <td colspan="4" class="py-4 text-center text-gray-500">No logs for today yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StaffLayout>
</template>