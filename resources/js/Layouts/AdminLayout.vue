<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
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
        :href="route('admin.dashboard')" 
        :class="{'bg-green-600 text-white': route().current('admin.dashboard')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        <span class="mx-4 text-lg font-semibold">Dashboard</span>
    </Link>
    
    <!-- Sales Forecasting Link -->
    <Link 
    :href="route('admin.forecasting.index')"
    :class="{ 'bg-green-600 text-white': route().current('admin.forecasting.index') }"
    class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
>
    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
    </svg>
    <span class="mx-4 text-lg font-semibold">Sales Forecasting</span>
</Link>

    <!-- Manage Expense Categories Link -->
    <Link  
        :href="route('admin.expense-categories.index')"
        :class="{'bg-green-600 text-white': route().current('admin.expense-categories.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
    >
         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
        </svg>
        <span class="mx-4 text-lg font-semibold">Expense Categories</span>
    </Link>

    <!-- Manage Egg Products Link -->
    <Link  
        :href="route('admin.egg-products.index')"
        :class="{'bg-green-600 text-white': route().current('admin.egg-products.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
    >
         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
        </svg>
        <span class="mx-4 text-lg font-semibold">Egg Products</span>
    </Link>

    <!-- Generate Reports Link -->
    <Link  
        :href="route('admin.reports.index')"
        :class="{'bg-green-600 text-white': route().current('admin.reports.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
    >
         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <span class="mx-4 text-lg font-semibold">Generate Reports</span>
    </Link>
    
    <!-- View Records Link -->
    <Link  
        :href="route('admin.records.index')"
        :class="{'bg-green-600 text-white': route().current('admin.records.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
    >
         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        <span class="mx-4 text-lg font-semibold">View Records</span>
    </Link>

    <!-- Manage Users Link -->
    <Link  
        :href="route('admin.users.index')"
          :class="{'bg-green-600 text-white': route().current('admin.users.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
    >
         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197M15 21a6 6 0 004.773-9.773"></path></svg>
        <span class="mx-4 text-lg font-semibold">Manage Users</span>
    </Link>

    <!-- Approvals Link -->
    <Link  
        :href="route('admin.approvals.index')" 
        :class="{'bg-green-600 text-white': route().current('admin.approvals.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
    >
         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
         <span class="mx-4 text-lg font-semibold">Approvals</span>
         <span v-if="$page.props.pendingApprovalsCount > 0" class="ml-auto bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded-full">{{ $page.props.pendingApprovalsCount }}</span>
    </Link>

    <!-- Audit Logs Link -->
    <Link  
        :href="route('admin.audit-logs.index')" 
        :class="{'bg-green-600 text-white': route().current('admin.audit-logs.index')}"
        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
    >
         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        <span class="mx-4 text-lg font-semibold">Audit Logs</span>
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
                 <div class="flex items-center">
                    <h1 class="text-3xl font-bold text-gray-800"> <!-- Change: Header size -->
                        <slot name="header" />
                    </h1>
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