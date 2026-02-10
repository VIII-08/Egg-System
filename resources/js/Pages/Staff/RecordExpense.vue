<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';


const props = defineProps({
    recentExpenses: Array,
    expenseCategories: Array,
});

// Create the Inertia form object
const form = useForm({
    expense_date: new Date().toISOString().slice(0, 10),
    category: null,
    feed_quantity_kg: null,
    amount: null,
    description: '',
    receipt_image: null, // This will hold the file object
});

// Computed property to check if Feeds category is selected
const isFeedsCategory = computed(() => form.category === 'Feeds');

// Watch for category changes and reset feed_quantity_kg if not Feeds
watch(() => form.category, (newCategory) => {
    if (newCategory !== 'Feeds') {
        form.feed_quantity_kg = null;
    }
});

const receiptPreview = ref(null);

// Function to handle the file selection
function handleFileChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    form.receipt_image = file;
    
    // Create a URL for image preview
    const reader = new FileReader();
    reader.onload = (e) => {
        receiptPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
}

const submit = () => {
    // Validate required fields
    if (!form.category) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Category',
            text: 'Please select an expense category.',
        });
        return;
    }

    if (!form.amount || form.amount <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Please enter a valid amount greater than 0.',
        });
        return;
    }

    if (!form.description) {
        Swal.fire({
            icon: 'error',
            title: 'Receipt Number Required',
            text: 'Please enter the receipt number.',
        });
        return;
    }

    if (!form.receipt_image) {
        Swal.fire({
            icon: 'error',
            title: 'Receipt Image Required',
            text: 'Please upload a receipt image.',
        });
        return;
    }

    form.post(route('expenses.store'), {
        onError: (errors) => {
            const msg = errors.expense_date || errors.category || errors.feed_quantity_kg || errors.amount || errors.receipt_image;
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: Array.isArray(msg) ? msg[0] : msg || 'Please check your input.',
            });
        },
        onSuccess: () => {
            form.reset();
            receiptPreview.value = null;

            const feedAddedKg = usePage().props.flash?.feed_added_kg;
            const successText = feedAddedKg
                ? `Your expense has been logged. ${parseFloat(feedAddedKg).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg of feed has been added to inventory.`
                : 'Your expense has been successfully logged.';

            Swal.fire({
                icon: 'success',
                title: feedAddedKg ? 'Expense & Feed Recorded!' : 'Expense Recorded!',
                text: successText,
                timer: feedAddedKg ? 3500 : 2000,
                showConfirmButton: false,
            });
        }
    });
};


</script>

<template>
    <Head title="Record an Expense" />

    <StaffLayout>
        <template #header>Record an Expense</template>
        
        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ $page.props.flash.success }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Main Form (3/5 width) -->
            <div class="lg:col-span-3 bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold text-gray-800 mb-2">New Expense Entry</h2>
                <p class="text-gray-500 mb-6">Fill in the details and attach a photo of the receipt.</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="expense_date" class="block text-sm font-medium text-gray-700">Date of Expense</label>
                        <input v-model="form.expense_date" type="date" id="expense_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Expense Category</label>
                        <select v-model="form.category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option :value="null" disabled>Select a category...</option>
                            <option v-for="cat in expenseCategories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>

                    <div v-if="isFeedsCategory">
                        <label for="feed_quantity_kg" class="block text-sm font-medium text-gray-700">Feed Quantity (kg)</label>
                        <div class="relative mt-1">
                            <input 
                                v-model="form.feed_quantity_kg" 
                                type="number" 
                                step="0.01" 
                                id="feed_quantity_kg" 
                                class="block w-full rounded-md border-gray-300 pr-12 shadow-sm focus:border-green-500 focus:ring-green-500" 
                                placeholder="0.00"
                                min="0"
                            >
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 text-sm">kg</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                         <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500">₱</span>
                            </div>
                            <input v-model="form.amount" type="number" step="0.01" id="amount" class="block w-full rounded-md border-gray-300 pl-7 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="0.00">
                         </div>
                    </div>

                    <div>
                         <label for="description" class="block text-sm font-medium text-gray-700">Receipt #</label>
                         <input v-model="form.description" type="text" id="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="e.g., B-Meg Integra 3000, OR#12345">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Attach Receipt Image</label>
                        <div class="mt-1 flex justify-center rounded-md border-2 border-dashed border-gray-300 px-6 pt-5 pb-6">
                            <div class="space-y-1 text-center">
                                <img v-if="receiptPreview" :src="receiptPreview" class="mx-auto h-24 w-auto object-contain">
                                <svg v-else class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="file-upload" class="relative cursor-pointer rounded-md bg-white font-medium text-green-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-green-500 focus-within:ring-offset-2 hover:text-green-500">
                                        <span>Click to browse</span>
                                        <input @change="handleFileChange" id="file-upload" name="file-upload" type="file" class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag & drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                            </div>
                        </div>
                    </div>

                     <div class="mt-6">
                         <PrimaryButton :disabled="form.processing" class="w-full text-lg py-3">Record Expense</PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Recent Expenses (2/5 width) -->
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                 <h2 class="text-xl font-bold text-gray-800 mb-2">My Recently Logged Expenses</h2>
                  <table class="min-w-full mt-6">
                    <thead class="border-b">
                        <tr>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Category</th>
                            <th class="py-2 text-center text-sm font-semibold text-gray-600">Receipt</th>
                            <th class="py-2 text-right text-sm font-semibold text-gray-600">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="expense in recentExpenses" :key="expense.id" class="border-b">
                            <td class="py-3 text-sm text-gray-500">{{ new Date(expense.created_at).toLocaleString('en-US', {month: 'short', day: 'numeric', hour:'numeric', minute: '2-digit'}) }}</td>
                            <td class="py-3 text-sm font-semibold text-gray-800">{{ expense.category }}</td>
                            <td class="py-3 text-center">
                                <span v-if="expense.receipt_image_path" class="text-green-500">✔</span>
                                <span v-else class="text-red-500">✖</span>
                            </td>
                            <td class="py-3 font-bold text-red-500 text-right">₱{{ parseFloat(expense.amount).toFixed(2) }}</td>
                        </tr>
                        <tr v-if="recentExpenses.length === 0">
                            <td colspan="4" class="py-4 text-center text-gray-500">No expenses recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StaffLayout>
</template>