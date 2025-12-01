<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    // Common props
    expenses: Object,
    filters: Object,
    userRole: String,

    // Role-specific props (will be null if not applicable)
    productionLogs: Object,
    chickenStockLogs: Object,
    salesTransactions: Object,
});

// Set the default active tab based on the user's role
const activeTab = ref(props.userRole === 'staff-marketing' ? 'sales' : 'production');

// Inertia form for the date filters
const filterForm = useForm({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const submitFilter = () => {
    filterForm.get(route('records.index'), {
        preserveState: true,
        preserveScroll: true,
    });
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
                <button v-if="userRole === 'staff-production'" @click="activeTab = 'production'" :class="[activeTab === 'production' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Egg Production</button>
                <button @click="activeTab = 'expenses'" :class="[activeTab === 'expenses' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Expenses</button>
                <button v-if="userRole === 'staff-production'" @click="activeTab = 'chicken'" :class="[activeTab === 'chicken' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Chicken Stock</button>
            </nav>
        </div>

        <!-- Content Area for Tabs -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <!-- Sales Table -->
            <div v-if="activeTab === 'sales'">
                <table class="min-w-full">
                    <thead><tr class="border-b"><th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th><th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th><th class="py-2 text-right text-sm font-semibold text-gray-600">Total Amount</th></tr></thead>
                    <tbody>
                        <tr v-for="sale in salesTransactions.data" :key="sale.id" class="border-b"><td class="py-3 font-bold text-gray-700">{{ sale.id }}</td><td class="py-3 text-gray-600">{{ new Date(sale.created_at).toLocaleDateString() }}</td><td class="py-3 font-bold text-green-600 text-right">₱{{ parseFloat(sale.total_amount).toFixed(2) }}</td></tr>
                        <tr v-if="salesTransactions.data.length === 0"><td colspan="3" class="py-4 text-center text-gray-500">No sales records found for the selected criteria.</td></tr>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-between items-center"><Link v-if="salesTransactions.prev_page_url" :href="salesTransactions.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link><div v-else></div><Link v-if="salesTransactions.next_page_url" :href="salesTransactions.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link></div>
            </div>

            <!-- Production Logs Table -->
            <div v-if="activeTab === 'production'">
                <table class="min-w-full">
                     <thead><tr class="border-b"><th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th><th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th><th class="py-2 text-left text-sm font-semibold text-gray-600">Product</th><th class="py-2 text-right text-sm font-semibold text-gray-600">Quantity</th></tr></thead>
                    <tbody>
                        <tr v-for="log in productionLogs.data" :key="log.id" class="border-b"><td class="py-3 font-bold text-gray-700">{{ log.id }}</td><td class="py-3 text-gray-600">{{ new Date(log.log_date).toLocaleDateString() }}</td><td class="py-3 font-semibold">{{ log.egg_product.name }}</td><td class="py-3 text-right">{{ log.quantity }} pcs</td></tr>
                        <tr v-if="productionLogs.data.length === 0"><td colspan="4" class="py-4 text-center text-gray-500">No production logs found for the selected criteria.</td></tr>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-between items-center"><Link v-if="productionLogs.prev_page_url" :href="productionLogs.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link><div v-else></div><Link v-if="productionLogs.next_page_url" :href="productionLogs.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link></div>
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

             <!-- Chicken Stock Table -->
             <div v-if="activeTab === 'chicken'">
                 <table class="min-w-full">
                    <thead><tr class="border-b"><th class="py-2 text-left text-sm font-semibold text-gray-600">ID</th><th class="py-2 text-left text-sm font-semibold text-gray-600">Date</th><th class="py-2 text-left text-sm font-semibold text-gray-600">Reason</th><th class="py-2 text-right text-sm font-semibold text-gray-600">Adjustment</th></tr></thead>
                    <tbody>
                        <tr v-for="log in chickenStockLogs.data" :key="log.id" class="border-b"><td class="py-3 font-bold text-gray-700">{{ log.id }}</td><td class="py-3 text-gray-600">{{ new Date(log.created_at).toLocaleDateString() }}</td><td class="py-3 font-semibold">{{ log.reason }}</td><td class="py-3 font-bold text-right" :class="log.adjustment_type === 'addition' ? 'text-green-600' : 'text-red-600'">{{ log.adjustment_type === 'addition' ? '+' : '-' }}{{ log.quantity }}</td></tr>
                        <tr v-if="chickenStockLogs.data.length === 0"><td colspan="4" class="py-4 text-center text-gray-500">No chicken stock adjustments found for the selected criteria.</td></tr>
                    </tbody>
                </table>
                 <div class="mt-4 flex justify-between items-center"><Link v-if="chickenStockLogs.prev_page_url" :href="chickenStockLogs.prev_page_url" class="px-4 py-2 bg-gray-200 rounded">Previous</Link><div v-else></div><Link v-if="chickenStockLogs.next_page_url" :href="chickenStockLogs.next_page_url" class="px-4 py-2 bg-gray-200 rounded">Next</Link></div>
             </div>
        </div>
    </StaffLayout>
</template>