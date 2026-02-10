<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    // Common props
    expenses: Object,
    filters: Object,
    userRole: String,

    // Role-specific props (will be null if not applicable)
    productionLogs: Object,
    chickenStockLogs: Object,
    salesTransactions: Object,
    collectibles: Object,
    currentFeedStock: Number,
    feedUsageLogs: Object,
});

// Set the default active tab based on the user's role
const activeTab = ref(props.userRole === 'staff-marketing' ? 'sales' : 'production');

// Inertia form for the date filters
const filterForm = useForm({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    production_view: props.filters.production_view || 'by_size',
    feed_entry_type: props.filters.feed_entry_type || '',
});

const submitFilter = () => {
    filterForm.get(route('records.index'), {
        preserveState: true,
        preserveScroll: true,
        onError: (errors) => {
            const msg = errors.end_date || errors.start_date;
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date Range',
                text: Array.isArray(msg) ? msg[0] : msg || 'The end date must be on or after the start date.',
            });
        },
    });
};

watch(() => filterForm.errors, (errors) => {
    const msg = errors?.end_date || errors?.start_date;
    if (msg) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Date Range',
            text: Array.isArray(msg) ? msg[0] : msg,
        });
    }
}, { deep: true });

// Payment modal state
const showPaymentModal = ref(false);
const selectedCollectible = ref(null);
const paymentForm = useForm({
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
});

// Payment history modal state
const showPaymentHistoryModal = ref(false);
const selectedCollectibleForHistory = ref(null);

const formatCurrency = (value) => `₱${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

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
    paymentForm.post(route('collectibles.payments.store', selectedCollectible.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentModal();
            // Reload the page to refresh collectibles data
            router.reload({ only: ['collectibles'] });
        },
    });
};

// Batch modal state (for production logs by batch)
const showBatchModal = ref(false);
const selectedBatch = ref(null);

const openBatchModal = (batch) => {
    selectedBatch.value = batch;
    showBatchModal.value = true;
};

const closeBatchModal = () => {
    showBatchModal.value = false;
    selectedBatch.value = null;
};

const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatTime = (value) => {
    if (!value) return '';
    const date = new Date(value);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Feed usage modal state
const showFeedUsageModal = ref(false);
const feedUsageForm = useForm({
    quantity_kg: null,
    notes: '',
});

const openFeedUsageModal = () => {
    showFeedUsageModal.value = true;
    feedUsageForm.reset();
};

const closeFeedUsageModal = () => {
    showFeedUsageModal.value = false;
    feedUsageForm.reset();
};

const submitFeedUsage = () => {
    feedUsageForm.post(route('feed.record-usage'), {
        preserveScroll: true,
        onSuccess: () => {
            closeFeedUsageModal();
            router.reload({ only: ['currentFeedStock', 'feedUsageLogs'] });
        },
        onError: (errors) => {
            const msg = errors.quantity_kg || errors.notes;
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: Array.isArray(msg) ? msg[0] : msg || 'Please check your input.',
            });
        },
    });
};

const openPaymentHistoryModal = (collectible) => {
    selectedCollectibleForHistory.value = collectible;
    showPaymentHistoryModal.value = true;
};

const closePaymentHistoryModal = () => {
    showPaymentHistoryModal.value = false;
    selectedCollectibleForHistory.value = null;
};

const getLastPaymentDate = (collectible) => {
    if (!collectible.payments || collectible.payments.length === 0) {
        return null;
    }
    // Sort payments by date descending and get the most recent
    const sortedPayments = [...collectible.payments].sort((a, b) => {
        return new Date(b.payment_date) - new Date(a.payment_date);
    });
    return sortedPayments[0].payment_date;
};
</script>

<template>
    <Head title="View My Records" />

    <StaffLayout>
        <template #header>View My Records</template>

        <!-- Filter Section -->
        <div class="bg-white p-4 rounded-lg shadow-md mb-6">
            <form @submit.prevent="submitFilter" class="flex flex-col sm:flex-row sm:items-end sm:space-x-4 space-y-2 sm:space-y-0">
                 <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input v-model="filterForm.start_date" type="date" id="start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                 <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input v-model="filterForm.end_date" type="date" id="end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div v-if="userRole === 'staff-production' && activeTab === 'feeds'">
                    <label for="feed_entry_type" class="block text-sm font-medium text-gray-700">Feed Type</label>
                    <select v-model="filterForm.feed_entry_type" id="feed_entry_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">All (Added & Taken)</option>
                        <option value="addition">Added only</option>
                        <option value="deduction">Taken only</option>
                    </select>
                </div>
                <!-- Filter Button -->
                    <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700
                            transition ease-in-out duration-200 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 012 0v2h14V4a1 1 0 112 0v2a2 2 0 01-2 2H5a2 2 0 01-2-2V4zm4 8a1 1 0 011-1h8a1 1 0 110 2H8a1 1 0 01-1-1zm2 5a1 1 0 100 2h6a1 1 0 100-2H9z" />
                        </svg>
                        Filter
                    </button>

                    <!-- Reset Button -->
                    <Link :href="route('records.index')"
                        class="px-5 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg shadow hover:bg-gray-300
                            transition ease-in-out duration-200 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4 4v5h.582l1.84-1.84A5 5 0 1110 15v-2l4 3-4 3v-2A7 7 0 107.757 5.757L8.88 6.88 10 5H5z"
                                clip-rule="evenodd" />
                        </svg>
                        Reset
                    </Link>

            </form>
        </div>

        <!-- Tabs Navigation -->
        <div class="mb-4 border-b border-gray-200">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button v-if="userRole === 'staff-marketing'" @click="activeTab = 'sales'" :class="[activeTab === 'sales' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Sales</button>
                <button v-if="userRole === 'staff-marketing'" @click="activeTab = 'collectibles'" :class="[activeTab === 'collectibles' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Collectibles</button>
                <button v-if="userRole === 'staff-production'" @click="activeTab = 'production'" :class="[activeTab === 'production' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Egg Production</button>
                <button @click="activeTab = 'expenses'" :class="[activeTab === 'expenses' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Expenses</button>
                <button v-if="userRole === 'staff-production'" @click="activeTab = 'feeds'" :class="[activeTab === 'feeds' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Feeds</button>
                <button v-if="userRole === 'staff-production'" @click="activeTab = 'chicken'" :class="[activeTab === 'chicken' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Chicken Stock</button>
            </nav>
        </div>

        <!-- Content Area for Tabs -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <!-- Sales Table -->
            <div v-if="activeTab === 'sales'">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Eggs Sold</th>
                            <th class="py-2 text-right text-sm font-semibold text-gray-600">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in salesTransactions.data" :key="sale.id" class="border-b">
                            <td class="py-3 font-bold text-gray-700">{{ sale.id }}</td>
                            <td class="py-3 text-gray-600">{{ new Date(sale.created_at).toLocaleDateString() }}</td>
                            <td class="py-3 text-gray-700">
                                <div v-if="sale.items && sale.items.length > 0" class="space-y-1">
                                    <div v-for="item in sale.items" :key="item.id" class="text-sm">
                                        <span class="font-semibold">{{ item.product?.name || 'N/A' }}:</span>
                                        <span class="ml-1">{{ item.quantity }} pcs</span>
                                    </div>
                                </div>
                                <span v-else class="text-gray-400 text-sm">No items</span>
                            </td>
                            <td class="py-3 font-bold text-green-600 text-right">₱{{ parseFloat(sale.total_amount).toFixed(2) }}</td>
                        </tr>
                        <tr v-if="salesTransactions.data.length === 0">
                            <td colspan="4" class="py-4 text-center text-gray-500">No sales records found for the selected criteria.</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-between items-center"><Link v-if="salesTransactions.prev_page_url" :href="salesTransactions.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link><div v-else></div><Link v-if="salesTransactions.next_page_url" :href="salesTransactions.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link></div>
            </div>

            <!-- Collectibles Table -->
            <div v-if="activeTab === 'collectibles' && collectibles">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Customer</th>
                            <th class="py-2 text-right text-sm font-semibold text-gray-600">Total Bill</th>
                            <th class="py-2 text-right text-sm font-semibold text-gray-600">Amount Paid</th>
                            <th class="py-2 text-right text-sm font-semibold text-gray-600">Remaining Balance</th>
                            <th class="py-2 text-center text-sm font-semibold text-gray-600">Status</th>
                            <th class="py-2 text-center text-sm font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="collectible in collectibles.data" :key="collectible.id" class="border-b">
                            <td class="py-3 font-bold text-gray-700">{{ collectible.id }}</td>
                            <td class="py-3 text-gray-600">{{ new Date(collectible.sales_transaction?.created_at || collectible.created_at).toLocaleDateString() }}</td>
                            <td class="py-3 font-semibold text-gray-700">{{ collectible.customer_name || 'N/A' }}</td>
                            <td class="py-3 font-bold text-gray-800 text-right">₱{{ parseFloat(collectible.total_amount).toFixed(2) }}</td>
                            <td class="py-3 text-green-600 text-right">₱{{ parseFloat(collectible.amount_paid).toFixed(2) }}</td>
                            <td class="py-3 font-bold text-orange-600 text-right">₱{{ parseFloat(collectible.balance).toFixed(2) }}</td>
                            <td class="py-3 text-center">
                                <span class="px-3 py-1 text-xs font-bold rounded-full" 
                                    :class="{
                                        'bg-red-100 text-red-800': collectible.status === 'unpaid',
                                        'bg-orange-100 text-orange-800': collectible.status === 'partial',
                                        'bg-green-100 text-green-800': collectible.status === 'paid'
                                    }">
                                    {{ collectible.status.charAt(0).toUpperCase() + collectible.status.slice(1) }}
                                </span>
                            </td>
                            <td class="py-3 text-center">
                                <button
                                    type="button"
                                    class="px-3 py-1.5 text-sm font-semibold text-blue-700 bg-blue-50 rounded-full border border-blue-100 hover:bg-blue-100 transition"
                                    @click="openPaymentHistoryModal(collectible)"
                                    :disabled="!collectible.payments || collectible.payments.length === 0"
                                >
                                    View Payments
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!collectibles.data || collectibles.data.length === 0">
                            <td colspan="8" class="py-4 text-center text-gray-500">No collectibles found for the selected criteria.</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="collectibles" class="mt-4 flex justify-between items-center">
                    <Link v-if="collectibles.prev_page_url" :href="collectibles.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link>
                    <div v-else></div>
                    <Link v-if="collectibles.next_page_url" :href="collectibles.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link>
                </div>
            </div>

            <!-- Production Logs Table -->
            <div v-if="activeTab === 'production'">
                <!-- View Toggle -->
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-gray-700">View Production By:</label>
                        <select 
                            v-model="filterForm.production_view" 
                            @change="submitFilter"
                            class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                        >
                            <option value="by_size">Egg Size Entries</option>
                            <option value="by_batch">Batch (with egg sizes)</option>
                        </select>
                    </div>
                </div>

                <!-- By Batch View -->
                <template v-if="filterForm.production_view === 'by_batch'">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th>
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">Batch ID</th>
                                <th class="py-2 text-right text-sm font-semibold text-gray-600">Total Quantity</th>
                                <th class="py-2 text-center text-sm font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="batch in productionLogs" :key="batch.batch_reference" class="border-b hover:bg-gray-50">
                                <td class="py-3 text-gray-600">{{ new Date(batch.log_date || batch.created_at).toLocaleDateString() }}</td>
                                <td class="py-3 font-bold text-gray-700">PROD-{{ batch.batch_reference.substring(0, 8) }}</td>
                                <td class="py-3 font-bold text-gray-800 text-right">{{ (batch.total_quantity ?? 0).toLocaleString() }} pcs</td>
                                <td class="py-3 text-center">
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 text-sm font-semibold text-emerald-700 bg-emerald-50 rounded-full border border-emerald-100 hover:bg-emerald-100 transition"
                                        @click="openBatchModal(batch)"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!productionLogs || productionLogs.length === 0">
                                <td colspan="4" class="py-4 text-center text-gray-500">No production batches found for the selected criteria.</td>
                            </tr>
                        </tbody>
                    </table>
                </template>

                <!-- By Size View (Default) -->
                <template v-else>
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th>
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th>
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">Product</th>
                                <th class="py-2 text-right text-sm font-semibold text-gray-600">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in productionLogs.data" :key="log.id" class="border-b">
                                <td class="py-3 font-bold text-gray-700">{{ log.id }}</td>
                                <td class="py-3 text-gray-600">{{ new Date(log.log_date).toLocaleDateString() }}</td>
                                <td class="py-3 font-semibold">{{ log.egg_product.name }}</td>
                                <td class="py-3 text-right">{{ log.quantity }} pcs</td>
                            </tr>
                            <tr v-if="productionLogs.data.length === 0">
                                <td colspan="4" class="py-4 text-center text-gray-500">No production logs found for the selected criteria.</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 flex justify-between items-center">
                        <Link v-if="productionLogs.prev_page_url" :href="productionLogs.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link>
                        <div v-else></div>
                        <Link v-if="productionLogs.next_page_url" :href="productionLogs.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link>
                    </div>
                </template>
            </div>

            <!-- Expenses Table -->
            <div v-if="activeTab === 'expenses'">
                 <table class="min-w-full">
                     <thead>
                         <tr class="border-b">
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Category</th>
                            <th class="py-2 text-left text-sm font-semibold text-gray-600">Receipt #</th>
                            <th class="py-2 text-right text-sm font-semibold text-gray-600">Amount</th>
                            <th class="py-2 text-center text-sm font-semibold text-gray-600">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="expense in expenses.data" :key="expense.id" class="border-b">
                            <td class="py-3 font-bold text-gray-700">{{ expense.id }}</td>
                            <td class="py-3 text-gray-600">{{ new Date(expense.expense_date).toLocaleDateString() }}</td>
                            <td class="py-3 font-semibold">{{ expense.category }}</td>
                            <td class="py-3 text-gray-700">{{ expense.description || 'N/A' }}</td>
                            <td class="py-3 font-bold text-red-500 text-right">₱{{ parseFloat(expense.amount).toFixed(2) }}</td>
                            
                            <td class="py-2 flex justify-center">
                                <!-- Check if the receipt_image_url exists -->
                                <div v-if="expense.receipt_image_url">
                                    <a :href="expense.receipt_image_url" target="_blank" title="View full image">
                                        <img :src="expense.receipt_image_url" alt="Receipt" class="h-12 w-12 object-cover rounded-md cursor-pointer transition transform hover:scale-110">
                                    </a>
                                </div>
                                <div v-else>
                                    <span class="text-xs text-gray-400">No Image</span>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="expenses.data.length === 0">
                            <td colspan="6" class="py-4 text-center text-gray-500">No expenses found for the selected criteria.</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-between items-center"><Link v-if="expenses.prev_page_url" :href="expenses.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link><div v-else></div><Link v-if="expenses.next_page_url" :href="expenses.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link></div>
            </div>

            <!-- Feeds Section -->
            <div v-if="activeTab === 'feeds' && userRole === 'staff-production'">
                <!-- Current Feed Stock Display -->
                <div class="mb-6 p-6 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-1">Current Feed Stock</h3>
                            <p class="text-3xl font-bold text-green-700">{{ (currentFeedStock || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} <span class="text-xl text-gray-600">kg</span></p>
                        </div>
                        <button
                            type="button"
                            @click="openFeedUsageModal"
                            class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition shadow-md"
                        >
                            Record Feed Usage
                        </button>
                    </div>
                </div>

                <!-- Feed Usage History -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Feed Usage History</h3>
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th>
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">Date & Time</th>
                                <th class="py-2 text-right text-sm font-semibold text-gray-600">Quantity (kg)</th>
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">Recorded By</th>
                                <th class="py-2 text-left text-sm font-semibold text-gray-600">Receipt Number</th>
                                <th class="py-2 text-center text-sm font-semibold text-gray-600">Receipt Pic</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in feedUsageLogs?.data || []" :key="log.id" class="border-b hover:bg-gray-50">
                                <td class="py-3 font-bold text-gray-700">{{ log.reference }}</td>
                                <td class="py-3 text-gray-600">
                                    <div>{{ new Date(log.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) }}</div>
                                    <div class="text-xs text-gray-500">{{ new Date(log.date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</div>
                                </td>
                                <td class="py-3 font-bold text-right" :class="log.entry_type === 'addition' ? 'text-green-600' : 'text-red-600'">
                                    {{ log.entry_type === 'addition' ? '+' : '−' }}{{ parseFloat(log.quantity_kg || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} kg
                                </td>
                                <td class="py-3 text-gray-700">{{ log.recorded_by || 'N/A' }}</td>
                                <td class="py-3 text-sm text-gray-700">{{ log.receipt_number || '—' }}</td>
                                <td class="py-3 text-center">
                                    <a v-if="log.receipt_image_url" :href="log.receipt_image_url" target="_blank" title="View receipt" class="inline-block">
                                        <img :src="log.receipt_image_url" alt="Receipt" class="h-12 w-12 object-cover rounded-md cursor-pointer transition transform hover:scale-110">
                                    </a>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                            </tr>
                            <tr v-if="!feedUsageLogs?.data || feedUsageLogs.data.length === 0">
                                <td colspan="6" class="py-4 text-center text-gray-500">No feed usage records found for the selected criteria.</td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="feedUsageLogs" class="mt-4 flex justify-between items-center">
                        <Link v-if="feedUsageLogs.prev_page_url" :href="feedUsageLogs.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link>
                        <div v-else></div>
                        <Link v-if="feedUsageLogs.next_page_url" :href="feedUsageLogs.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link>
                    </div>
                </div>
            </div>

             <!-- Chicken Stock Table -->
             <div v-if="activeTab === 'chicken'">
                 <table class="min-w-full">
                    <thead><tr class="border-b"><th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th><th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th><th class="py-2 text-left text-sm font-semibold text-gray-600">Reason</th><th class="py-2 text-right text-sm font-semibold text-gray-600">Adjustment</th><th class="py-2 text-left text-sm font-semibold text-gray-600">Notes</th></tr></thead>
                    <tbody>
                        <tr v-for="log in chickenStockLogs.data" :key="log.id" class="border-b">
                            <td class="py-3 font-bold text-gray-700">{{ log.id }}</td>
                            <td class="py-3 text-gray-600">{{ new Date(log.created_at).toLocaleDateString() }}</td>
                            <td class="py-3 font-semibold">{{ log.reason }}</td>
                            <td class="py-3 font-bold text-right" :class="log.adjustment_type === 'addition' ? 'text-green-600' : 'text-red-600'">{{ log.adjustment_type === 'addition' ? '+' : '-' }}{{ log.quantity }}</td>
                            <td class="py-3 text-sm text-gray-500 italic">{{ log.notes || '-' }}</td>
                        </tr>
                        <tr v-if="chickenStockLogs.data.length === 0"><td colspan="5" class="py-4 text-center text-gray-500">No chicken stock adjustments found for the selected criteria.</td></tr>
                    </tbody>
                </table>
                 <div class="mt-4 flex justify-between items-center"><Link v-if="chickenStockLogs.prev_page_url" :href="chickenStockLogs.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link><div v-else></div><Link v-if="chickenStockLogs.next_page_url" :href="chickenStockLogs.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link></div>
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
                        <input v-model="paymentForm.amount" type="number" min="0.01" step="0.01" :max="selectedCollectible.balance" class="mt-1 block w-full rounded-md border-gray-300" />
                        <p class="text-xs text-gray-500 mt-1">Remaining: {{ formatCurrency(selectedCollectible.balance) }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Payment Date</label>
                        <input v-model="paymentForm.payment_date" type="date" class="mt-1 block w-full rounded-md border-gray-300" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700">Notes</label>
                        <textarea v-model="paymentForm.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300" placeholder="Optional notes"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-3">
                        <button type="button" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700" @click="closePaymentModal">Cancel</button>
                        <PrimaryButton :disabled="paymentForm.processing" @click="submitPayment">Save Payment</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History Modal -->
        <div
            v-if="showPaymentHistoryModal && selectedCollectibleForHistory"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black bg-opacity-40 px-4 py-10"
            @click.self="closePaymentHistoryModal"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden border border-gray-100">
                <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-blue-700 font-semibold">Payment History</p>
                        <h3 class="text-xl font-bold text-gray-900">{{ selectedCollectibleForHistory.customer_name || 'Customer' }}</h3>
                        <p class="text-sm text-gray-500">
                            Total Bill: {{ formatCurrency(selectedCollectibleForHistory.total_amount) }} | 
                            Paid: {{ formatCurrency(selectedCollectibleForHistory.amount_paid) }} | 
                            Balance: {{ formatCurrency(selectedCollectibleForHistory.balance) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none"
                        @click="closePaymentHistoryModal"
                    >
                        ✕
                    </button>
                </div>
                <div class="px-6 py-4">
                    <!-- Last Payment Info -->
                    <div v-if="getLastPaymentDate(selectedCollectibleForHistory)" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-green-800">Last Payment Date</p>
                                <p class="text-lg font-bold text-green-900 mt-1">
                                    {{ new Date(getLastPaymentDate(selectedCollectibleForHistory)).toLocaleDateString('en-US', { 
                                        year: 'numeric', 
                                        month: 'long', 
                                        day: 'numeric' 
                                    }) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-green-700">Total Payments</p>
                                <p class="text-2xl font-bold text-green-900">
                                    {{ (selectedCollectibleForHistory.payments || []).length }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <p class="text-sm text-gray-600">No payments have been recorded yet.</p>
                    </div>

                    <!-- Payment History Table -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">All Payments</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-3 text-left text-xs font-semibold text-gray-700">Payment Date</th>
                                        <th class="p-3 text-right text-xs font-semibold text-gray-700">Amount</th>
                                        <th class="p-3 text-left text-xs font-semibold text-gray-700">Payment Method</th>
                                        <th class="p-3 text-left text-xs font-semibold text-gray-700">Recorded By</th>
                                        <th class="p-3 text-left text-xs font-semibold text-gray-700">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!selectedCollectibleForHistory.payments || selectedCollectibleForHistory.payments.length === 0">
                                        <td colspan="5" class="p-4 text-center text-gray-500">No payments recorded yet.</td>
                                    </tr>
                                    <tr 
                                        v-for="payment in (selectedCollectibleForHistory.payments || []).sort((a, b) => new Date(b.payment_date) - new Date(a.payment_date))" 
                                        :key="payment.id" 
                                        class="border-b hover:bg-gray-50"
                                    >
                                        <td class="p-3">
                                            <div class="font-medium text-gray-900">
                                                {{ new Date(payment.payment_date).toLocaleDateString('en-US', { 
                                                    year: 'numeric', 
                                                    month: 'short', 
                                                    day: 'numeric' 
                                                }) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ new Date(payment.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                            </div>
                                        </td>
                                        <td class="p-3 text-right font-bold text-green-700">{{ formatCurrency(payment.amount || 0) }}</td>
                                        <td class="p-3 text-gray-700">{{ payment.payment_method || 'Cash' }}</td>
                                        <td class="p-3 text-gray-700">{{ payment.recorded_by?.name || 'N/A' }}</td>
                                        <td class="p-3 text-gray-600 text-sm">{{ payment.notes || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Batch Details Modal -->
        <div
            v-if="showBatchModal && selectedBatch"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black bg-opacity-40 px-4 py-10"
            @click.self="closeBatchModal"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-100">
                <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Batch Details</p>
                        <h3 class="text-xl font-bold text-gray-900">PROD-{{ selectedBatch.batch_reference?.substring(0, 8) }}</h3>
                        <p class="text-sm text-gray-500">{{ formatDate(selectedBatch.log_date || selectedBatch.created_at) }} · {{ formatTime(selectedBatch.created_at) }}</p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none"
                        @click="closeBatchModal"
                    >
                        ✕
                    </button>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm text-gray-600">{{ formatDate(selectedBatch.log_date || selectedBatch.created_at) }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Logged by {{ selectedBatch.logged_by ?? 'Me' }}</p>
                        </div>
                        <p class="text-2xl font-extrabold text-emerald-700">{{ (selectedBatch.total_quantity ?? 0).toLocaleString() }} pcs</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="item in selectedBatch.items"
                            :key="item.egg_size + item.quantity"
                            class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-3"
                        >
                            <div class="text-sm font-semibold text-gray-800">{{ item.egg_size }}</div>
                            <div class="text-base font-bold text-gray-900">{{ item.quantity?.toLocaleString() }} pcs</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feed Usage Modal -->
        <div
            v-if="showFeedUsageModal"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black bg-opacity-40 px-4 py-10"
            @click.self="closeFeedUsageModal"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100">
                <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-green-700 font-semibold">Record Feed Usage</p>
                        <h3 class="text-lg font-bold text-gray-900">Take Feed from Inventory</h3>
                        <p class="text-sm text-gray-500">Current Stock: {{ (currentFeedStock || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} kg</p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none"
                        @click="closeFeedUsageModal"
                    >
                        ✕
                    </button>
                </div>
                <form @submit.prevent="submitFeedUsage" class="px-6 py-4 space-y-4">
                    <div>
                        <label for="quantity_kg" class="block text-sm font-medium text-gray-700 mb-1">Quantity (kg)</label>
                        <div class="relative">
                            <input
                                v-model="feedUsageForm.quantity_kg"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="currentFeedStock || 0"
                                id="quantity_kg"
                                class="block w-full rounded-md border-gray-300 pr-12 shadow-sm focus:border-green-500 focus:ring-green-500"
                                placeholder="0.00"
                                required
                            />
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 text-sm">kg</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Maximum available: {{ (currentFeedStock || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} kg</p>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea
                            v-model="feedUsageForm.notes"
                            id="notes"
                            rows="3"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            placeholder="e.g., Fed to Layer 1, Morning feeding..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button
                            type="button"
                            @click="closeFeedUsageModal"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="feedUsageForm.processing"
                            class="px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition disabled:opacity-50"
                        >
                            Record Usage
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </StaffLayout>
</template>