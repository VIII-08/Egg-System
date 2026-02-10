<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TreasurerLayout from '@/Layouts/TreasurerLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    reportData: Object,
    filters: Object,
});

const filterForm = useForm({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const previewReport = () => {
    // Client-side validation
    if (!filterForm.start_date || !filterForm.end_date) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Dates',
            text: 'Please select both a start and end date to generate a preview.',
        });
        return;
    }
    if (new Date(filterForm.start_date) > new Date(filterForm.end_date)) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Date Range',
            text: 'The start date cannot be after the end date.',
        });
        return;
    }

    filterForm.get(route('treasurer.reports.index'), { preserveState: true });
};

// 3. UPGRADE THE submitForReview FUNCTION WITH CONFIRMATION AND FEEDBACK
const submitForReview = () => {
    // Check if a report has been generated first
    if (!props.reportData) {
        Swal.fire({
            icon: 'warning',
            title: 'No Report to Submit',
            text: 'Please preview a report before submitting it for review.',
        });
        return;
    }
    
    // Confirmation dialog
    Swal.fire({
        title: 'Submit for Review?',
        html: `This will finalize the report for the period <strong>${props.reportData.startDateFormatted}</strong> to <strong>${props.reportData.endDateFormatted}</strong> and send it for admin approval.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6', // Blue
        cancelButtonColor: '#6B7280', // Gray
        confirmButtonText: 'Yes, Submit It'
    }).then((result) => {
        if (result.isConfirmed) {
            useForm({
                start_date: filterForm.start_date,
                end_date: filterForm.end_date,
                report_data: props.reportData
            }).post(route('treasurer.reports.store'), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Submitted!',
                        text: 'The financial summary has been sent for admin review.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Optionally reset the page state
                    filterForm.reset();
                    // Inertia.get(route('treasurer.reports.index')); // Could reload to clear the preview
                },
                onError: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Submission Failed',
                        text: 'An unexpected error occurred. Please try again.',
                    });
                }
            });
        }
    });
};

const printReport = () => window.print();

const formatCurrency = (value) => `₱${parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
</script>

<template>
    <Head title="Generate Financial Summary" />

    <TreasurerLayout>
        <template #header>Generate Financial Summary</template>

        <!-- Hide controls when printing -->
        <div class="print:hidden">
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Control Panel -->
                <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-bold mb-4">Report Options</h2>
                    <form @submit.prevent="previewReport" class="space-y-4">
                        <div>
                            <label for="start_date" class="text-lg">Start Date</label>
                            <input v-model="filterForm.start_date" type="date" class="mt-1 block w-full text-base rounded-md border-gray-300">
                        </div>
                        <div>
                            <label for="end_date" class="text-lg">End Date</label>
                            <input v-model="filterForm.end_date" type="date" class="mt-1 block w-full text-base rounded-md border-gray-300">
                        </div>
                        <PrimaryButton type="submit" class="w-full text-lg py-3">Preview Report</PrimaryButton>
                    </form>
                </div>

                <!-- Live Preview Panel -->
                <div class="lg:col-span-3">
                    <div v-if="!reportData" class="flex items-center justify-center bg-gray-50 h-full p-6 rounded-lg border-2 border-dashed">
                         <p class="text-xl text-gray-500">Select a date range to preview your report.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- The Report Preview (this is what gets printed) -->
         <div v-if="reportData" class="mt-8 lg:mt-0 bg-white p-8 rounded-lg shadow" id="report-preview">
             <!-- Header with Logo and Company Info -->
             <div class="text-center mb-8 border-b-2 border-gray-300 pb-6">
                 <div class="flex justify-center items-center mb-4">
                     <img src="/Image/logo.jpg" alt="United Farmers Association Logo" class="h-20 w-auto">
                 </div>
                 <h1 class="text-3xl font-bold text-gray-800 mb-2">United Farmers Association of Baugo</h1>
                 <p class="text-sm text-gray-600 mb-1">Baugo, Maasin City, Southern Leyte, 6600, Philippines</p>
                 <h2 class="text-2xl font-bold text-gray-700 mt-4 mb-2">Financial Summary Report</h2>
                 <p class="text-md text-gray-500">For the period of {{ reportData.startDateFormatted }} to {{ reportData.endDateFormatted }}</p>
             </div>

             <!-- Income Statement Section -->
             <div class="mb-8">
                 <h2 class="text-xl font-bold mb-4 text-gray-700">INCOME STATEMENT</h2>
                 <p class="text-sm text-gray-600 mb-4">This statement shows the company's profitability by detailing revenues and expenses for the period.</p>
                 <table class="w-full text-sm border-collapse">
                     <tbody>
                         <tr class="border-b">
                             <td class="py-3 px-4 font-semibold">Total Revenue</td>
                             <td class="py-3 px-4 text-right font-semibold text-gray-700">{{ formatCurrency(reportData.totalRevenue) }}</td>
                         </tr>
                        <tr class="border-b" v-if="reportData.amountReceivables !== undefined">
                            <td class="py-3 px-4 font-semibold">Amount Receivables (Outstanding)</td>
                            <td class="py-3 px-4 text-right font-semibold text-amber-700">{{ formatCurrency(reportData.amountReceivables) }}</td>
                        </tr>
                        <tr class="border-b" v-if="reportData.cashCollected !== undefined">
                            <td class="py-3 px-4 font-semibold">Cash Collections (Revenue - Receivables)</td>
                            <td class="py-3 px-4 text-right font-semibold text-emerald-700">{{ formatCurrency(reportData.cashCollected) }}</td>
                        </tr>
                         <tr class="border-b">
                             <td class="py-3 px-4 font-semibold">Total Expenses</td>
                             <td class="py-3 px-4 text-right font-semibold text-gray-700">({{ formatCurrency(reportData.totalExpenses) }})</td>
                         </tr>
                         <tr class="font-bold text-lg bg-gray-100">
                             <td class="py-4 px-4">Net Income</td>
                             <td class="py-4 px-4 text-right text-gray-700">
                                 {{ formatCurrency(reportData.netIncome) }}
                             </td>
                         </tr>
                     </tbody>
                 </table>
             </div>

             <!-- Revenue Breakdown (if available) -->
             <div class="mb-8" v-if="reportData.revenueBreakdown && Object.keys(reportData.revenueBreakdown).length > 0">
                 <h2 class="text-xl font-bold mb-4 text-gray-700">REVENUE BREAKDOWN</h2>
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
                             <td class="border p-2 text-right text-gray-700">{{ formatCurrency(reportData.totalRevenue) }}</td>
                         </tr>
                     </tfoot>
                 </table>
             </div>

             <!-- Expense Breakdown -->
             <div class="mb-8" v-if="reportData.expenseBreakdown && Object.keys(reportData.expenseBreakdown).length > 0">
                 <h2 class="text-xl font-bold mb-4 text-gray-700">EXPENSE BREAKDOWN</h2>
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
                             <td class="border p-2 text-right text-gray-700">{{ formatCurrency(reportData.totalExpenses) }}</td>
                         </tr>
                     </tfoot>
                 </table>
             </div>

             <!-- Production Summary (Additional Financial Information) -->
             <div class="mb-8" v-if="reportData.productionBreakdown && Object.keys(reportData.productionBreakdown).length > 0">
                 <h2 class="text-xl font-bold mb-4 text-gray-700">OPERATIONAL SUMMARY</h2>
                 <p class="text-sm text-gray-600 mb-4">Production data for the reporting period.</p>
                 <table class="w-full text-sm border-collapse">
                     <thead>
                         <tr class="bg-gray-200 font-bold">
                             <th class="border p-2 text-left">Product</th>
                             <th class="border p-2 text-right">Quantity</th>
                         </tr>
                     </thead>
                     <tbody>
                         <tr v-for="(quantity, product) in reportData.productionBreakdown" :key="product" class="border-b">
                             <td class="border p-2">{{ product }}</td>
                             <td class="border p-2 text-right">{{ quantity.toLocaleString() }} pcs</td>
                         </tr>
                     </tbody>
                 </table>
             </div>
             
             <div class="mt-12 text-center print:hidden">
                <button @click="printReport" class="px-6 py-3 bg-gray-600 text-white rounded-md mr-4 hover:bg-gray-700">Print</button>
                <PrimaryButton @click="submitForReview" class="text-lg py-3">Submit for Admin Review</PrimaryButton>
             </div>
        </div>

    </TreasurerLayout>
</template>

<style>
@media print {
  body { visibility: hidden; }
  .print-container { padding: 0 !important; }
  #report-preview, #report-preview * { visibility: visible; }
  #report-preview { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>