<script setup>
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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
    todayProductionBatches: Array,
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

const productionBatches = computed(() => props.todayProductionBatches ?? []);
const showBatchModal = ref(false);
const selectedBatchRef = ref(null);

const openBatchModal = () => {
    if (!productionBatches.value.length) return;
    if (!selectedBatchRef.value && productionBatches.value.length) {
        selectedBatchRef.value = productionBatches.value[0]?.batch_reference ?? null;
    }
    showBatchModal.value = true;
};

const closeBatchModal = () => {
    showBatchModal.value = false;
};

const selectBatch = (batchRef) => {
    selectedBatchRef.value = batchRef;
};

const selectedBatch = computed(() =>
    productionBatches.value.find(b => b.batch_reference === selectedBatchRef.value) ?? null
);

const formatTime = (value) => {
    if (!value) return '';
    const date = new Date(value);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    return date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
};
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <template #header>Admin Dashboard</template>
        <div class="space-y-6">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow"><h4 class="text-sm font-medium text-gray-500">Total Sales (This Month)</h4><p class="text-3xl font-bold mt-2">{{ formatCurrency(totalSalesThisMonth) }}</p></div>
                <button
                    type="button"
                    class="bg-white p-6 rounded-lg shadow text-left focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition hover:shadow-md disabled:opacity-70 disabled:cursor-not-allowed"
                    :disabled="!productionBatches.length"
                    @click="openBatchModal"
                >
                    <h4 class="text-sm font-medium text-gray-500 flex items-center justify-between">
                        <span>Today's Egg Production</span>
                        <span v-if="productionBatches.length" class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded-full">
                            View batches
                        </span>
                    </h4>
                    <p class="text-3xl font-bold mt-2">{{ eggProductionToday?.toLocaleString() ?? 0 }} pcs</p>
                    <p class="text-xs text-gray-500 mt-1" v-if="productionBatches.length">Tap to see today's batches</p>
                    <p class="text-xs text-gray-400 mt-1" v-else>No batches logged yet</p>
                </button>
                <div class="bg-white p-6 rounded-lg shadow"><h4 class="text-sm font-medium text-gray-500">Total Eggs in Stock</h4><p class="text-3xl font-bold text-green-600 mt-2">{{ totalEggsInStock?.toLocaleString() ?? 0 }} pcs</p></div>
                <Link
                    :href="route('admin.approvals.index')"
                    class="block bg-yellow-100 border border-yellow-300 p-6 rounded-lg shadow flex flex-col justify-center text-center transition hover:shadow-md hover:bg-yellow-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 cursor-pointer"
                >
                    <h4 class="text-sm font-medium text-yellow-800">Pending Approvals</h4>
                    <p class="text-3xl font-bold text-yellow-900 mt-2">{{ pendingApprovalsCount }} Requests</p>
                </Link>
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

        <!-- Today's Production Batches Modal -->
        <div
            v-if="showBatchModal"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black bg-opacity-40 px-4 py-10"
            @click.self="closeBatchModal"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full overflow-hidden border border-gray-100">
                <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Today</p>
                        <h3 class="text-xl font-bold text-gray-900">Egg Production Batches</h3>
                        <p class="text-sm text-gray-500">Tap a batch to view its egg size breakdown.</p>
                    </div>
                    <button
                        type="button"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none"
                        @click="closeBatchModal"
                    >
                        ✕
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-0 lg:gap-6 p-6">
                    <div class="lg:col-span-1 space-y-3">
                        <p class="text-sm font-semibold text-gray-700">Batches logged today</p>
                        <div
                            v-if="productionBatches.length"
                            class="space-y-3"
                        >
                            <button
                                v-for="batch in productionBatches"
                                :key="batch.batch_reference"
                                type="button"
                                @click="selectBatch(batch.batch_reference)"
                                class="w-full text-left rounded-xl border transition p-4"
                                :class="selectedBatchRef === batch.batch_reference ? 'border-emerald-500 bg-emerald-50 shadow-sm' : 'border-gray-200 hover:border-emerald-200'"
                            >
                                <div class="flex items-center justify-between text-sm text-gray-600">
                                    <span>{{ formatDate(batch.created_at) }}</span>
                                    <span class="font-semibold text-emerald-700">{{ formatTime(batch.created_at) }}</span>
                                </div>
                                <p class="text-lg font-bold text-gray-900 mt-1">{{ batch.total_quantity?.toLocaleString() }} pcs</p>
                                <p class="text-xs text-gray-500">Logged by {{ batch.logged_by ?? 'Unknown' }}</p>
                            </button>
                        </div>
                        <div v-else class="text-sm text-gray-500 bg-gray-50 border border-dashed border-gray-200 rounded-xl p-4">
                            No batches logged today yet.
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div v-if="selectedBatch" class="rounded-2xl border border-gray-100 bg-white shadow-sm">
                            <div class="px-5 py-4 border-b bg-gray-50 rounded-t-2xl">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Batch details</p>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-gray-600">{{ formatDate(selectedBatch.created_at) }} · {{ formatTime(selectedBatch.created_at) }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Logged by {{ selectedBatch.logged_by ?? 'Unknown' }}</p>
                                    </div>
                                    <p class="text-2xl font-extrabold text-emerald-700">{{ selectedBatch.total_quantity?.toLocaleString() }} pcs</p>
                                </div>
                            </div>
                            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div
                                    v-for="(item, idx) in selectedBatch.items"
                                    :key="`${selectedBatch.batch_reference}-${idx}`"
                                    class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-3"
                                >
                                    <div class="text-sm font-semibold text-gray-800">{{ item.egg_size }}</div>
                                    <div class="text-base font-bold text-gray-900">{{ item.quantity?.toLocaleString() }} pcs</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="h-full flex items-center justify-center text-sm text-gray-500 bg-gray-50 border border-dashed border-gray-200 rounded-2xl p-6">
                            Select a batch on the left to see details.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>