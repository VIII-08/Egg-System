<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const hasViewedCorrections = ref(false);
const newCorrectionsCount = ref(0);

// Check localStorage on mount and compare with current reviewed corrections count
onMounted(() => {
    if (typeof window !== 'undefined') {
        const lastViewedCount = parseInt(localStorage.getItem('staff_last_viewed_corrections_count') || '0', 10);
        const currentCount = page.props.reviewedCorrectionsCount || 0;
        
        // Calculate new corrections (difference between current and last viewed)
        const newCount = Math.max(0, currentCount - lastViewedCount);
        newCorrectionsCount.value = newCount;
        
        // If there are new corrections, show notification
        if (newCount > 0) {
            hasViewedCorrections.value = false;
        } else {
            // No new corrections - mark as viewed
            hasViewedCorrections.value = true;
        }
    }
});
</script>

<template>
    <div class="flex h-screen bg-gray-100 font-sans">
        <!-- Sidebar - Made wider and more spacious -->
        <aside class="w-90 flex-shrink-0 hidden md:block bg-gray-800 text-gray-300"> <!-- Change: Width w-64 to w-80 -->
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
                    <Link 
                        :href="page.props.auth.user.role === 'staff-production' ? route('production.dashboard') : route('marketing.dashboard')"
                        class="flex items-center mt-2 py-3 px-6 rounded-lg transition duration-200"
                        :class="{'bg-green-600 text-white': route().current('production.dashboard') || route().current('marketing.dashboard'),'hover:bg-gray-700': !route().current('production.dashboard') && !route().current('marketing.dashboard')}"
                    >
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 20V14H14V20H19V12H22L12 3L2 12H5V20H10Z" fill="currentColor"></path></svg> <!-- Change: Icon h-5 w-5 to h-6 w-6 -->
                        <span class="mx-4 text-lg font-semibold">My Dashboard</span> <!-- Change: Font size, weight, and margin -->
                    </Link>

                     

                    
                     <Link 
                        :href="page.props.auth.user.role === 'staff-production' ? route('production.forecasting') : route('marketing.forecasting')"
                        class="flex items-center mt-2 py-3 px-6 rounded-lg transition duration-200"
                        :class="{'bg-green-600 text-white': route().current('production.forecasting') || route().current('marketing.forecasting'),'hover:bg-gray-700': !route().current('production.forecasting') && !route().current('marketing.forecasting')}"
                    >
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 6H17.29L12 11.29L8.71 8L3 13.71L4.41 15.12L8.71 10.83L12 14.12L18.71 7.41L20 8.71V6H16Z" fill="currentColor"></path><path d="M5 20H19V8H21V20C21 21.1 20.1 22 19 22H5C3.9 22 3 21.1 3 20V4C3 2.9 3.9 2 5 2H13V4H5V20Z" fill="currentColor"></path></svg>
                        <span class="mx-4 text-lg font-semibold">Forecasting</span>
                    </Link>


                        <!-- MARKETING Links -->
                        <template v-if="page.props.auth.user.role === 'staff-marketing'">

                            <Link 
                            :href="route('sales.create')"
                            class="flex items-center mt-2 py-3 px-6 rounded-lg transition duration-200"
                            :class="{'bg-green-600 text-white': route().current('sales.create')}"
                            >
                            <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM17 13H13V17H11V13H7V11H11V7H13V11H17V13Z" fill="currentColor"/>
                                </svg>
                            <span class="mx-4 text-lg font-semibold">Record Sales</span>
                        </Link>
                        
                        </template>

                      <!-- PRODUCTION Links -->
                      <template v-if="page.props.auth.user.role === 'staff-production'">
                            <Link 
                                :href="route('production.logs.create')" 
                                class="flex items-center mt-2 py-3 px-6 rounded-lg transition duration-200"
                                :class="{'bg-green-600 text-white': route().current('production.logs.create'), 'hover:bg-gray-700': !route().current('production.logs.create') }">
                                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM17 13H13V17H11V13H7V11H11V7H13V11H17V13Z" fill="currentColor"/>
                                </svg>
                                <span class="mx-4 text-lg font-semibold">Log Egg Production</span>
                            </Link>

                            <Link 
                                :href="route('chicken.stock.index')" 
                                class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
                                :class="{'bg-green-600 text-white': route().current('chicken.stock.index')}"
                                >
                                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 9.54C22 9.17 21.82 8.84 21.54 8.65L20.25 7.78L20.5 6.13C20.57 5.67 20.24 5.23 19.79 5.1L18.23 4.5L17.5 3.03C17.27 2.53 16.74 2.24 16.2 2.24C16.03 2.24 15.86 2.27 15.7 2.33L14.1 2.9L12.87 2.1C12.44 1.84 11.9 2.05 11.66 2.47L10.97 3.65L9.5 3.68C9.04 3.69 8.63 4.09 8.63 4.56C8.63 4.71 8.66 4.87 8.71 5.03L9.13 6.4L7.54 6.94C7.09 7.08 6.78 7.55 6.89 8.01L7.26 9.59L6.5 10.88C6.35 11.13 6.3 11.41 6.36 11.69L6.6 13.3L5.41 14.16C5.16 14.34 5 14.65 5 14.96C5 15.3 5.16 15.63 5.41 15.82L6.7 16.69L6.44 18.34C6.38 18.8 6.7 19.24 7.16 19.37L8.71 19.97L9.45 21.44C9.69 21.94 10.22 22.23 10.75 22.23C10.92 22.23 11.1 22.2 11.25 22.14L12.85 21.57L14.07 22.38C14.5 22.64 15.05 22.43 15.28 22L15.98 20.82L17.45 20.79C17.91 20.79 18.31 20.38 18.31 19.92C18.31 19.76 18.28 19.61 18.23 19.45L17.82 18.07L19.4 17.53C19.85 17.39 20.17 16.92 20.06 16.46L19.69 14.88L20.44 13.59C20.6 13.34 20.65 13.06 20.59 12.78L20.36 11.17L21.54 10.32C21.8 10.13 22 9.85 22 9.54ZM12 16C9.79 16 8 14.21 8 12C8 9.79 9.79 8 12 8C14.21 8 16 9.79 16 12C16 14.21 14.21 16 12 16Z" fill="currentColor"></path></svg>
                                <span class="mx-4 text-lg font-semibold">Adjust Chicken Stock</span>
                            </Link>
                    </template>


                        <!-- SHARED Links -->
                    <Link 
                        :href="route('expenses.create')"
                        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
                        :class="{'bg-green-600 text-white': route().current('expenses.create')}"
                        >
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 19V4H5V19H19ZM19 2H5C3.9 2 3 2.9 3 4V20C3 21.1 3.9 22 5 22H19C20.1 22 21 21.1 21 20V4C21 2.9 20.1 2 19 2ZM13.88 12.88L12 11.5L10.12 12.88L10.88 10.62L9 9.25H11.23L12 7L12.77 9.25H15L13.12 10.62L13.88 12.88Z" fill="currentColor"></path></svg>
                        <span class="mx-4 text-lg font-semibold">Record an Expense</span>
                    </Link>

                    <Link 
                        :href="route('records.index')"
                        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700"
                        :class="{'bg-green-600 text-white': route().current('records.index')}"
                        >
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 2C4.9 2 4 2.9 4 4V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V8L14 2H6Z" fill="currentColor"/>
                            <path d="M14 2V8H20" fill="currentColor"/>
                            </svg>
                            <span class="mx-4 text-lg font-semibold">View My Records</span>

                    </Link>

                     <Link 
                        :href="route('data-correction.create')" 
                        class="flex items-center mt-2 py-3 px-6 rounded-lg hover:bg-gray-700 relative"
                        :class="{'bg-green-600 text-white': route().current('data-correction.create')}"
                        >
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 7L22.41 8.41L13.41 17.41L9.41 13.41L8 14.82L13.41 20.24L22.83 10.82L24.24 12.24V7H21Z" fill="currentColor"></path><path d="M18 19H4V5H14.58L16.58 3H4C2.9 3 2 3.9 2 5V19C2 20.1 2.9 21 4 21H18C19.1 21 20 20.1 20 19V11.8L18 13.8V19Z" fill="currentColor"></path></svg>
                        <span class="mx-4 text-lg font-semibold">Request Data Correction</span>
                        <span v-if="newCorrectionsCount > 0" class="ml-auto px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">
                            {{ newCorrectionsCount }}
                        </span>
                    </Link>
                </nav>

                <!-- Logout Button -->
                <div class="mt-auto">
                    <Link
                        v-if="page.props.auth && page.props.auth.user"
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex w-full items-center py-4 px-8 rounded-xl text-gray-400 hover:bg-red-700 hover:text-white transition duration-200"
                    >
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            ></path>
                        </svg>
                        <span class="mx-4 text-xl font-semibold">Log Out</span>
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