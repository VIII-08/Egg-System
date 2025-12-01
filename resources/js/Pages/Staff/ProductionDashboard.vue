<script setup>
import StaffLayout from '@/Layouts/StaffLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    totalEggInventory: Number,
    eggsCollectedToday: Number,
    brokenEggsToday: Number,
    expensesToday: Number,
    recentActivities: Array,
});


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
                <div class="bg-white p-6 rounded-lg shadow">
                    <h4 class="text-sm font-medium text-gray-500">Eggs I Collected Today</h4>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ eggsCollectedToday.toLocaleString() }} pcs</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-red-500 font-medium uppercase text-sm">Broken Eggs Today</h3>
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
    </StaffLayout>
</template>