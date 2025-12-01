<script setup>
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import BaseChart from '@/Components/BaseChart.vue';

const props = defineProps({
    totalSalesThisMonth: Number,
    eggProductionToday: Number,
    totalEggsInStock: Number,
    pendingApprovalsCount: Number,
    inventoryPieChartData: Array,
    recentActivity: Array,
    inventoryStatusData: Array,
    salesVsForecastChart: Object,
});

const formatCurrency = (value) => `₱${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const formatAuditLog = (logEntry) => {
    if (!logEntry) return '';
    // Replace backticks with bold formatting
    return logEntry
        .replace(/`([^`]+)`/g, '<span class="font-bold">$1</span>')
        .replace(/\n/g, '<br>');
};

// Prepare Pie Chart Data
const pieChart = {
    labels: props.inventoryPieChartData.map(p => p.label),
    datasets: [{
        backgroundColor: ['#6A994E', '#A7C957', '#386641', '#F2E8CF', '#BC4749'],
        data: props.inventoryPieChartData.map(p => p.value)
    }]
};

const salesPerformanceChart = computed(() => {
    if (!props.salesVsForecastChart) return null;

    return {
        labels: props.salesVsForecastChart.labels,
        datasets: [
            {
                label: 'Actual Monthly Sales',
                backgroundColor: 'rgba(59, 130, 246, 0.4)',
                borderColor: '#1D4ED8',
                data: props.salesVsForecastChart.actual,
                tension: 0.2,
                fill: true,
            },
            {
                label: 'Forecasted Sales',
                borderColor: '#F97316',
                backgroundColor: 'rgba(249, 115, 22, 0.2)',
                borderDash: [6, 4],
                data: props.salesVsForecastChart.forecast,
                tension: 0.2,
            },
        ],
    };
});
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <template #header>Admin Dashboard</template>
        <div class="space-y-6">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow"><h4 class="text-sm font-medium text-gray-500">Total Sales (This Month)</h4><p class="text-3xl font-bold mt-2">{{ formatCurrency(totalSalesThisMonth) }}</p></div>
                <div class="bg-white p-6 rounded-lg shadow"><h4 class="text-sm font-medium text-gray-500">Today's Egg Production</h4><p class="text-3xl font-bold mt-2">{{ eggProductionToday?.toLocaleString() ?? 0 }} pcs</p></div>
                <div class="bg-white p-6 rounded-lg shadow"><h4 class="text-sm font-medium text-gray-500">Total Eggs in Stock</h4><p class="text-3xl font-bold text-green-600 mt-2">{{ totalEggsInStock?.toLocaleString() ?? 0 }} pcs</p></div>
                <div class="bg-yellow-100 border border-yellow-300 p-6 rounded-lg shadow flex flex-col justify-center text-center"><h4 class="text-sm font-medium text-yellow-800">Pending Approvals</h4><p class="text-3xl font-bold text-yellow-900 mt-2">{{ pendingApprovalsCount }} Requests</p></div>
            </div>

            <!-- Sales Performance Graph -->
            <div class="bg-white p-6 rounded-lg shadow">
                 <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales Performance vs. Forecast</h3>
                 <div class="h-80">
                    <BaseChart
                        v-if="salesPerformanceChart"
                        chartType="line"
                        :chartData="salesPerformanceChart"
                    />
                    <div v-else class="flex items-center justify-center h-full text-gray-400">
                        Not enough data to display the chart.
                    </div>
                 </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Inventory by Egg Size Pie Chart -->
                <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                     <h3 class="text-lg font-semibold text-gray-800 mb-4">Inventory by Egg Size</h3>
                     <div class="h-96">
                        <BaseChart chartType="pie" :chartData="pieChart" />
                     </div>
                </div>
                <!-- Recent System Activity (with safe access) -->
                <div class="bg-white p-6 rounded-lg shadow">
                     <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent System Activity</h3>
                     <ul class="space-y-4">
                         <li v-for="activity in recentActivity" :key="activity.date + (activity.data.id || activity.data.log_entry)" class="flex items-center space-x-3">
                            <span class="p-2 rounded-full" :class="{
                                'bg-green-100': activity.type === 'Sale', 
                                'bg-red-100': activity.type === 'Expense', 
                                'bg-blue-100': activity.type === 'Production',
                                'bg-yellow-100': activity.type === 'Audit'
                            }"></span>
                             <div>
                                 <p class="text-sm text-gray-800">
                                     <template v-if="activity.type === 'Sale'">Sold for <span class="font-bold">{{ formatCurrency(activity.data.total_amount) }}</span></template>
                                     <template v-if="activity.type === 'Expense'">{{ activity.data.category }} expense for <span class="font-bold">{{ formatCurrency(activity.data.amount) }}</span></template>
                                     <template v-if="activity.type === 'Production'">Collected <span class="font-bold">{{ activity.data.quantity }} pcs</span> of <span class="font-bold">{{ activity.data.egg_product?.name ?? 'N/A' }}</span></template>
                                     <template v-if="activity.type === 'Audit'"><span v-html="formatAuditLog(activity.data.log_entry)"></span></template>
                                 </p>
                                 <p class="text-xs text-gray-500" v-if="activity.type !== 'Audit'">by {{ activity.data.user?.name ?? 'N/A' }}</p>
                                 <p class="text-xs text-gray-500" v-if="activity.type === 'Audit'">by {{ activity.data.user?.name ?? 'System' }}</p>
                             </div>
                         </li>
                     </ul>
                </div>
            </div>

            <!-- Daily Inventory Status Table (with safe access) -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Daily Inventory Status</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-4 text-left font-semibold text-gray-600">Egg Sizes</th>
                                <th class="p-4 text-right font-semibold text-gray-600">Remaining</th>
                                <th class="p-4 text-right font-semibold text-gray-600">Sold (Today)</th>
                                <th class="p-4 text-center font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-if="inventoryStatusData && inventoryStatusData.length > 0" v-for="item in inventoryStatusData" :key="item.egg_size">
                                <td class="p-4 font-bold text-gray-700">{{ item.egg_size }}</td>
                                <td class="p-4 text-right text-gray-800">{{ item.remaining?.toLocaleString() ?? 'N/A' }} pcs</td>
                                <td class="p-4 text-right text-gray-800">{{ item.sold_today?.toLocaleString() ?? 0 }} pcs</td>
                                <td class="p-4 text-center">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full" :class="{'bg-red-100 text-red-800': item.status === 'Out of Stock', 'bg-yellow-100 text-yellow-800': item.status === 'Low on Stocks', 'bg-green-100 text-green-800': item.status === 'Good'}">
                                        {{ item.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-else>
                                <td colspan="4" class="p-8 text-center text-gray-500">No inventory data to display.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>