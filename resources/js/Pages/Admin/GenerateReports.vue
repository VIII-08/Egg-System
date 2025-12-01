<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2'; 

const props = defineProps({ reportData: Object, filters: Object });

const filterForm = useForm({
    report_type: props.filters.report_type || 'inventory_report',
    start_date: props.filters.start_date || new Date().toISOString().slice(0, 10),
    end_date: props.filters.end_date || new Date().toISOString().slice(0, 10),
});

const generateReport = () => {
    // Client-side validation
    if (!filterForm.start_date || !filterForm.end_date) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Dates',
            text: 'Please select both a start and end date to generate a report.',
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

    filterForm.get(route('admin.reports.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const printReport = () => window.print();

const formatCurrency = (value) => `₱${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
</script>

<template>
    <Head title="Generate Reports" />
    <AdminLayout>
        <template #header>Generate Reports</template>

        <!-- Control Panel (hidden on print) -->
        <div class="print:hidden">
            <div class="bg-white p-6 rounded-lg shadow space-y-4">
                 <h2 class="text-xl font-bold">1. Select Report Options</h2>
                 <p class="text-gray-600">Choose the type of report and the date range you want to view.</p>
                <form @submit.prevent="generateReport" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label>Report Type</label>
                        <select v-model="filterForm.report_type" class="w-full mt-1 rounded-md text-lg"><option value="expense_summary">Expense Summary</option><option value="sales_summary">Sales Summary</option><option value="inventory_report">Inventory Report</option></select>
                    </div>
                    <div><label>Start Date</label><input v-model="filterForm.start_date" type="date" class="w-full mt-1 rounded-md"></div>
                    <div><label>End Date</label><input v-model="filterForm.end_date" type="date" class="w-full mt-1 rounded-md"></div>
                    <PrimaryButton
                        type="submit"
                        class="bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600
                            text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl
                            transition-all duration-300 ease-in-out text-lg tracking-wide"
                    >
                        Generate Report
                    </PrimaryButton>

                </form>
            </div>
        </div>

        <!-- Report Preview (what gets shown and printed) -->
        <div v-if="reportData" class="mt-6 bg-white p-8 rounded-lg shadow" id="report-preview">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <span v-if="reportData.reportType === 'expense_summary'">Expense Summary</span>
                        <span v-if="reportData.reportType === 'sales_summary'">Sales Summary</span>
                        <span v-if="reportData.reportType === 'inventory_report'">Inventory Report</span>
                    </h2>
                    <p class="text-gray-600">For the period of {{ reportData.startDate }} to {{ reportData.endDate }}</p>
                </div>
                 <div class="print:hidden flex space-x-2">
                     <button @click="printReport" class="px-4 py-2 bg-gray-200 text-sm rounded-md">Print</button>
                     <a :href="route('admin.reports.download', filterForm.data())" class="px-4 py-2 bg-gray-700 text-white text-sm rounded-md">Download as PDF</a>
                     <a v-if="filterForm.report_type === 'expense_summary' || filterForm.report_type === 'sales_summary' || filterForm.report_type === 'inventory_report'"
                       :href="route('admin.reports.download.excel', filterForm.data())"
                       class="px-4 py-2 bg-green-700 text-white text-sm rounded-md hover:bg-green-800">
                        Download as Excel
                     </a>
                 </div>
            </div>

            <!-- ** THIS IS THE MISSING HTML FOR THE NEW REPORTS ** -->

            <!-- Inventory Report Table -->
            <div v-if="reportData.reportType === 'inventory_report'">
                <h3 class="text-xl font-bold text-center mb-2">{{ reportData.monthYear ? reportData.monthYear.toUpperCase() : '' }}</h3>
                <h4 class="text-lg font-bold text-center mb-4">EGG PRODUCTION (Grams)</h4>
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-200 font-bold">
                            <th class="border p-2">DAYS</th>
                            <th class="border p-2">HENS</th>
                            <th class="border p-2">PULLETS</th>
                            <th class="border p-2">SMALL</th>
                            <th class="border p-2">MEDIUM</th>
                            <th class="border p-2">LARGE</th>
                            <th class="border p-2">X-LARGE</th>
                            <th class="border p-2">JUMBO</th>
                            <th class="border p-2">DAMAGED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in reportData.items" :key="index" 
                            :class="item.day === 'TOTAL' ? 'bg-gray-300 font-bold' : ''"
                            class="border-b">
                            <td class="border p-2 text-center">{{ item.day }}</td>
                            <td class="border p-2 text-center">{{ item.hens !== '' && item.hens !== null ? item.hens : '' }}</td>
                            <td class="border p-2 text-center">{{ item.PULLETS || 0 }}</td>
                            <td class="border p-2 text-center">{{ item.SMALL || 0 }}</td>
                            <td class="border p-2 text-center">{{ item.MEDIUM || 0 }}</td>
                            <td class="border p-2 text-center">{{ item.LARGE || 0 }}</td>
                            <td class="border p-2 text-center">{{ item['X-LARGE'] || 0 }}</td>
                            <td class="border p-2 text-center">{{ item.JUMBO || 0 }}</td>
                            <td class="border p-2 text-center">{{ item.DAMAGED || 0 }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Existing Tables Below -->

            <!-- Expense Summary Table -->
            <div v-if="reportData.reportType === 'expense_summary'">
                <h3 class="text-xl font-bold text-center mb-2">{{ reportData.monthYear ? reportData.monthYear.toUpperCase() : '' }}</h3>
                <div v-if="!reportData.items || reportData.items.length === 0" class="text-center text-gray-500 py-8">
                    <p>No expense data found for the selected date range.</p>
                </div>
                <table v-else class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-200 font-bold">
                            <th :colspan="(reportData.categoryNames?.length || 0) + 1" class="border p-2">EXPENSES</th>
                            <th class="border p-2">TOTAL</th>
                        </tr>
                        <tr class="bg-gray-200 font-bold">
                            <th class="border p-2">DAYS</th>
                            <th v-for="categoryName in reportData.categoryNames" :key="categoryName" class="border p-2">{{ categoryName }}</th>
                            <th class="border p-2">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in reportData.items" :key="index"
                            :class="item.day === 'TOTAL' ? 'bg-gray-300 font-bold' : ''"
                            class="border-b">
                            <td class="border p-2 text-center">{{ item.day }}</td>
                            <td v-for="categoryName in reportData.categoryNames" :key="categoryName" class="border p-2 text-right">
                                {{ (item[categoryName] || 0).toFixed(2) }}
                            </td>
                            <td class="border p-2 text-right font-semibold">{{ (item.total_expenses || 0).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Sales Summary Table -->
            <div v-if="reportData.reportType === 'sales_summary'">
                <h3 class="text-xl font-bold text-center mb-2">{{ reportData.monthYear ? reportData.monthYear.toUpperCase() : '' }}</h3>
                <div v-if="!reportData.items || reportData.items.length === 0" class="text-center text-gray-500 py-8">
                    <p>No sales data found for the selected date range.</p>
                </div>
                <table v-else class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-200 font-bold">
                            <th :colspan="(reportData.productNames?.length || 6) + 1" class="border p-2">EGG SALES</th>
                            <th colspan="2" class="border p-2">TOTAL</th>
                        </tr>
                        <tr class="bg-gray-100 font-bold">
                            <td class="border p-2"></td>
                            <td v-for="productName in reportData.productNames" :key="productName" class="border p-2">
                                {{ reportData.prices?.[productName] ? Number(reportData.prices[productName]).toFixed(2) : '0.00' }}
                            </td>
                            <td colspan="2" class="border p-2"></td>
                        </tr>
                        <tr class="bg-gray-200 font-bold">
                            <th class="border p-2">DAYS</th>
                            <th v-for="productName in reportData.productNames" :key="productName" class="border p-2">{{ productName }}</th>
                            <th class="border p-2">EGGS</th>
                            <th class="border p-2">SALES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in reportData.items" :key="index" 
                            :class="item.day === 'TOTAL' ? 'bg-gray-300 font-bold' : ''"
                            class="border-b">
                            <td class="border p-2 text-center">{{ item.day }}</td>
                            <template v-if="item.day === 'TOTAL'">
                                <td v-for="productName in reportData.productNames" :key="productName" class="border p-2 text-right">
                                    {{ (item[productName]?.quantity || 0).toLocaleString() }}
                                </td>
                            </template>
                            <template v-else>
                                <td v-for="productName in reportData.productNames" :key="productName" class="border p-2 text-right">
                                    {{ (item[productName]?.revenue || 0).toFixed(2) }}
                                </td>
                            </template>
                            <td class="border p-2 text-right">{{ (item.total_eggs || 0).toLocaleString() }}</td>
                            <td class="border p-2 text-right">{{ (item.total_sales || 0).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>