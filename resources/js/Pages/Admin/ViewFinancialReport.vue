<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    report: Object,
    reportData: Object,
});

const formatCurrency = (value) => `₱${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="View Financial Report" />
    <AdminLayout>
        <template #header>Financial Report Details</template>

        <!-- Back Button -->
        <div class="mb-4">
            <Link 
                :href="route('admin.records.index', { type: 'financial_summaries' })"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-medium"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Financial Summaries
            </Link>
        </div>

        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Financial Report</h2>
                    <p class="text-gray-600">
                        Period: {{ new Date(report.start_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }} - 
                        {{ new Date(report.end_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">Submitted by: {{ report.generated_by?.name || 'N/A' }}</p>
                </div>
                <div class="print:hidden flex space-x-2">
                    <button @click="printReport" class="px-4 py-2 bg-gray-200 text-sm rounded-md hover:bg-gray-300">Print</button>
                    <a :href="route('admin.financial-reports.download', report.id)" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                        Download PDF
                    </a>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="mb-6">
                <span class="px-4 py-2 text-sm font-semibold rounded-full inline-flex items-center gap-2"
                    :class="{
                        'bg-yellow-100 text-yellow-800 border border-yellow-300': report.status === 'submitted',
                        'bg-green-100 text-green-800 border border-green-300': report.status === 'approved',
                        'bg-red-100 text-red-800 border border-red-300': report.status === 'rejected'
                    }">
                    <svg v-if="report.status === 'approved'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <svg v-else-if="report.status === 'rejected'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                    {{ report.status.toUpperCase() }}
                </span>
                <p v-if="report.reviewed_at" class="text-xs text-gray-500 mt-2">
                    Reviewed on: {{ new Date(report.reviewed_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                </p>
            </div>

            <!-- Admin Notes (if rejected or approved) -->
            <div v-if="report.admin_notes && (report.status === 'rejected' || report.status === 'approved')" 
                 class="mb-6 p-4 rounded-lg border-l-4"
                 :class="{
                     'bg-red-50 border-red-500': report.status === 'rejected',
                     'bg-green-50 border-green-500': report.status === 'approved'
                 }">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" 
                         :class="report.status === 'rejected' ? 'text-red-600' : 'text-green-600'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold mb-1"
                           :class="report.status === 'rejected' ? 'text-red-800' : 'text-green-800'">
                            Admin Notes:
                        </p>
                        <p class="text-sm leading-relaxed"
                           :class="report.status === 'rejected' ? 'text-red-700' : 'text-green-700'">
                            {{ report.admin_notes }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="mb-8">
                <h3 class="text-lg font-bold mb-3 text-gray-700">FINANCIAL SUMMARY</h3>
                <table class="w-full text-sm border-collapse">
                    <tbody>
                        <tr class="border-b">
                            <td class="py-3 px-4 font-semibold">Total Revenue</td>
                            <td class="py-3 px-4 text-right font-semibold text-green-600">{{ formatCurrency(report.total_revenue) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 px-4 font-semibold">Total Expenses</td>
                            <td class="py-3 px-4 text-right font-semibold text-red-600">({{ formatCurrency(report.total_expenses) }})</td>
                        </tr>
                        <tr class="font-bold text-lg bg-gray-100">
                            <td class="py-4 px-4">Net Income</td>
                            <td class="py-4 px-4 text-right" :class="report.net_income >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ formatCurrency(report.net_income) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Revenue Breakdown -->
            <div class="mb-8" v-if="reportData?.revenueBreakdown && Object.keys(reportData.revenueBreakdown).length > 0">
                <h3 class="text-lg font-bold mb-3 text-gray-700">REVENUE BREAKDOWN</h3>
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-200 font-bold">
                            <th class="border p-2 text-left">Product</th>
                            <th class="border p-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(amount, product) in reportData.revenueBreakdown" :key="product" class="border-b">
                            <td class="border p-2">{{ product }}</td>
                            <td class="border p-2 text-right">{{ formatCurrency(amount) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold">
                            <td class="border p-2">Total Revenue</td>
                            <td class="border p-2 text-right text-green-600">{{ formatCurrency(report.total_revenue) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Expense Breakdown -->
            <div class="mb-8" v-if="reportData?.expenseBreakdown && Object.keys(reportData.expenseBreakdown).length > 0">
                <h3 class="text-lg font-bold mb-3 text-gray-700">EXPENSE BREAKDOWN</h3>
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-200 font-bold">
                            <th class="border p-2 text-left">Category</th>
                            <th class="border p-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(amount, category) in reportData.expenseBreakdown" :key="category" class="border-b">
                            <td class="border p-2">{{ category || 'Uncategorized' }}</td>
                            <td class="border p-2 text-right">{{ formatCurrency(amount) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold">
                            <td class="border p-2">Total Expenses</td>
                            <td class="border p-2 text-right text-red-600">{{ formatCurrency(report.total_expenses) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

