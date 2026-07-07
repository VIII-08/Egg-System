<script setup>
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    logs: Object, 
    users: Array, 
    actions: Array, 
    filters: Object
});

const filterForm = useForm({
    user_id: props.filters.user_id || 'all',
    action_type: props.filters.action_type || 'all',
    date: props.filters.date || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || '',
});

const showAdvancedFilters = ref(false);

// Watch for user_id changes and reload actions
watch(() => filterForm.user_id, (newUserId, oldUserId) => {
    if (newUserId !== oldUserId) {
        // Reset action_type when user changes (since available actions may differ)
        filterForm.action_type = null;
        // Reload to get updated actions list
        submitFilter();
    }
});

const submitFilter = () => {
    const queryParams = {
        user_id: filterForm.user_id === 'all' ? null : filterForm.user_id,
        action_type: filterForm.action_type === 'all' ? null : filterForm.action_type,
        date: filterForm.date,
        date_from: filterForm.date_from,
        date_to: filterForm.date_to,
        search: filterForm.search,
    };
    filterForm.get(route('admin.audit-logs.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.user_id = 'all';
    filterForm.action_type = 'all';
    filterForm.date = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    filterForm.search = '';
    submitFilter();
};

const getActionIcon = (action) => {
    const iconMap = {
        'user_login': '🔐',
        'user_logout': '🚪',
        'user_created': '👤',
        'user_updated': '✏️',
        'user_deleted': '🗑️',
        'sale_created': '💰',
        'sale_updated': '📝',
        'sale_deleted': '❌',
        'expense_created': '💸',
        'expense_updated': '📝',
        'expense_deleted': '❌',
        'production_logged': '🥚',
        'production_log_updated': '📝',
        'production_log_deleted': '❌',
        'egg_product_created': '➕',
        'egg_product_updated': '✏️',
        'egg_product_deleted': '🗑️',
        'expense_category_created': '📁',
        'expense_category_updated': '✏️',
        'expense_category_deleted': '🗑️',
        'chicken_stock_adjusted': '🐔',
        'chicken_stock_log_updated': '📝',
        'chicken_stock_log_deleted': '❌',
        'correction_request_created': '📋',
        'correction_request_approved': '✅',
        'correction_request_rejected': '❌',
        'financial_report_submitted': '📊',
        'financial_report_approved': '✅',
        'financial_report_rejected': '❌',
    };
    return iconMap[action] || '📝';
};

const getActionColor = (action) => {
    if (action.includes('created') || action.includes('login') || action.includes('approved') || action.includes('submitted')) {
        return 'bg-green-100 text-green-800';
    }
    if (action.includes('updated')) {
        return 'bg-blue-100 text-blue-800';
    }
    if (action.includes('deleted') || action.includes('rejected') || action.includes('logout')) {
        return 'bg-red-100 text-red-800';
    }
    return 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Audit Logs" />
    <AdminLayout>
        <template #header>Audit Logs</template>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">System Activity Log</h2>
                <p class="text-gray-600">A comprehensive chronological record of all important actions performed within the system.</p>
            </div>

            <!-- Filters -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-white-700">Filters</h3>
                    <div class="flex items-center gap-3">
                        <button
                            @click="clearFilters"
                            class="px-4 py-2 bg-green-600 text-white-700 rounded-md hover:bg-gray-300 text-sm"
                        >
                            Clear Filters
                        </button>
                        <button
                            @click="showAdvancedFilters = !showAdvancedFilters"
                            class="text-sm text-green-600 hover:text-green-800"
                        >
                            {{ showAdvancedFilters ? 'Hide' : 'Show' }} Advanced Filters
                        </button>
                    </div>
                </div>
                
                <form @submit.prevent="submitFilter" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                        <select
                            v-model="filterForm.user_id"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                        >
                            <option value="all">All Users</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.role?.replace('-', ' ') || '' }})
                            </option>
                        </select>
                        <p v-if="filterForm.user_id && filterForm.user_id !== 'all'" class="text-xs text-gray-500 mt-1">
                            Showing actions available for selected user's role
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                        <select
                            v-model="filterForm.action_type"
                            @change="submitFilter"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            :disabled="filterForm.user_id && filterForm.user_id !== 'all' && actions.length === 0"
                        >
                            <option value="all">All Actions</option>
                            <option v-for="action in actions" :key="action" :value="action">
                                {{ action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                            </option>
                        </select>
                        <p v-if="filterForm.user_id && filterForm.user_id !== 'all' && actions.length === 0" class="text-xs text-gray-500 mt-1">
                            No actions found for this user
                        </p>
                        <p v-else-if="filterForm.user_id && filterForm.user_id !== 'all'" class="text-xs text-gray-500 mt-1">
                            {{ actions.length }} action(s) available
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input
                            v-model="filterForm.date"
                            @change="submitFilter"
                            type="date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                        />
                    </div>
            </form>

                <!-- Advanced Filters -->
                <div v-if="showAdvancedFilters" class="mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <input
                                v-model="filterForm.date_from"
                                @change="submitFilter"
                                type="date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <input
                                v-model="filterForm.date_to"
                                @change="submitFilter"
                                type="date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Display -->
            <div class="space-y-4">
                <template v-if="logs && logs.data && logs.data.length > 0">
                    <div
                        v-for="log in logs.data"
                        :key="log.id"
                        class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">{{ getActionIcon(log.action) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        :class="getActionColor(log.action)"
                                        class="px-2 py-1 rounded text-xs font-semibold"
                                    >
                                        {{ log.action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                    </span>
                                    <span class="text-sm text-gray-500">{{ formatDate(log.created_at) }}</span>
                                </div>
                                <p
                                    class="text-gray-800 text-base mb-2"
                                    v-html="log.log_entry.replace(/`([^`]+)`/g, '<strong class=\'font-semibold text-blue-600\'>$1</strong>')"
                                ></p>
                                <div class="flex flex-wrap gap-4 text-xs text-gray-500">
                                    <span v-if="log.user">
                                        👤 {{ log.user.name }} ({{ log.user.email }})
                                    </span>
                                    <span v-if="log.ip_address">
                                        🌐 {{ log.ip_address }}
                                    </span>
                                    <span v-if="log.user_agent" class="truncate max-w-xs" :title="log.user_agent">
                                        💻 {{ log.user_agent.substring(0, 50) }}{{ log.user_agent.length > 50 ? '...' : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                
                <div v-else class="text-center py-12">
                     <p class="text-xl font-semibold text-gray-500">No Audit Logs Found</p>
                     <p class="text-gray-400 mt-2">No activity matching your criteria was found in the system.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logs && logs.links && logs.links.length > 3" class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} results
                </div>
                <div class="flex gap-2">
                    <Link
                        v-for="link in logs.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-2 rounded-md text-sm font-medium',
                            link.active
                                ? 'bg-green-600 text-white'
                                : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                        ]"
                        v-html="link.label"
                    ></Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
