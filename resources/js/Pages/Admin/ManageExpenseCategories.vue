<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    selectedRole: {
        type: String,
        default: 'all',
    },
});

const showAddForm = ref(false);

const addForm = useForm({
    category: '',
    role: props.selectedRole !== 'all' ? props.selectedRole : 'staff-production',
});

const submitAdd = () => {
    addForm.post(route('admin.expense-categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Category Added!',
                text: 'The expense category has been added successfully.',
                timer: 2000,
                showConfirmButton: false,
            });
            addForm.reset();
            showAddForm.value = false;
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to add category. Please check the form for errors.',
            });
        },
    });
};

const confirmDelete = (category) => {
    Swal.fire({
        icon: 'warning',
        title: 'Delete Category?',
        text: `Are you sure you want to delete "${category.name}"? This action cannot be undone.`,
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            submitDelete(category);
        }
    });
};


const submitDelete = (category) => {
    const deleteForm = useForm({});
    deleteForm.delete(route('admin.expense-categories.destroy', category.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Category Deleted!',
                text: 'The expense category has been deleted successfully.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Delete',
                text: 'This category cannot be deleted because it is currently in use.',
            });
        },
    });
};
</script>

<template>
    <Head title="Manage Expense Categories" />
    <AdminLayout>
        <template #header>Manage Expense Categories</template>

        <div class="space-y-6">
            <!-- Role Filter -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">Filter by Role:</label>
                    <select
                        :value="selectedRole"
                        @change="$inertia.get(route('admin.expense-categories.index'), { role: $event.target.value }, { preserveState: true })"
                        class="rounded-md border-gray-300"
                    >
                        <option value="all">All Roles</option>
                        <option value="staff-production">Production Staff</option>
                        <option value="staff-marketing">Marketing Staff</option>
                    </select>
                </div>
            </div>

            <!-- Add Category Section -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Expense Categories</h2>
                    <button
                        @click="showAddForm = !showAddForm"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        {{ showAddForm ? 'Cancel' : '+ Add Category' }}
                    </button>
                </div>

                <form v-if="showAddForm" @submit.prevent="submitAdd" class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category Name</label>
                            <input
                                v-model="addForm.category"
                                type="text"
                                class="w-full rounded-md border-gray-300"
                                placeholder="e.g., Feeds, Electricity"
                                required
                            />
                            <div v-if="addForm.errors.category" class="text-red-600 text-sm mt-1">
                                {{ addForm.errors.category }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">For Role</label>
                            <select
                                v-model="addForm.role"
                                class="w-full rounded-md border-gray-300"
                                required
                            >
                                <option value="staff-production">Production Staff</option>
                                <option value="staff-marketing">Marketing Staff</option>
                            </select>
                            <div v-if="addForm.errors.role" class="text-red-600 text-sm mt-1">
                                {{ addForm.errors.role }}
                            </div>
                        </div>
                        <div class="flex items-end">
                            <button
                                type="submit"
                                :disabled="addForm.processing"
                                class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                            >
                                Add
                            </button>
                        </div>
                    </div>
                </form>

            </div>

            <!-- Categories List -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Existing Categories</h3>
                
                <div v-if="categories.length === 0" class="text-center py-8 text-gray-500">
                    No expense categories found. Add one above to get started.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Category Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Times Used</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="category in categories" :key="category.id">
                                <td class="px-4 py-3 text-gray-800 font-medium">{{ category.name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ category.usage_count }} expense(s)</td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        @click="confirmDelete(category)"
                                        :disabled="category.usage_count > 0"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

