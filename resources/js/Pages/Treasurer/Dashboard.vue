<script setup>
import TreasurerLayout from '@/Layouts/TreasurerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import BaseChart from '@/Components/BaseChart.vue'; // Use our new flexible chart component

const props = defineProps({
    revenueThisMonth: Number,
    expensesThisMonth: Number,
    netIncomeThisMonth: Number,
    barChartData: Object,
    pieChartData: Object,
    latestReportNotification: Object,
});

const formatCurrency = (value) => `₱${parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

// Data for Monthly Income vs. Expenses (Bar Chart)
const barChart = {
    labels: props.barChartData.labels,
    datasets: [
        { label: 'Total Revenue', backgroundColor: '#6A994E', data: props.barChartData.sales },
        { label: 'Total Expenses', backgroundColor: '#BC4749', data: props.barChartData.expenses },
    ],
};

// Data for Expense Breakdown (Pie Chart)
const pieChart = {
    labels: props.pieChartData.labels,
    datasets: [ { backgroundColor: ['#386641', '#2C7DA0', '#A7C957', '#C4C4C4'], data: props.pieChartData.data } ]
};
</script>

<template>
    <Head title="Treasurer Dashboard" />
    <TreasurerLayout>
        <template #header>Treasurer's Dashboard</template>
        <div class="space-y-6">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow"><h4 class="text-sm font-medium text-gray-500">📈 Total Revenue (This Month)</h4><p class="text-3xl font-bold text-gray-800 mt-2">{{ formatCurrency(revenueThisMonth) }}</p></div>
                <div class="bg-white p-6 rounded-lg shadow"><h4 class="text-sm font-medium text-gray-500">📉 Total Expenses (This Month)</h4><p class="text-3xl font-bold text-red-600 mt-2">{{ formatCurrency(expensesThisMonth) }}</p></div>
                <div class="bg-white p-6 rounded-lg shadow"><h4 class="text-sm font-medium text-gray-500">💰 Net Income (This Month)</h4><p class="text-3xl font-bold text-gray-800 mt-2">{{ formatCurrency(netIncomeThisMonth) }}</p></div>
                <!-- Financial Report Notification Card -->
                <div v-if="latestReportNotification" 
                     class="bg-white p-6 rounded-lg shadow flex flex-col"
                     :class="{
                         'border-l-4 border-green-500': latestReportNotification.status === 'approved',
                         'border-l-4 border-red-500': latestReportNotification.status === 'rejected'
                     }">
                    <div class="flex items-center gap-2 mb-2">
                        <svg v-if="latestReportNotification.status === 'approved'" 
                             class="w-6 h-6 text-green-600" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else 
                             class="w-6 h-6 text-red-600" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="text-sm font-medium"
                            :class="{
                                'text-green-600': latestReportNotification.status === 'approved',
                                'text-red-600': latestReportNotification.status === 'rejected'
                            }">
                            Financial Report {{ latestReportNotification.status === 'approved' ? 'Approved' : 'Rejected' }}
                        </h4>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">
                        {{ new Date(latestReportNotification.start_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }} - 
                        {{ new Date(latestReportNotification.end_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                    </p>
                    <p v-if="latestReportNotification.admin_notes" 
                       class="text-xs text-gray-700 mt-2 line-clamp-2">
                        {{ latestReportNotification.admin_notes }}
                    </p>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ new Date(latestReportNotification.reviewed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                    </p>
                </div>
                <div v-else class="bg-white p-6 rounded-lg shadow flex flex-col justify-center text-center">
                    <h4 class="text-sm font-medium text-gray-500">📄 No Recent Updates</h4>
                    <p class="mt-2 text-sm text-gray-400">No financial reports have been reviewed yet.</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Monthly Income vs Expenses Bar Chart -->
                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                     <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Income vs. Expenses</h3>
                     <div class="h-80">
                        <BaseChart chartType="bar" :chartData="barChart" />
                     </div>
                </div>
                <!-- Expense Breakdown Pie Chart -->
                <div class="bg-white p-6 rounded-lg shadow">
                     <h3 class="text-lg font-semibold text-gray-800 mb-4">Expense Breakdown</h3>
                      <div class="h-80">
                         <BaseChart chartType="pie" :chartData="pieChart" />
                     </div>
                </div>
            </div>
        </div>
    </TreasurerLayout>
</template>