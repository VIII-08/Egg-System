<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';


const props = defineProps({
    currentStock: Number,
    recentAdjustments: Array,
});

// A ref to control which form is visible: 'add' or 'remove'
const activeForm = ref(null); // Initially no form is shown

// Two separate forms for clarity
const addForm = useForm({
    adjustment_type: 'addition',
    quantity: null,
    reason: 'New Chick Arrivals',
    notes: '',
});

const removeForm = useForm({
    adjustment_type: 'removal',
    quantity: null,
    reason: 'Culled',
    notes: '',
});

const submitAddition = () => {
    if (!addForm.quantity || addForm.quantity <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please enter a valid quantity to add!',
        });
        return; // stop submission
    }

    addForm.post(route('chicken.stock.store'), {
        onSuccess: () => {
            addForm.reset();
            activeForm.value = null;

            Swal.fire({
                icon: 'success',
                title: 'Added!',
                text: 'Chicken stock successfully added.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (errors) => {
            // Handle backend validation errors
            const errorMessage = errors.quantity 
                ? (Array.isArray(errors.quantity) ? errors.quantity[0] : errors.quantity)
                : 'An error occurred while processing your request.';
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage,
                confirmButtonColor: '#3085d6',
            });
        }
    });
};

const submitRemoval = () => {
    if (!removeForm.quantity || removeForm.quantity <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please enter a valid quantity to remove!',
        });
        return; // stop submission
    }

    // Check if removal quantity exceeds current stock
    if (removeForm.quantity > props.currentStock) {
        Swal.fire({
            icon: 'error',
            title: 'Insufficient Stock',
            text: `You cannot remove ${removeForm.quantity} chickens. Current stock is only ${props.currentStock} chickens.`,
        });
        return; // stop submission
    }

    removeForm.post(route('chicken.stock.store'), {
        onSuccess: () => {
            removeForm.reset();
            activeForm.value = null;

            Swal.fire({
                icon: 'success',
                title: 'Removed!',
                text: 'Chicken stock successfully removed.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (errors) => {
            // Handle backend validation errors
            if (errors.quantity) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Stock',
                    text: Array.isArray(errors.quantity) ? errors.quantity[0] : errors.quantity,
                    confirmButtonColor: '#3085d6',
                });
            } else {
                // Handle other errors
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                    confirmButtonColor: '#3085d6',
                });
            }
        }
    });
};


// Lists for the dropdowns
const additionReasons = ['New Chick Arrivals', 'Purchase', 'Miscount Correction'];
const removalReasons = ['Culled', 'Deceased', 'Sold Live', 'Transferred', 'Miscount Correction'];
</script>

<template>
    <Head title="Adjust Chicken Stock" />

    <StaffLayout>
        <template #header>Adjust Chicken Stock</template>

         <!-- Success Message -->
        <div  v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ $page.props.flash.success }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Panel: The Forms -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold text-gray-800 mb-2">1. Choose Action & Enter Details</h2>
                
                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-4">
                    <button @click="activeForm = 'add'" :class="{'ring-2 ring-offset-2 ring-green-500': activeForm === 'add'}" class="p-4 rounded-lg bg-green-100 text-green-800 hover:bg-green-200 focus:outline-none transition">
                        <span class="font-bold text-lg">+ Add Chickens</span>
                    </button>
                     <button @click="activeForm = 'remove'" :class="{'ring-2 ring-offset-2 ring-red-500': activeForm === 'remove'}" class="p-4 rounded-lg bg-red-100 text-red-800 hover:bg-red-200 focus:outline-none transition">
                        <span class="font-bold text-lg">- Remove Chickens</span>
                    </button>
                </div>

                <!-- Add Chickens Form -->
                <form v-if="activeForm === 'add'" @submit.prevent="submitAddition" class="mt-6 space-y-4">
                    <div>
                        <label for="add_quantity" class="block text-sm font-medium text-gray-700">Quantity to Add</label>
                        <input v-model="addForm.quantity" type="number" id="add_quantity" class="mt-1 block w-full rounded-md border-gray-300" placeholder="e.g., 100">
                    </div>
                    <div>
                        <label for="add_reason" class="block text-sm font-medium text-gray-700">Reason for Addition</label>
                        <select v-model="addForm.reason" id="add_reason" class="mt-1 block w-full rounded-md border-gray-300">
                            <option v-for="reason in additionReasons" :key="reason" :value="reason">{{ reason }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                        <textarea v-model="addForm.notes" id="add_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300" placeholder="e.g., From supplier X, Batch #123"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Additional details about this adjustment. Will be displayed in the history table.</p>
                    </div>
                    <PrimaryButton :disabled="addForm.processing" class="w-full text-lg bg-green-600 hover:bg-green-700">Confirm Addition</PrimaryButton>
                </form>

                 <!-- Remove Chickens Form -->
                <form v-if="activeForm === 'remove'" @submit.prevent="submitRemoval" class="mt-6 space-y-4">
                     <div>
                        <label for="remove_quantity" class="block text-sm font-medium text-gray-700">Quantity to Remove</label>
                        <input v-model="removeForm.quantity" type="number" id="remove_quantity" :max="currentStock" min="1" class="mt-1 block w-full rounded-md border-gray-300" placeholder="e.g., 20">
                         <p v-if="currentStock > 0" class="text-xs text-gray-500 mt-1">Maximum: {{ currentStock }} chickens</p>
                    </div>
                     <div>
                        <label for="remove_reason" class="block text-sm font-medium text-gray-700">Reason for Removal</label>
                         <select v-model="removeForm.reason" id="remove_reason" class="mt-1 block w-full rounded-md border-gray-300">
                            <option v-for="reason in removalReasons" :key="reason" :value="reason">{{ reason }}</option>
                        </select>
                    </div>
                     <div>
                        <label for="remove_notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                        <textarea v-model="removeForm.notes" id="remove_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300" placeholder="e.g., Transferred to Farm Y, Sold to customer ABC"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Additional details about this adjustment. Will be displayed in the history table.</p>
                    </div>
                    <PrimaryButton :disabled="removeForm.processing" class="w-full text-lg bg-red-600 hover:bg-red-700">Confirm Removal</PrimaryButton>
                </form>

            </div>

            <!-- Right Panel: Overview -->
             <div class="bg-white p-6 rounded-lg shadow">
                 <h2 class="text-xl font-bold text-gray-800 mb-4">2. Overview & Recent History</h2>

                 <div class="p-6 rounded-lg bg-indigo-100 text-center">
                     <p class="text-sm font-medium text-indigo-800">Current Live Chicken Stock</p>
                     <p class="text-6xl font-bold text-indigo-700 mt-2">{{ currentStock.toLocaleString() }}</p>
                 </div>

                 <div class="mt-6">
                     <h3 class="font-semibold text-gray-700 mb-2">Recent Adjustments</h3>
                     <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2 text-left text-xs font-semibold text-gray-600">Date</th>
                                <th class="py-2 text-left text-xs font-semibold text-gray-600">Type</th>
                                <th class="py-2 text-right text-xs font-semibold text-gray-600">Quantity</th>
                                <th class="py-2 text-left text-xs font-semibold text-gray-600">Reason</th>
                                <th class="py-2 text-left text-xs font-semibold text-gray-600">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in recentAdjustments" :key="log.id" class="border-b">
                                <td class="py-2 text-sm text-gray-500">{{ new Date(log.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'}) }}</td>
                                <td class="py-2">
                                    <span v-if="log.adjustment_type === 'addition'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Addition</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Removal</span>
                                </td>
                                <td class="py-2 text-sm font-bold text-right" :class="{'text-green-600': log.adjustment_type === 'addition', 'text-red-600': log.adjustment_type === 'removal'}">
                                   {{ log.adjustment_type === 'addition' ? '+' : '-' }}{{ log.quantity }}
                                </td>
                                <td class="py-2 text-sm text-gray-600">{{ log.reason }}</td>
                                <td class="py-2 text-sm text-gray-500 italic">{{ log.notes || '-' }}</td>
                            </tr>
                        </tbody>
                     </table>
                 </div>
            </div>

        </div>
    </StaffLayout>
</template>