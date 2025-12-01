<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue'; // We'll re-use Breeze's modal
import Swal from 'sweetalert2';

const props = defineProps({
    users: Array,
});

// State to control the modal
const showModal = ref(false);
const isEditMode = ref(false);
const selectedUser = ref(null);

const form = useForm({
    id: '', name: '', email: '', role: 'staff-production', 
    password: '', password_confirmation: '', is_active: true,
    profile_picture: null,
});

const profilePicturePreview = ref(null);
const profilePictureFile = ref(null);

const openAddModal = () => {
    form.reset();
    isEditMode.value = false;
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    selectedUser.value = user;
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.is_active = user.is_active;
    form.password = '';
    form.password_confirmation = '';
    form.profile_picture = null;
    profilePictureFile.value = null;
    profilePicturePreview.value = user.profile_picture 
        ? user.profile_picture 
        : `https://ui-avatars.com/api/?name=${user.name.replace(' ','+')}&background=random`;
    showModal.value = true;
};

const closeModal = () => { 
    showModal.value = false; 
    form.reset(); 
    profilePicturePreview.value = null;
    profilePictureFile.value = null;
};

const handleProfilePictureChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        profilePictureFile.value = file;
        form.profile_picture = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            profilePicturePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const saveUser = () => {
    const commonOptions = {
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: `User ${isEditMode.value ? 'Updated' : 'Created'}!`,
                text: `The user account has been saved successfully.`,
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: () => {
             // We don't need a Swal error here because the individual error messages will appear.
        },
        preserveScroll: false, // Force page refresh to show updated profile picture
    };
    
    if (isEditMode.value) {
        // For file uploads with PUT, we need to use POST with _method override
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('admin.users.update', form.id), commonOptions);
    } else {
        form.post(route('admin.users.store'), commonOptions);
    }
};

const deleteUser = (user) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete the user: ${user.name}. You won't be able to revert this!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
             useForm({}).delete(route('admin.users.destroy', user.id), {
                 onSuccess: () => {
                     Swal.fire('Deleted!', 'The user has been deleted.', 'success');
                 }
             });
        }
    });
};

// Determine user status based on active session, last_login_at and is_active
const getUserStatus = (user) => {
    if (!user.is_active) {
        return { text: 'Disabled', class: 'bg-gray-100 text-gray-800' };
    }
    
    // Check if user has an active session (is currently logged in)
    if (user.is_online) {
        return { text: 'Online', class: 'bg-green-100 text-green-800' };
    }
    
    if (!user.last_login_at) {
        return { text: 'Never Logged In', class: 'bg-yellow-100 text-yellow-800' };
    }
    
    // User is not online but has logged in before
    return { text: 'Offline', class: 'bg-red-100 text-red-800' };
};
</script>

<template>
    <Head title="Manage User Accounts" />
    <AdminLayout>
        <template #header>Manage User Accounts</template>
        
        <div class="flex justify-between items-center mb-6">
             <div><h2 class="text-xl font-bold">User List</h2><p class="text-gray-600">Add, edit, or deactivate user accounts for the system.</p></div>
             <PrimaryButton @click="openAddModal">Add New User</PrimaryButton>
        </div>
        
        <!-- User Table -->
        <div class="bg-white rounded-lg shadow"><div class="overflow-x-auto"><table class="w-full text-lg">
            <thead class="bg-gray-50"><tr><th class="p-4 text-left">User Info</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                 <tr v-for="user in users" :key="user.id" class="border-b">
                     <td class="p-4 flex items-center space-x-3">
                         <img 
                             :src="user.profile_picture || `https://ui-avatars.com/api/?name=${user.name.replace(' ','+')}&background=random`" 
                             class="h-12 w-12 rounded-full object-cover border-2 border-gray-200"
                             :alt="user.name"
                             @error="$event.target.src = `https://ui-avatars.com/api/?name=${user.name.replace(' ','+')}&background=random`"
                         >
                         <div><div class="font-bold">{{ user.name }}</div><div class="text-sm text-gray-500">{{ user.email }}</div></div>
                     </td>
                     <td class="text-center">{{ user.role.replace('-', ' ') }}</td>
                     <td class="text-center">{{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}</td>
                     <td class="text-center">
                         <span class="px-2 py-1 text-xs rounded-full" :class="getUserStatus(user).class">
                             {{ getUserStatus(user).text }}
                         </span>
                     </td>
                     <td class="text-center space-x-2">
                        <button @click="openEditModal(user)">✏️</button>
                        <button 
                            v-if="user.role !== 'admin'" 
                            @click="deleteUser(user)" 
                            class="text-red-500"
                        >🗑️</button>
                        <span v-else class="text-gray-400 text-xs" title="Cannot delete admin account">🔒</span>
                     </td>
                 </tr>
            </tbody>
        </table></div></div>
        
        <!-- Add/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-t-lg">
                    <h2 class="text-2xl font-bold text-white">
                        {{ isEditMode ? 'Edit User Account' : 'Add New User Account' }}
                    </h2>
                    <p class="text-green-100 text-sm mt-1">
                        {{ isEditMode ? 'Update user information and account settings' : 'Create a new user account for the system' }}
                    </p>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <form @submit.prevent="saveUser" class="space-y-6">
                        <!-- Full Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                placeholder="Enter full name"
                                required
                            >
                            <div v-if="form.errors.name" class="text-sm text-red-600 mt-1 flex items-center">
                                <span class="mr-1">⚠️</span> {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="form.email" 
                                type="email" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                placeholder="user@example.com"
                                required
                            >
                            <div v-if="form.errors.email" class="text-sm text-red-600 mt-1 flex items-center">
                                <span class="mr-1">⚠️</span> {{ form.errors.email }}
                            </div>
                        </div>

                        <!-- Role and Status Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Assign Role -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Assign Role <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    v-model="form.role" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition bg-white"
                                    :disabled="isEditMode && selectedUser?.role === 'admin'"
                                >
                                    <option value="admin">Admin</option>
                                    <option value="staff-production">Staff Production</option>
                                    <option value="staff-marketing">Staff Marketing</option>
                                    <option value="treasurer">Treasurer</option>
                                </select>
                                <div v-if="form.errors.role" class="text-sm text-red-600 mt-1 flex items-center">
                                    <span class="mr-1">⚠️</span> {{ form.errors.role }}
                                </div>
                            </div>

                            <!-- Status (Edit Mode Only) -->
                            <div v-if="isEditMode">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Account Status
                                </label>
                                <select 
                                    v-model="form.is_active" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition bg-white"
                                    :disabled="selectedUser?.role === 'admin'"
                                >
                                    <option :value="true">Active</option>
                                    <option :value="false">Inactive</option>
                                </select>
                                <p v-if="selectedUser?.role === 'admin'" class="text-xs text-gray-500 mt-1">Admin accounts cannot be deactivated</p>
                            </div>
                        </div>

                        <!-- Profile Picture (Edit Mode Only) -->
                        <div v-if="isEditMode">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Profile Picture
                            </label>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <img 
                                        :src="profilePicturePreview || (selectedUser?.profile_picture || `https://ui-avatars.com/api/?name=${form.name.replace(' ','+')}&background=random`)" 
                                        class="h-20 w-20 rounded-full object-cover border-2 border-gray-300"
                                        alt="Profile Preview"
                                    >
                                </div>
                                <div class="flex-1">
                                    <input 
                                        type="file" 
                                        @change="handleProfilePictureChange"
                                        accept="image/*"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition text-sm"
                                    >
                                    <p class="text-xs text-gray-500 mt-1">Accepted formats: JPEG, PNG, JPG, GIF. Max size: 2MB</p>
                                    <div v-if="form.errors.profile_picture" class="text-sm text-red-600 mt-1 flex items-center">
                                        <span class="mr-1">⚠️</span> {{ form.errors.profile_picture }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Fields Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ isEditMode ? 'New Password (optional)' : 'Password' }}
                                    <span v-if="!isEditMode" class="text-red-500">*</span>
                                </label>
                                <input 
                                    v-model="form.password" 
                                    type="password" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                    :placeholder="isEditMode ? 'Leave blank to keep current' : 'Enter password'"
                                    :required="!isEditMode"
                                >
                                <div v-if="form.errors.password" class="text-sm text-red-600 mt-1 flex items-center">
                                    <span class="mr-1">⚠️</span> {{ form.errors.password }}
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Confirm Password
                                    <span v-if="!isEditMode" class="text-red-500">*</span>
                                </label>
                                <input 
                                    v-model="form.password_confirmation" 
                                    type="password" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition"
                                    :placeholder="isEditMode ? 'Confirm new password' : 'Re-enter password'"
                                    :required="!isEditMode"
                                >
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button 
                                type="button" 
                                @click="closeModal"
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium"
                            >
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>{{ isEditMode ? 'Update User' : 'Create User' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Modal>

    </AdminLayout>
</template>