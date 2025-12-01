<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    records: [Object, Array],
    filters: Object,
    users: Array,
});

const filterForm = useForm({
    type: props.filters.type || 'sales_transactions',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || '',
    entered_by: props.filters.entered_by || null,
});

// This computed property safely determines if filters should be shown.
const showTransactionalFilters = computed(() => {
    const transactionalTypes = ['sales_transactions', 'expenses', 'production_logs'];
    return transactionalTypes.includes(filterForm.type);
});

const submitFilter = () => {
    filterForm.get(route('admin.records.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatCurrency = (value) => `₱${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
</script>

<template>
    <Head title="View Specific Records" />
    <AdminLayout>
        <template #header>View Records</template>

        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <form @submit.prevent="submitFilter" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="font-medium">View Records For:</label>
                        <select v-model="filterForm.type" @change="submitFilter" class="mt-1 block w-full rounded-md text-lg"><optgroup label="Transaction Logs"><option value="sales_transactions">Sales Transactions</option><option value="expenses">Expenses</option><option value="production_logs">Production Logs</option></optgroup><optgroup label="Summaries & Reports"><option value="financial_summaries">Financial Summaries</option><option value="current_egg_inventory">Current Egg Inventory</option><option value="current_chicken_stock">Current Chicken Stock</option></optgroup></select>
                    </div>
                    <template v-if="showTransactionalFilters">
                         <div><label>From Date</label><input v-model="filterForm.from_date" type="date" class="mt-1 block w-full rounded-md"></div>
                         <div><label>To Date</label><input v-model="filterForm.to_date" type="date" class="mt-1 block w-full rounded-md"></div>
                         <div><label>Entered By</label><select v-model="filterForm.entered_by" class="mt-1 block w-full rounded-md"><option :value="null">All Users</option><option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option></select></div>
                    </template>
                </div>
                 <div class="mt-4" v-if="showTransactionalFilters"><PrimaryButton type="submit">Search</PrimaryButton></div>
            </form>
        </div>
        
        <div class="bg-white rounded-lg shadow"><div class="overflow-x-auto"><table class="w-full text-lg">
            <thead class="bg-gray-50 text-left text-gray-600 font-semibold">
                <tr v-if="filterForm.type === 'sales_transactions'"><th class="p-4">Date</th><th>ID</th><th>Customer</th><th>Amount</th><th>Staff</th></tr>
                <tr v-if="filterForm.type === 'expenses'"><th class="p-4">Date</th><th>ID</th><th>Category</th><th>Receipt #</th><th>Amount</th><th>Staff</th><th class="text-center">Receipt</th></tr>
                <tr v-if="filterForm.type === 'production_logs'"><th class="p-4">Date</th><th>Batch ID</th><th>Egg Size</th><th>Quantity Logged</th><th>Staff</th></tr>
                <tr v-if="filterForm.type === 'financial_summaries'"><th class="p-4">Period</th><th>Submitted By</th><th>Status</th><th>Net Income</th><th class="p-4">Actions</th></tr>
                <tr v-if="filterForm.type === 'current_egg_inventory'"><th class="p-4">Egg Size</th><th>Current Stock (pcs)</th><th>Value (Est.)</th></tr>
                <tr v-if="filterForm.type === 'current_chicken_stock'"><th class="p-4">Stock Type</th><th>Quantity</th><th>Last Updated</th></tr>
            </thead>
            <tbody class="divide-y">
                <template v-if="records && records.data"> <!-- This block is for paginated results -->
                    <tr v-for="record in records.data" :key="record.id" class="hover:bg-gray-50">
                        <td class="p-4" v-if="filterForm.type === 'sales_transactions'">{{ new Date(record.created_at).toLocaleDateString() }}</td><td v-if="filterForm.type === 'sales_transactions'">SALE-{{ record.id }}</td><td v-if="filterForm.type === 'sales_transactions'">{{ record.customer_name || 'N/A' }}</td><td v-if="filterForm.type === 'sales_transactions'">{{ formatCurrency(record.total_amount) }}</td><td v-if="filterForm.type === 'sales_transactions'">{{ record.user.name }}</td>
                        
                        <td class="p-4" v-if="filterForm.type === 'expenses'">{{ new Date(record.expense_date).toLocaleDateString() }}</td><td v-if="filterForm.type === 'expenses'">EXP-{{ record.id }}</td><td v-if="filterForm.type === 'expenses'">{{ record.category }}</td><td v-if="filterForm.type === 'expenses'">{{ record.description || 'N/A' }}</td><td v-if="filterForm.type === 'expenses'">{{ formatCurrency(record.amount) }}</td><td v-if="filterForm.type === 'expenses'">{{ record.user.name }}</td>
                        <td class="p-2 text-center" v-if="filterForm.type === 'expenses'">
                            <div v-if="record.receipt_image_url">
                                <a :href="record.receipt_image_url" target="_blank" title="View full image">
                                    <img :src="record.receipt_image_url" alt="Receipt" class="h-12 w-12 inline-block object-cover rounded-md cursor-pointer transition hover:scale-105">
                                </a>
                            </div>
                            <div v-else>
                                <span class="text-xs text-gray-400">No Image</span>
                            </div>
                        </td>
                        
                        
                        <td class="p-4" v-if="filterForm.type === 'production_logs'">{{ new Date(record.log_date).toLocaleDateString() }}</td><td v-if="filterForm.type === 'production_logs'">PROD-{{ record.id }}</td><td v-if="filterForm.type === 'production_logs'">{{ record.egg_product.name }}</td><td v-if="filterForm.type === 'production_logs'">{{ record.quantity }} pcs</td><td v-if="filterForm.type === 'production_logs'">{{ record.user.name }}</td>
                        
                        <td class="p-4" v-if="filterForm.type === 'financial_summaries'">{{ new Date(record.start_date).toLocaleString('default', { month: 'long', year: 'numeric' }) }}</td>
                        <td v-if="filterForm.type === 'financial_summaries'">{{ record.generated_by?.name || 'N/A' }}</td>
                        <td v-if="filterForm.type === 'financial_summaries'">
                            <span class="px-3 py-1.5 text-xs font-semibold rounded-full inline-flex items-center gap-1"
                                :class="{
                                    'bg-yellow-100 text-yellow-800 border border-yellow-300': record.status === 'submitted',
                                    'bg-green-100 text-green-800 border border-green-300': record.status === 'approved',
                                    'bg-red-100 text-red-800 border border-red-300': record.status === 'rejected'
                                }">
                                <svg v-if="record.status === 'approved'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <svg v-else-if="record.status === 'rejected'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                {{ record.status.toUpperCase() }}
                            </span>
                        </td>
                        <td v-if="filterForm.type === 'financial_summaries'">{{ formatCurrency(record.net_income) }}</td>
                        <td v-if="filterForm.type === 'financial_summaries'" class="p-4">
                            <div class="flex items-center gap-2">
                                <Link 
                                    :href="route('admin.financial-reports.view', record.id)"
                                    class="px-3 py-1.5 bg-blue-500 text-white text-xs font-semibold rounded-lg hover:bg-blue-600 transition duration-200 flex items-center gap-1"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View
                                </Link>
                                <a 
                                    :href="route('admin.financial-reports.download', record.id)"
                                    download
                                    class="px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition duration-200 flex items-center gap-1"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="records.data.length === 0"><td colspan="7" class="p-8 text-center text-gray-500">No records found.</td></tr>
                </template>
                <template v-else-if="records && records.length > 0"> <!-- This block is for simple array results -->
                    <tr v-for="(record, index) in records" :key="index" class="border-b">
                        <td class="p-4" v-if="filterForm.type === 'current_egg_inventory'">{{ record.egg_size }}</td><td v-if="filterForm.type === 'current_egg_inventory'">{{ record.current_stock.toLocaleString() }} pcs</td><td v-if="filterForm.type === 'current_egg_inventory'">{{ formatCurrency(record.value_est) }}</td>
                        <td class="p-4" v-if="filterForm.type === 'current_chicken_stock'">Laying Hens</td><td v-if="filterForm.type === 'current_chicken_stock'">{{ record.quantity }}</td><td v-if="filterForm.type === 'current_chicken_stock'">{{ new Date(record.updated_at).toLocaleDateString() }}</td>
                    </tr>
                </template>
                <tr v-else><td colspan="7" class="p-8 text-center text-gray-500">No records found.</td></tr>
            </tbody>
        </table></div>
        
        <!-- Enhanced Pagination -->
        <div v-if="records && records.links" class="bg-white px-6 py-4 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <!-- Results Info -->
                <div class="text-sm text-gray-600">
                    Showing <span class="font-semibold">{{ records.from }}</span> to <span class="font-semibold">{{ records.to }}</span> of <span class="font-semibold">{{ records.total }}</span> results
                </div>
                
                <!-- Pagination Controls -->
                <div class="flex items-center gap-2">
                    <!-- Previous Button -->
                    <Link 
                        v-if="records.prev_page_url" 
                        :href="records.prev_page_url"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-medium"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Previous
                    </Link>
                    <span 
                        v-else
                        class="flex items-center gap-2 px-4 py-2 bg-gray-50 text-gray-400 rounded-lg cursor-not-allowed font-medium"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Previous
                    </span>
                    
                    <!-- Page Numbers (if available) -->
                    <div v-if="records.links && records.links.length > 3" class="flex items-center gap-1">
                        <template v-for="(link, index) in records.links" :key="index">
                            <Link
                                v-if="link.url && !link.active && link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;'"
                                :href="link.url"
                                class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200"
                                v-html="link.label"
                            ></Link>
                            <span
                                v-else-if="link.active"
                                class="px-3 py-2 text-sm font-semibold text-white bg-green-600 border border-green-600 rounded-lg"
                                v-html="link.label"
                            ></span>
                            <span
                                v-else-if="!link.url && link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;'"
                                class="px-3 py-2 text-sm text-gray-400"
                                v-html="link.label"
                            ></span>
                        </template>
                    </div>
                    
                    <!-- Next Button -->
                    <Link 
                        v-if="records.next_page_url" 
                        :href="records.next_page_url"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-medium"
                    >
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </Link>
                    <span 
                        v-else
                        class="flex items-center gap-2 px-4 py-2 bg-gray-50 text-gray-400 rounded-lg cursor-not-allowed font-medium"
                    >
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </span>
                </div>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>