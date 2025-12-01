<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BaseChart from '@/Components/BaseChart.vue';

const props = defineProps({
    eggProducts: Array,
    forecastData: Object,
    filters: Object,
});

const isLoading = ref(false);

// The form holds the user's selections
const form = useForm({
    product_id: props.filters.product_id,
    horizon: props.filters.horizon,
});

watch(
    () => props.filters,
    (newFilters) => {
        form.product_id = newFilters.product_id;
        form.horizon = newFilters.horizon;
    },
    { deep: true }
);
const submitFilters = () => {
    form.get(route('admin.forecasting.index'), {
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            isLoading.value = true;
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

// React immediately when user changes filters
watch(
    [() => form.product_id, () => form.horizon],
    () => {
        submitFilters();
    }
);


// This computed property prepares the data perfectly for Chart.js
const salesChartConfig = computed(() => {
    if (!props.forecastData) return null;
    
    const forecastLabel = props.forecastData.model
        ? `Forecasted Sales (${props.forecastData.model})`
        : 'Forecasted Sales';

    return {
        labels: props.forecastData.chartLabels,
        datasets: [
            {
                label: 'Historical Daily Sales',
                backgroundColor: 'rgba(22, 163, 74, 0.2)',
                borderColor: '#16A34A',
                tension: 0.2,
                data: props.forecastData.historicalData,
                fill: true,
            },
            {
                label: forecastLabel,
                borderColor: '#F97316', // Orange color for forecast
                borderDash: [5, 5], // Make the line dashed
                data: props.forecastData.forecastData,
                tension: 0.2,
            },
        ],
    };
});

// Helper for the selected product name
const selectedProductName = computed(() => {
    return props.eggProducts.find(p => p.id === form.product_id)?.name || '';
});

</script>

<template>
    <Head title="Interactive Sales Forecasting" />

    <AdminLayout>
        <template #header>Interactive Sales Forecasting</template>

        <div class="space-y-6">
            <!-- Control Panel -->
            <div class="bg-white p-6 rounded-lg shadow space-y-4">
                <h2 class="text-xl font-bold">Forecasting Options</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="product_id" class="font-medium">1. Select Egg Size to Forecast:</label>
                        <select v-model="form.product_id" id="product_id" class="mt-1 block w-full text-base rounded-md border-gray-300">
                            <option v-for="product in eggProducts" :key="product.id" :value="product.id">{{ product.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-medium">2. Select Forecast Period:</label>
                        <div class="mt-2 flex space-x-4">
                            <label class="flex items-center"><input type="radio" v-model="form.horizon" :value="7" name="horizon" class="form-radio"> <span class="ml-2">Next 7 Days</span></label>
                            <label class="flex items-center"><input type="radio" v-model="form.horizon" :value="14" name="horizon" class="form-radio"> <span class="ml-2">Next 14 Days</span></label>
                            <label class="flex items-center"><input type="radio" v-model="form.horizon" :value="30" name="horizon" class="form-radio"> <span class="ml-2">Next 30 Days</span></label>
                        </div>
                    </div>
                </div>
                <p v-if="isLoading" class="text-sm text-gray-400">Updating forecast...</p>
            </div>

            <!-- Results Section -->
            <div v-if="forecastData" class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- KPI Card -->
                <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow text-center">
                    <h4 class="text-sm font-medium text-gray-500 uppercase">Total Predicted Sales for</h4>
                    <h3 class="text-2xl font-bold my-2 text-blue-600">{{ selectedProductName }}</h3>
                    <p class="text-6xl font-bold text-gray-800">
                        {{ forecastData.totalPredictedSales }}
                    </p>
                    <p class="text-gray-600">pcs over the next {{ filters.horizon }} days</p>
                    <div class="mt-4 pt-4 border-t text-sm text-gray-500 space-y-2">
                        <p>
                            Based on <strong class="text-gray-700">{{ forecastData.model }}</strong> projections averaging
                            <strong class="text-gray-700">{{ forecastData.dailyAverage }}</strong> pcs/day.
                        </p>
                        <p class="text-xs">
                            Model run: <strong class="text-gray-700">{{ forecastData.modelGeneratedAt || 'N/A' }}</strong><br>
                            Training data through: <strong class="text-gray-700">{{ forecastData.modelTrainingCutoff || 'N/A' }}</strong>
                        </p>
                        <p class="text-xs">
                            Last recorded sale: <strong class="text-gray-700">{{ forecastData.lastSaleDate }}</strong>
                        </p>
                        <div v-if="forecastData.metrics" class="pt-2 border-t text-xs text-gray-500">
                            <p class="font-semibold text-gray-600">Model Accuracy</p>
                            <p>MAE: <strong class="text-gray-700">{{ forecastData.metrics.mae ?? 'N/A' }}</strong></p>
                            <p>RMSE: <strong class="text-gray-700">{{ forecastData.metrics.rmse ?? 'N/A' }}</strong></p>
                            <p>Evaluation points: <strong class="text-gray-700">{{ forecastData.metrics.test_points ?? 'N/A' }}</strong></p>
                        </div>
                    </div>
                </div>

                <!-- Main Chart -->
                <div class="lg:col-span-3 bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-800">Historical Data and Sales Forecast</h3>
                    <div class="h-96 mt-4">
                        <BaseChart v-if="salesChartConfig" chartType="line" :chartData="salesChartConfig" />
                    </div>
                </div>
            </div>
            
            <div v-else class="text-center p-12 bg-gray-50 rounded-lg">
                <p class="text-xl text-gray-500">Not enough sales data to generate a forecast.</p>
            </div>

        </div>
    </AdminLayout>
</template>