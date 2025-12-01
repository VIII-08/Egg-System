<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    logs: Object, 
    users: Array, 
    actions: Array, 
    filters: Object
});

const filterForm = useForm({
    user_id: props.filters.user_id || null,
    action_type: props.filters.action_type || null,
    date: props.filters.date || '',
});

const submitFilter = () => {
    // We replace 'all' with null so Laravel receives an empty value
    const queryParams = {
        user_id: filterForm.user_id === 'all' ? null : filterForm.user_id,
        action_type: filterForm.action_type === 'all' ? null : filterForm.action_type,
        date: filterForm.date,
    };
    useForm(queryParams).get(route('admin.audit-logs.index'));
};
</script>

<template>
    <Head title="Audit Logs" />
    <AdminLayout>
        <template #header>Audit Logs</template>

        <div class="bg-white p-6 rounded-lg shadow">
             <h2 class="text-xl font-bold">System Activity Log</h2>
             <p class="text-gray-600 mb-6">A chronological record of all important actions performed within the system.</p>
            
            <form @submit.prevent="submitFilter" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                 <div><label>Filter By User</label><select v-model="filterForm.user_id" @change="submitFilter" class="mt-1 w-full rounded"><option value="all">All Users</option><option v-for="user in users" :value="user.id">{{user.name}}</option></select></div>
                 <div><label>Filter By Action</label><select v-model="filterForm.action_type" @change="submitFilter" class="mt-1 w-full rounded"><option value="all">All Actions</option><option v-for="action in actions" :value="action">{{action}}</option></select></div>
                 <div><label>Filter By Date</label><input v-model="filterForm.date" @change="submitFilter" type="date" class="mt-1 w-full rounded"></div>
            </form>

            <!-- This is the corrected rendering block -->
            <div class="space-y-6">
                <!-- Check if logs and logs.data exist and have content -->
                <template v-if="logs && logs.data && logs.data.length > 0">
                    <div v-for="log in logs.data" :key="log.id" class="flex items-start space-x-4 border-b pb-4">
                        <span class="p-3 bg-gray-100 rounded-full mt-1">📝</span> <!-- Icon would be dynamic based on action type -->
                        <div>
                             <!-- The v-html is used here carefully. Ensure log_entry is always sanitized on the backend if it contains user input -->
                             <p class="text-gray-800 text-lg" v-html="log.log_entry.replace(/`([^`]+)`/g, '<strong class=\'font-semibold text-blue-600\'>$1</strong>')"></p>
                             <p class="text-sm text-gray-500">{{ new Date(log.created_at).toLocaleString() }}</p>
                        </div>
                    </div>
                </template>
                <!-- Show this message if there are no logs -->
                <div v-else class="text-center py-12">
                     <p class="text-xl font-semibold text-gray-500">No Audit Logs Found</p>
                     <p class="text-gray-400 mt-2">No activity matching your criteria was found in the system.</p>
                </div>
            </div>

            <!-- Pagination Links Here -->
        </div>
    </AdminLayout>
</template>