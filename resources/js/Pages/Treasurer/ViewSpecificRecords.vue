<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TreasurerLayout from '@/Layouts/TreasurerLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    sales: Object,
    expenses: Object,
    financialReports: Object,
    inventory: Object,
    staffAudit: Array,
    filters: Object,
    reviewedReportsCount: Number,
});

const activeTab = ref('inventory');
const hasViewedFinancialReports = ref(false);
const newReportsCount = ref(0);
const showViewReportModal = ref(false);
const selectedReportForView = ref(null);

// Check localStorage on mount and compare with current reviewed reports count
onMounted(() => {
    if (typeof window !== 'undefined') {
        const lastViewedCount = parseInt(localStorage.getItem('treasurer_last_viewed_reports_count') || '0', 10);
        const currentCount = props.reviewedReportsCount || 0;
        
        // Calculate new reports (difference between current and last viewed)
        const newCount = Math.max(0, currentCount - lastViewedCount);
        newReportsCount.value = newCount;
        
        // If there are new reports, show notification
        if (newCount > 0) {
            hasViewedFinancialReports.value = false;
        } else {
            // No new reports - mark as viewed
            hasViewedFinancialReports.value = true;
        }
    }
});

// Watch for when financial reports tab becomes active
watch(activeTab, (newTab) => {
    if (newTab === 'financial') {
        markFinancialReportsAsViewed();
    }
});

// Function to mark financial reports as viewed
const markFinancialReportsAsViewed = () => {
    if (typeof window !== 'undefined') {
        // Store the current count of reviewed reports
        localStorage.setItem('treasurer_last_viewed_reports_count', String(props.reviewedReportsCount || 0));
        hasViewedFinancialReports.value = true;
    }
};
const filterForm = useForm({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});
const submitFilter = () => { 
    filterForm.get(route('treasurer.records.index'), { preserveState: true }); 
};
const formatCurrency = (value) => `₱${parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const downloadReport = (reportId) => {
    console.log('Downloading report:', reportId);
    const url = `/treasurer/financial-reports/${reportId}/download`;
    console.log('Download URL:', url);
    window.open(url, '_blank');
};

const viewReport = (report) => {
    selectedReportForView.value = report;
    showViewReportModal.value = true;
};

const closeViewReportModal = () => {
    showViewReportModal.value = false;
    selectedReportForView.value = null;
};

const testReport = (reportId) => {
    console.log('Testing report:', reportId);
    const url = `/treasurer/financial-reports/${reportId}/test`;
    console.log('Test URL:', url);
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('Test response:', data);
            alert('Test successful: ' + data.message);
        })
        .catch(error => {
            console.error('Test error:', error);
            alert('Test failed: ' + error.message);
        });
};
</script>

<template>
    <Head title="View Records" />

    <TreasurerLayout>
        <template #header>View Records</template>

        <!-- Tab Navigation -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'inventory'" :class="[activeTab === 'inventory' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Inventory Status</button>
                <button @click="activeTab = 'sales'" :class="[activeTab === 'sales' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Sales Transactions</button>
                <button @click="activeTab = 'expenses'" :class="[activeTab === 'expenses' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Expense Records</button>
                <button @click="activeTab = 'financial'" :class="[activeTab === 'financial' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg relative">
                    Financial Reports
                    <span v-if="newReportsCount > 0" class="ml-2 px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">
                        {{ newReportsCount }}
                    </span>
                </button>
                <button @click="activeTab = 'audit'" :class="[activeTab === 'audit' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700']" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-lg">Staff Activity</button>
            </nav>
        </div>

        <!-- INVENTORY Tab -->
        <div v-if="activeTab === 'inventory' && inventory && inventory.eggs" class="grid grid-cols-1 md:grid-cols-2 gap-8">
             <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Categorized Egg Inventory</h2>
                <table class="w-full text-lg">
                    <tbody>
                        <tr v-for="egg in inventory.eggs" class="border-b">
                            <td class="py-2 text-gray-700">{{ egg.name }}</td>
                            <td class="py-2 text-right font-bold text-gray-900">{{ egg.stock_quantity.toLocaleString() }} pcs</td>
                        </tr>
                    </tbody>
                </table>
             </div>
             <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Chicken Stock</h2>
                <div class="text-center mt-8">
                    <p class="text-6xl font-extrabold text-blue-600">{{ inventory.chickens.toLocaleString() }}</p>
                    <p class="text-lg text-gray-600">Live Heads</p>
                </div>
             </div>
        </div>
        
        <!-- Other Tabs (with date filters) -->
        <div v-else>
             <div class="bg-white p-4 rounded-lg shadow mb-6">
                <form @submit.prevent="submitFilter" class="flex items-end space-x-4">
                    <div>
                        <label for="start_date">Start Date</label>
                        <input v-model="filterForm.start_date" type="date" id="start_date" class="mt-1 block w-full rounded-md">
                    </div>
                    <div>
                        <label for="end_date">End Date</label>
                        <input v-model="filterForm.end_date" type="date" id="end_date" class="mt-1 block w-full rounded-md">
                    </div>
                    <PrimaryButton type="submit">Filter</PrimaryButton>
                </form>
             </div>

             <!-- SALES Tab -->
             <div v-if="activeTab === 'sales'" class="bg-white rounded-lg shadow-md">
                 <div class="overflow-x-auto">
                    <table class="w-full text-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 text-left font-semibold text-gray-600">ID</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Date</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Recorded By</th>
                                <th class="p-4 text-right font-semibold text-gray-600">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="record in sales.data" :key="record.id" class="hover:bg-gray-50">
                                <td class="p-4 font-bold text-gray-700">{{ record.id }}</td>
                                <td class="p-4 text-gray-600">{{ new Date(record.created_at).toLocaleDateString() }}</td>
                                <td class="p-4 text-gray-800">{{ record.user?.name || 'N/A' }}</td>
                                <td class="p-4 text-right font-bold text-green-600">{{ formatCurrency(record.total_amount) }}</td>
                            </tr>
                            <tr v-if="sales.data.length === 0">
                                <td colspan="10" class="p-8 text-center text-gray-500">No sales records found.</td>
                            </tr>
                        </tbody>
                    </table>
                 </div>
                 <!-- Pagination -->
                 <div class="p-4 flex justify-between items-center border-t">
                      <div class="text-sm text-gray-600">Showing {{ sales.from }} to {{ sales.to }} of {{ sales.total }} results</div>
                      <div>
                        <Link v-if="sales.prev_page_url" :href="sales.prev_page_url" class="px-4 py-2 bg-gray-200 rounded-l-md">Previous</Link>
                        <Link v-if="sales.next_page_url" :href="sales.next_page_url" class="px-4 py-2 bg-gray-200 rounded-r-md">Next</Link>
                      </div>
                 </div>
             </div>

             <!-- EXPENSES Tab -->
             <div v-if="activeTab === 'expenses'" class="bg-white rounded-lg shadow-md">
                 <div class="overflow-x-auto">
                    <table class="w-full text-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 text-left font-semibold text-gray-600">ID</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Date</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Recorded By</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Category</th>
                                <th class="p-4 text-right font-semibold text-gray-600">Amount</th>
                                <!-- 1. ADD NEW HEADER FOR THE RECEIPT IMAGE -->
                                <th class="p-4 text-center font-semibold text-gray-600">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="record in expenses.data" :key="record.id" class="hover:bg-gray-50">
                                <td class="p-4 font-bold text-gray-700">{{ record.id }}</td>
                                <td class="p-4 text-gray-600">{{ new Date(record.expense_date).toLocaleDateString() }}</td>
                                <td class="p-4 text-gray-800">{{ record.user?.name || 'N/A' }}</td>
                                <td class="p-4 text-gray-800">{{ record.category }}</td>
                                <td class="p-4 text-right font-bold text-red-600">{{ formatCurrency(record.amount) }}</td>
                                
                                <!-- 2. ADD NEW CELL FOR THE IMAGE -->
                                <td class="p-2 text-center">
                                    <div v-if="record.receipt_image_url">
                                        <a :href="record.receipt_image_url" target="_blank" title="View full image">
                                            <img :src="record.receipt_image_url" alt="Receipt" class="h-14 w-14 inline-block object-cover rounded-md cursor-pointer transition transform hover:scale-110">
                                        </a>
                                    </div>
                                    <div v-else>
                                        <span class="text-sm text-gray-400">No Image</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- 3. UPDATE COLSPAN TO 6 FOR THE "NO EXPENSES" ROW -->
                            <tr v-if="expenses.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-gray-500">No expense records found.</td>
                            </tr>
                        </tbody>
                    </table>
                 </div>
                 <!-- Pagination -->
                 <div class="p-4 flex justify-between items-center border-t">
                      <div class="text-sm text-gray-600">Showing {{ expenses.from }} to {{ expenses.to }} of {{ expenses.total }} results</div>
                      <div>
                        <Link v-if="expenses.prev_page_url" :href="expenses.prev_page_url" class="px-4 py-2 bg-gray-200 rounded-l-md">Previous</Link>
                        <Link v-if="expenses.next_page_url" :href="expenses.next_page_url" class="px-4 py-2 bg-gray-200 rounded-r-md">Next</Link>
                      </div>
                 </div>
             </div>

             <!-- FINANCIAL REPORTS Tab -->
             <div v-if="activeTab === 'financial'" class="bg-white rounded-lg shadow-md">
                 <div class="overflow-x-auto">
                    <table class="w-full text-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 text-left font-semibold text-gray-600">ID</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Period</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Generated By</th>
                                <th class="p-4 text-left font-semibold text-gray-600">Status</th>
                                <th class="p-4 text-right font-semibold text-gray-600">Net Income</th>
                                <th class="p-4 text-center font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="report in financialReports.data" :key="report.id" class="hover:bg-gray-50">
                                <td class="p-4 font-bold text-gray-700">{{ report.id }}</td>
                                <td class="p-4 text-gray-600">{{ new Date(report.start_date).toLocaleDateString() }} - {{ new Date(report.end_date).toLocaleDateString() }}</td>
                                <td class="p-4 text-gray-800">{{ report.generated_by?.name || 'N/A' }}</td>
                                <td class="p-4">
                                    <span :class="{
                                        'bg-green-100 text-green-800': report.status === 'approved',
                                        'bg-red-100 text-red-800': report.status === 'rejected',
                                        'bg-yellow-100 text-yellow-800': report.status === 'submitted',
                                        'bg-gray-100 text-gray-800': report.status === 'draft'
                                    }" class="px-2 py-1 rounded-full text-sm font-medium">
                                        {{ report.status.charAt(0).toUpperCase() + report.status.slice(1) }}
                                    </span>
                                </td>
                                <td class="p-4 text-right font-bold" :class="report.net_income >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatCurrency(report.net_income) }}
                                </td>
                                <td class="p-4 text-center">
                                    <div v-if="report.status === 'approved' || report.status === 'rejected'" class="flex justify-center space-x-2">
                                        <button @click="viewReport(report)" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </button>
                                        <button v-if="report.status === 'approved'" @click="downloadReport(report.id)" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-sm">
                                            Download
                                        </button>
                                    </div>
                                    
                                </td>
                            </tr>
                            <tr v-if="financialReports.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-gray-500">No financial reports found.</td>
                            </tr>
                        </tbody>
                    </table>
                 </div>
                 <!-- Pagination -->
                 <div class="p-4 flex justify-between items-center border-t">
                      <div class="text-sm text-gray-600">Showing {{ financialReports.from }} to {{ financialReports.to }} of {{ financialReports.total }} results</div>
                      <div>
                        <Link v-if="financialReports.prev_page_url" :href="financialReports.prev_page_url" class="px-4 py-2 bg-gray-200 rounded-l-md">Previous</Link>
                        <Link v-if="financialReports.next_page_url" :href="financialReports.next_page_url" class="px-4 py-2 bg-gray-200 rounded-r-md">Next</Link>
                      </div>
                 </div>
             </div>

             <!-- STAFF AUDIT Tab -->
             <div v-if="activeTab === 'audit'" class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Staff Activity Summary</h2>
                 <table class="w-full text-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-4 text-left">Staff Member</th>
                            <th class="p-4 text-center">Production Logs</th>
                            <th class="p-4 text-center">Expenses Logged</th>
                            <th class="p-4 text-center">Sales Logged</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in staffAudit" :key="user.id" class="border-b">
                            <td class="p-4 font-semibold">{{ user.name }}</td>
                            <td class="p-4 text-center">{{ user.production_logs_count }}</td>
                            <td class="p-4 text-center">{{ user.expenses_count }}</td>
                            <td class="p-4 text-center">{{ user.sales_transactions_count }}</td>
                        </tr>
                    </tbody>
                 </table>
             </div>
        </div>
        <!-- View Financial Report Modal -->
        <Modal :show="showViewReportModal" @close="closeViewReportModal" max-width="5xl">
            <div v-if="selectedReportForView" class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-t-lg flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Financial Summary Report
                            </h2>
                            <p class="text-green-100 text-sm mt-1">
                                Period: {{ new Date(selectedReportForView.start_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }} - 
                                {{ new Date(selectedReportForView.end_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}
                            </p>
                            <p class="text-green-100 text-xs mt-1">
                                Status: 
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                    :class="{
                                        'bg-green-200 text-green-800': selectedReportForView.status === 'approved',
                                        'bg-red-200 text-red-800': selectedReportForView.status === 'rejected',
                                        'bg-yellow-200 text-yellow-800': selectedReportForView.status === 'submitted'
                                    }">
                                    {{ selectedReportForView.status.toUpperCase() }}
                                </span>
                            </p>
                        </div>
                        <button 
                            @click="closeViewReportModal"
                            class="text-white hover:text-gray-200 transition"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body - Scrollable -->
                <div class="p-6 overflow-y-auto flex-1">
                    <!-- Header with Logo and Company Info -->
                    <div class="text-center mb-8 border-b-2 border-gray-300 pb-6">
                        <div class="flex justify-center items-center mb-4">
                            <img src="/Image/logo.jpg" alt="United Farmers Association Logo" class="h-20 w-auto">
                        </div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">United Farmers Association of Baugo</h1>
                        <p class="text-sm text-gray-600 mb-1">Baugo, Maasin City, Southern Leyte, 6600, Philippines</p>
                        <h2 class="text-2xl font-bold text-gray-700 mt-4 mb-2">Financial Summary Report</h2>
                        <p class="text-md text-gray-500">For the period of {{ new Date(selectedReportForView.start_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }} - {{ new Date(selectedReportForView.end_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</p>
                    </div>

                    <!-- Admin Notes (if rejected or approved) -->
                    <div v-if="selectedReportForView.admin_notes && (selectedReportForView.status === 'rejected' || selectedReportForView.status === 'approved')" 
                         class="mb-6 p-4 rounded-lg border-l-4"
                         :class="{
                             'bg-red-50 border-red-500': selectedReportForView.status === 'rejected',
                             'bg-green-50 border-green-500': selectedReportForView.status === 'approved'
                         }">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" 
                                 :class="selectedReportForView.status === 'rejected' ? 'text-red-600' : 'text-green-600'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold mb-1"
                                   :class="selectedReportForView.status === 'rejected' ? 'text-red-800' : 'text-green-800'">
                                    Admin Notes:
                                </p>
                                <p class="text-sm leading-relaxed"
                                   :class="selectedReportForView.status === 'rejected' ? 'text-red-700' : 'text-green-700'">
                                    {{ selectedReportForView.admin_notes }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Income Statement Section -->
                    <div class="mb-8">
                        <h3 class="text-xl font-bold mb-3 text-gray-700">INCOME STATEMENT</h3>
                        <p class="text-sm text-gray-600 mb-4">This statement shows the company's profitability by detailing revenues and expenses for the period.</p>
                        <table class="w-full text-sm border-collapse">
                            <tbody>
                                <tr class="border-b">
                                    <td class="py-3 px-4 font-semibold">Total Revenue</td>
                                    <td class="py-3 px-4 text-right font-semibold text-gray-700">
                                        {{ formatCurrency(selectedReportForView.report_data?.totalRevenue || selectedReportForView.total_revenue) }}
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-3 px-4 font-semibold">Total Expenses</td>
                                    <td class="py-3 px-4 text-right font-semibold text-gray-700">
                                        ({{ formatCurrency(selectedReportForView.report_data?.totalExpenses || selectedReportForView.total_expenses) }})
                                    </td>
                                </tr>
                                <tr class="font-bold text-lg bg-gray-100">
                                    <td class="py-4 px-4">Net Income</td>
                                    <td class="py-4 px-4 text-right text-gray-700">
                                        {{ formatCurrency(selectedReportForView.report_data?.netIncome || selectedReportForView.net_income) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Revenue Breakdown -->
                    <div class="mb-8" v-if="selectedReportForView.report_data?.revenueBreakdown && Object.keys(selectedReportForView.report_data.revenueBreakdown).length > 0">
                        <h3 class="text-xl font-bold mb-3 text-gray-700">REVENUE BREAKDOWN</h3>
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-200 font-bold">
                                    <th class="border p-2 text-left">Product</th>
                                    <th class="border p-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(amount, product) in selectedReportForView.report_data.revenueBreakdown" :key="product" class="border-b">
                                    <td class="border p-2">{{ product }}</td>
                                    <td class="border p-2 text-right">{{ formatCurrency(amount) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td class="border p-2">Total Revenue</td>
                                    <td class="border p-2 text-right text-gray-700">
                                        {{ formatCurrency(selectedReportForView.report_data?.totalRevenue || selectedReportForView.total_revenue) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Expense Breakdown -->
                    <div class="mb-8" v-if="selectedReportForView.report_data?.expenseBreakdown && Object.keys(selectedReportForView.report_data.expenseBreakdown).length > 0">
                        <h3 class="text-xl font-bold mb-3 text-gray-700">EXPENSE BREAKDOWN</h3>
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-200 font-bold">
                                    <th class="border p-2 text-left">Category</th>
                                    <th class="border p-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(amount, category) in selectedReportForView.report_data.expenseBreakdown" :key="category" class="border-b">
                                    <td class="border p-2">{{ category || 'Uncategorized' }}</td>
                                    <td class="border p-2 text-right">{{ formatCurrency(amount) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td class="border p-2">Total Expenses</td>
                                    <td class="border p-2 text-right text-gray-700">
                                        {{ formatCurrency(selectedReportForView.report_data?.totalExpenses || selectedReportForView.total_expenses) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Production Summary -->
                    <div class="mb-8" v-if="selectedReportForView.report_data?.productionBreakdown && Object.keys(selectedReportForView.report_data.productionBreakdown).length > 0">
                        <h3 class="text-xl font-bold mb-3 text-gray-700">OPERATIONAL SUMMARY</h3>
                        <p class="text-sm text-gray-600 mb-4">Production data for the reporting period.</p>
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-200 font-bold">
                                    <th class="border p-2 text-left">Product</th>
                                    <th class="border p-2 text-right">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(quantity, product) in selectedReportForView.report_data.productionBreakdown" :key="product" class="border-b">
                                    <td class="border p-2">{{ product }}</td>
                                    <td class="border p-2 text-right">{{ quantity.toLocaleString() }} pcs</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg border-t border-gray-200 flex-shrink-0 flex justify-end gap-3">
                    <button 
                        @click="closeViewReportModal" 
                        class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
                    >
                        Close
                    </button>
                    <a 
                        v-if="selectedReportForView.status === 'approved'"
                        :href="route('treasurer.reports.download', selectedReportForView.id)"
                        class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download PDF
                    </a>
                </div>
            </div>
        </Modal>
    </TreasurerLayout>
</template>
