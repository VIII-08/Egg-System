<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const editingProduct = ref(null);
const showAddForm = ref(false);

const addForm = useForm({
    name: '',
    price: '',
    description: '',
    stock_quantity: 0,
});

const editForm = useForm({
    name: '',
    price: '',
    description: '',
});

const startEdit = (product) => {
    editingProduct.value = product.id;
    editForm.name = product.name;
    editForm.price = product.price;
    editForm.description = product.description || '';
};

const cancelEdit = () => {
    editingProduct.value = null;
    editForm.reset();
};

const submitAdd = () => {
    addForm.post(route('admin.egg-products.store'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Product Added!',
                text: 'The new egg product has been created successfully.',
                timer: 2000,
                showConfirmButton: false,
            });
            addForm.reset();
            showAddForm.value = false;
        },
        onError: (errors) => {
            const msg = errors.name || errors.price || errors.stock_quantity || errors.description;
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: Array.isArray(msg) ? msg[0] : msg || 'Failed to create product. Please check your input.',
            });
        },
    });
};

const submitEdit = (productId) => {
    editForm.put(route('admin.egg-products.update', productId), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Product Updated!',
                text: 'The egg product has been updated successfully.',
                timer: 2000,
                showConfirmButton: false,
            });
            editingProduct.value = null;
            editForm.reset();
        },
        onError: (errors) => {
            const msg = errors.name || errors.price || errors.stock_quantity || errors.description;
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: Array.isArray(msg) ? msg[0] : msg || 'Failed to update product. Please check your input.',
            });
        },
    });
};

const confirmDelete = (product) => {
    Swal.fire({
        icon: 'warning',
        title: 'Delete Product?',
        text: `Are you sure you want to delete "${product.name}"? This action cannot be undone.`,
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            submitDelete(product);
        }
    });
};

const submitDelete = (product) => {
    const deleteForm = useForm({});
    deleteForm.delete(route('admin.egg-products.destroy', product.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Product Deleted!',
                text: 'The egg product has been deleted successfully.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Cannot Delete',
                text: 'This product cannot be deleted because it has been used in sales.',
            });
        },
    });
};
</script>

<template>
    <Head title="Manage Egg Products" />
    <AdminLayout>
        <template #header>Manage Egg Products</template>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Egg Products</h2>
                    <button
                        @click="showAddForm = !showAddForm"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        {{ showAddForm ? 'Cancel' : '+ Add New Egg Size' }}
                    </button>
                </div>

                <!-- Add New Product Form -->
                <form v-if="showAddForm" @submit.prevent="submitAdd" class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Add New Egg Size</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Egg Size Name *</label>
                            <input
                                v-model="addForm.name"
                                type="text"
                                class="w-full rounded-md border-gray-300"
                                placeholder="e.g., Extra Large, Premium"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price (₱) *</label>
                            <input
                                v-model="addForm.price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-md border-gray-300"
                                placeholder="0.00"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Initial Stock Quantity</label>
                            <input
                                v-model="addForm.stock_quantity"
                                type="number"
                                min="0"
                                class="w-full rounded-md border-gray-300"
                                placeholder="0"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input
                                v-model="addForm.description"
                                type="text"
                                class="w-full rounded-md border-gray-300"
                                placeholder="Optional description"
                            />
                        </div>
                    </div>
                    <div class="mt-4">
                        <button
                            type="submit"
                            :disabled="addForm.processing"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                        >
                            Create Product
                        </button>
                    </div>
                </form>

                <div v-if="products.length === 0" class="text-center py-8 text-gray-500">
                    No egg products found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Price</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Stock</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Times Used</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="product in products" :key="product.id">
                                <td class="px-4 py-3">
                                    <div v-if="editingProduct !== product.id" class="font-medium text-gray-800">
                                        {{ product.name }}
                                    </div>
                                    <input
                                        v-else
                                        v-model="editForm.name"
                                        type="text"
                                        class="w-full rounded-md border-gray-300"
                                        required
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="editingProduct !== product.id" class="text-gray-600">
                                        ₱{{ parseFloat(product.price).toFixed(2) }}
                                    </div>
                                    <input
                                        v-else
                                        v-model="editForm.price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded-md border-gray-300"
                                        required
                                    />
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ product.stock_quantity }} pcs
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ product.usage_count }} sale(s)
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div v-if="editingProduct !== product.id" class="flex gap-2 justify-end">
                                        <button
                                            @click="startEdit(product)"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="confirmDelete(product)"
                                            :disabled="product.usage_count > 0"
                                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                    <div v-else class="flex gap-2 justify-end">
                                        <button
                                            @click="submitEdit(product.id)"
                                            :disabled="editForm.processing"
                                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                                        >
                                            Save
                                        </button>
                                        <button
                                            @click="cancelEdit"
                                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

