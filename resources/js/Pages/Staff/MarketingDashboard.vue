<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    totalEggInventory: Number,
    salesToday: Number,
    expensesToday: Number,
    recentActivities: Array,
});
</script>

<template>
    <Head title="Marketing Dashboard" />

    <StaffLayout>
        <template #header>Marketing Dashboard</template>

        <div class="space-y-6">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h4 class="text-sm font-medium text-gray-500">Sales Today</h4>
                    <p class="text-3xl font-bold text-gray-800 mt-2">₱{{ salesToday.toFixed(2) }}</p>
                </div>
                 <div class="bg-white p-6 rounded-lg shadow">
                    <h4 class="text-sm font-medium text-gray-500">My Expenses Today</h4>
                    <p class="text-3xl font-bold text-gray-800 mt-2">₱{{ expensesToday.toFixed(2) }}</p>
                </div>
                 <div class="bg-white p-6 rounded-lg shadow">
                    <h4 class="text-sm font-medium text-gray-500">Total Egg Inventory</h4>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ totalEggInventory.toLocaleString() }} pcs</p>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">My Recent Sales Activity</h3>
                <table class="min-w-full">
                    <tbody>
                        <tr v-for="activity in recentActivities" :key="activity.id" class="border-b">
                            <td class="py-3 text-gray-500">{{ new Date(activity.created_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit'}) }}</td>
                            <td class="py-3">
                                <span class="bg-purple-100 text-purple-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full">Sale</span>
                            </td>
                            <td class="py-3 text-gray-800">Processed sale with a total of:</td>
                            <td class="py-3 font-semibold text-gray-800 text-right">₱{{ parseFloat(activity.total_amount).toFixed(2) }}</td>
                        </tr>
                        <tr v-if="recentActivities.length === 0">
                            <td colspan="4" class="py-4 text-center text-gray-500">No sales recorded yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </StaffLayout>
</template>