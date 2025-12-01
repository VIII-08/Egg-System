<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const hasViewedFinancialReports = ref(false);
const newReportsCount = ref(0);

// Check localStorage on mount and compare with current reviewed reports count
onMounted(() => {
    if (typeof window !== 'undefined') {
        const lastViewedCount = parseInt(localStorage.getItem('treasurer_last_viewed_reports_count') || '0', 10);
        const currentCount = page.props.reviewedReportsCount || 0;
        
        // Calculate new reports (difference between current and last viewed)
        const newCount = Math.max(0, currentCount - lastViewedCount);
        newReportsCount.value = newCount;
        
        // If there are new reports, show notification
        if (newCount > 0) {
            hasViewedFinancialReports.value = false;
        } else {
            // No new reports - mark as viewed
            hasViewedFinancialReports.value = true;
        }
    }
});
</script>

<template>
    <div class="flex h-screen bg-gray-100 font-sans">
        <!-- Sidebar - Made wider and more spacious -->
        <aside class="w-80 flex-shrink-0 hidden md:block bg-gray-800 text-gray-300"> <!-- Change: Width w-64 to w-80 -->
            <div class="py-6 px-6 h-full flex flex-col">
                <!-- Logo and Title -->
                <div class="flex items-center space-x-4 mb-10">
                    <img src="/Image/logo.jpg" alt="United Farmers Association" class="h-12 w-auto"> <!-- Change: Logo h-10 to h-12 -->
                    <div>
                        <span class="text-2xl font-bold text-white">United Farmers</span> <!-- Change: Font size -->
                        <span class="block text-sm text-gray-400">Association</span>
                    </div>
                </div>

                <!-- Navigation Links - Larger text, icons, and padding -->
                <nav class="mt-8 flex-grow">
    <!-- Dashboard Link -->
    <Link
        :href="route('treasurer.dashboard')"
        class="flex items-center mt-2 py-3 px-6 rounded-lg transition duration-200"
        :class="{ 'bg-green-600 text-white': route().current('treasurer.dashboard'), 'hover:bg-gray-700': !route().current('treasurer.dashboard') }"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 20V14H14V20H19V12H22L12 3L2 12H5V20H10Z"></path></svg>
        <span class="mx-4 text-lg font-semibold">Dashboard</span>
    </Link>

     <!-- Forecasting Link -->
     <Link
        :href="route('treasurer.forecasting.index')"
        class="flex items-center mt-2 py-3 px-6 rounded-lg"
        :class="{ 'bg-green-600 text-white': route().current('treasurer.forecasting.index'), 'hover:bg-gray-700': !route().current('treasurer.forecasting.index') }"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
        <span class="mx-4 text-lg font-semibold">Forecasting</span>
    </Link>
    
    <!-- Manage Expense Categories Link -->
    <Link
        :href="route('treasurer.expense-categories.index')"
        class="flex items-center mt-2 py-3 px-6 rounded-lg"
        :class="{ 'bg-green-600 text-white': route().current('treasurer.expense-categories.index'), 'hover:bg-gray-700': !route().current('treasurer.expense-categories.index') }"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
        <span class="mx-4 text-lg font-semibold">Expense Categories</span>
    </Link>

    <!-- Manage Egg Products Link -->
    <Link
        :href="route('treasurer.egg-products.index')"
        class="flex items-center mt-2 py-3 px-6 rounded-lg"
        :class="{ 'bg-green-600 text-white': route().current('treasurer.egg-products.index'), 'hover:bg-gray-700': !route().current('treasurer.egg-products.index') }"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
        <span class="mx-4 text-lg font-semibold">Egg Products</span>
    </Link>

    <!-- View Specific Records Link -->
    <Link 
        :href="route('treasurer.records.index')" 
        :class="{'bg-green-600 text-white': route().current('treasurer.records.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700 relative">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        <span class="mx-4 text-lg font-semibold">View Specific Records</span>
        <span v-if="newReportsCount > 0" class="ml-auto px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">
            {{ newReportsCount }}
        </span>
    </Link>
    
    <!-- Generate Financial Report Link -->
    <Link  
        :href="route('treasurer.reports.index')" 
        :class="{'bg-green-600 text-white': route().current('treasurer.reports.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700">
         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        <span class="mx-4 text-lg font-semibold">Generate Financial Report</span>
    </Link>
</nav>

                <!-- Logout Button -->
                <div class="mt-auto">
                     <Link v-if="page.props.auth && page.props.auth.user" :href="route('logout')" method="post" as="button" class="flex w-full items-center py-3 px-6 rounded-lg text-gray-400 hover:bg-red-700 hover:text-white transition duration-200">
                       <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                       <span class="mx-4 text-lg font-semibold">Log Out</span>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="flex justify-between items-center p-6 bg-white border-b">
                 <div class="flex items-center flex-1">
                    <slot name="header">
                        <h1 class="text-3xl font-bold text-gray-800">
                            <!-- Default header if no slot content -->
                        </h1>
                    </slot>
                </div>

                <!-- User Profile -->
                <div v-if="page.props.auth && page.props.auth.user" class="flex items-center space-x-4">
                     <div class="h-12 w-12 rounded-full bg-gray-200 overflow-hidden">
                        <img 
                            :src="page.props.auth.user.profile_picture || `https://ui-avatars.com/api/?name=${page.props.auth.user.name.replace(' ', '+')}&background=a7c957&color=386641&size=128`" 
                            alt="User Avatar"
                            class="h-full w-full object-cover"
                        >
                     </div>
                    <div>
                        <h2 class="font-semibold text-gray-800">{{ page.props.auth.user.name }}</h2>
                        <p class="text-sm text-gray-500 capitalize">{{ page.props.auth.user.role.replace('-', ' ') }}</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-8"> <!-- Change: Padding p-6 to p-8 -->
                <slot />
            </main>
        </div>
    </div>
</template>