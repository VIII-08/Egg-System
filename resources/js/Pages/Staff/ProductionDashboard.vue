<script setup>
import { computed, ref } from 'vue';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    totalEggInventory: Number,
    eggsCollectedToday: Number,
    brokenEggsToday: Number,
    expensesToday: Number,
    currentFeedStock: Number,
    recentActivities: Array,
    todayProductionBatches: Array,
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
    <Head title="Staff Dashboard" />

    <StaffLayout>
        <!-- Page Header -->
        <template #header>
            Staff Dashboard
        </template>

        <!-- Main Content Grid -->
        <div class="space-y-6">
            <!-- Top Action Buttons -->
            

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <button
                    type="button"
                    class="bg-white p-6 rounded-lg shadow text-left focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition hover:shadow-md"
                    :class="{'opacity-70 cursor-not-allowed': !productionBatches.length && eggsCollectedToday > 0, 'cursor-pointer': productionBatches.length > 0}"
                    :disabled="!productionBatches.length"
                    @click="openBatchModal"
                >
                    <h4 class="text-sm font-medium text-gray-500 flex items-center justify-between">
                        <span>Eggs I Collected Today</span>
                        <span v-if="productionBatches.length" class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded-full">
                            View batches
                        </span>
                    </h4>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ eggsCollectedToday.toLocaleString() }} pcs</p>
                    <p class="text-xs text-emerald-600 mt-1 font-medium" v-if="productionBatches.length">
                        Tap to see {{ productionBatches.length }} batch(es)
                    </p>
                    <p class="text-xs text-gray-400 mt-1" v-else-if="eggsCollectedToday > 0">
                        Refresh page to view batches
                    </p>
                    <p class="text-xs text-gray-400 mt-1" v-else>
                        No batches logged yet
                    </p>
                </button>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-red-500 font-medium uppercase text-sm">Damaged Eggs Today</h3>
                <p class="text-4xl font-semibold text-red-700 mt-2">{{ brokenEggsToday }} Eggs</p>
            </div>
                 <div class="bg-white p-6 rounded-lg shadow">
                    <h4 class="text-sm font-medium text-gray-500">Expenses Today</h4>
                    <p class="text-3xl font-bold text-gray-800 mt-2">₱{{ expensesToday.toFixed(2) }}</p>
                </div>
                 <div class="bg-white p-6 rounded-lg shadow">
                    <h4 class="text-sm font-medium text-gray-500">Total Egg Inventory</h4>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ totalEggInventory.toLocaleString() }} pcs</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h4 class="text-sm font-medium text-gray-500">Current Feed Stock</h4>
                    <p class="text-3xl font-bold text-green-700 mt-2">{{ (currentFeedStock ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} <span class="text-lg text-gray-600">kg</span></p>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <div class="bg-white p-6 rounded-lg shadow md:col-span-3 lg:col-span-1 bg-white">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">My Recent Activity</h3>
                <table class="min-w-full">
                    <tbody>
                        <tr v-for="activity in recentActivities" :key="activity.id" class="border-b">
                            <td class="py-3 text-gray-500">{{ new Date(activity.created_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit'}) }}</td>
                            <td class="py-3">
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full">Production</span>
                            </td>
                            <td class="py-3 text-gray-800">Collected {{ activity.egg_product.name }}</td>
                            <td class="py-3 font-semibold text-gray-800 text-right">{{ activity.quantity }} pcs</td>
                        </tr>
                        <tr v-if="recentActivities.length === 0">
                            <td colspan="4" class="py-4 text-center text-gray-500">No activity recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
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
                        <h3 class="text-xl font-bold text-gray-900">My Egg Production Batches</h3>
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
                                <p class="text-xs text-gray-500">Logged by {{ batch.logged_by ?? 'Me' }}</p>
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
                                        <p class="text-xs text-gray-500 mt-0.5">Logged by {{ selectedBatch.logged_by ?? 'Me' }}</p>
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
    </StaffLayout>
</template>