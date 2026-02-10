<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    collectibles: Object,
    customers: Array,
    filters: Object,
});

const searchForm = useForm({
    search: props.filters.search || '',
    status: props.filters.status || 'all',
});

// Payment modal state
const showPaymentModal = ref(false);
const selectedCollectible = ref(null);
const paymentForm = useForm({
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
});

const formatCurrency = (value) => `₱${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const submitSearch = () => {
    searchForm.get(route('collectibles.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const openPaymentModal = (collectible) => {
    selectedCollectible.value = collectible;
    showPaymentModal.value = true;
    paymentForm.reset();
    paymentForm.payment_date = new Date().toISOString().slice(0, 10);
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    selectedCollectible.value = null;
};

const submitPayment = () => {
    if (!selectedCollectible.value) return;
    
    const amount = parseFloat(paymentForm.amount || 0);
    const balance = parseFloat(selectedCollectible.value.balance || 0);
    
    if (amount <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Payment amount must be greater than 0.',
        });
        return;
    }
    
    if (amount > balance) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: `Payment amount (${formatCurrency(amount)}) cannot exceed remaining balance (${formatCurrency(balance)}).`,
        });
        return;
    }
    
    paymentForm.post(route('collectibles.payments.store', selectedCollectible.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentModal();
            Swal.fire({
                icon: 'success',
                title: 'Payment Recorded!',
                text: 'The payment has been successfully recorded.',
                timer: 2000,
                showConfirmButton: false,
            });
            router.reload({ only: ['collectibles'] });
        },
        onError: (errors) => {
            if (errors.amount) {
                Swal.fire({
                    icon: 'error',
                    title: 'Payment Failed',
                    text: errors.amount,
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Payment Failed',
                    text: 'An error occurred. Please try again.',
                });
            }
        },
    });
};
</script>

<template>
    <Head title="Collectibles" />

    <StaffLayout>
        <template #header>Collectibles</template>

        <!-- Search and Filter Section -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <form @submit.prevent="submitSearch" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Customer</label>
                        <input
                            v-model="searchForm.search"
                            type="text"
                            placeholder="Enter customer name..."
                            class="w-full rounded-md border-gray-300 shadow-sm"
                            list="customers-list"
                        />
                        <datalist id="customers-list">
                            <option v-for="customer in customers" :key="customer" :value="customer" />
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select v-model="searchForm.status" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="all">All Status</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <PrimaryButton type="submit" class="w-full">Search</PrimaryButton>
                    </div>
                </div>
            </form>
        </div>

        <!-- Collectibles Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bill</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining Balance</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="collectible in collectibles.data" :key="collectible.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ new Date(collectible.sales_transaction?.created_at || collectible.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                {{ collectible.customer_name || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 text-right">
                                {{ formatCurrency(collectible.total_amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-right">
                                {{ formatCurrency(collectible.amount_paid) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-orange-600 text-right">
                                {{ formatCurrency(collectible.balance) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span
                                    class="px-3 py-1 text-xs font-bold rounded-full"
                                    :class="{
                                        'bg-red-100 text-red-800': collectible.status === 'unpaid',
                                        'bg-orange-100 text-orange-800': collectible.status === 'partial',
                                        'bg-green-100 text-green-800': collectible.status === 'paid',
                                    }"
                                >
                                    {{ collectible.status.charAt(0).toUpperCase() + collectible.status.slice(1) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button
                                    v-if="collectible.status !== 'paid'"
                                    @click="openPaymentModal(collectible)"
                                    class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition duration-200"
                                >
                                    Add Payment
                                </button>
                                <span v-else class="text-xs text-gray-400">Paid</span>
                            </td>
                        </tr>
                        <tr v-if="!collectibles.data || collectibles.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No collectibles found for the selected criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="collectibles.links && collectibles.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link
                            v-if="collectibles.prev_page_url"
                            :href="collectibles.prev_page_url"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="collectibles.next_page_url"
                            :href="collectibles.next_page_url"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ collectibles.from }}</span>
                                to
                                <span class="font-medium">{{ collectibles.to }}</span>
                                of
                                <span class="font-medium">{{ collectibles.total }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <Link
                                    v-for="(link, index) in collectibles.links"
                                    :key="index"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        link.active
                                            ? 'z-10 bg-green-50 border-green-500 text-green-600'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                        index === 0 ? 'rounded-l-md' : '',
                                        index === collectibles.links.length - 1 ? 'rounded-r-md' : '',
                                    ]"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div
            v-if="showPaymentModal && selectedCollectible"
            class="fixed inset-0 z-50 flex items-start justify-center bg-black bg-opacity-40 px-4 py-10"
            @click.self="closePaymentModal"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100">
                <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Add Payment</p>
                        <h3 class="text-lg font-bold text-gray-900">{{ selectedCollectible.customer_name || 'Customer' }}</h3>
                        <p class="text-sm text-gray-500">Balance: {{ formatCurrency(selectedCollectible.balance) }}</p>
                    </div>
                    <button type="button" class="text-gray-500 hover:text-gray-700" @click="closePaymentModal">✕</button>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Amount</label>
                        <input
                            v-model="paymentForm.amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            :max="selectedCollectible.balance"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        />
                        <p class="text-xs text-gray-500 mt-1">Remaining: {{ formatCurrency(selectedCollectible.balance) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Payment Date</label>
                        <input v-model="paymentForm.payment_date" type="date" class="mt-1 block w-full rounded-md border-gray-300" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Notes</label>
                        <textarea
                            v-model="paymentForm.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            placeholder="Optional notes"
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-3">
                        <button type="button" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700" @click="closePaymentModal">
                            Cancel
                        </button>
                        <PrimaryButton :disabled="paymentForm.processing" @click="submitPayment">Save Payment</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>

