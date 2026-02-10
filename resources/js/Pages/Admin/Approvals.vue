<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    correctionRequests: {
        type: Array,
        default: () => [],
    },
    financialReports: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('corrections');
const showReviewModal = ref(false);
const showFullReportModal = ref(false);
const selectedRequest = ref(null);
const selectedReportForView = ref(null);
const pendingModalData = ref(null); // Store modal data when closing for alert

const approvalForm = useForm({
    type: '',
    action: '',
    admin_notes: '',
});

const openReviewModal = (request, type) => {
    selectedRequest.value = request;
    approvalForm.type = type;
    approvalForm.admin_notes = ''; // Reset notes when opening
    showReviewModal.value = true;
    pendingModalData.value = null; // Clear any pending data
};

const closeModal = () => { 
    showReviewModal.value = false; 
    if (!pendingModalData.value) {
        // Only clear if we're not temporarily closing for alert
        selectedRequest.value = null; 
        approvalForm.reset();
    }
};

const openFullReportModal = (report) => {
    selectedReportForView.value = report;
    showFullReportModal.value = true;
};

const closeFullReportModal = () => {
    showFullReportModal.value = false;
    selectedReportForView.value = null;
};

const formatCurrency = (value) => `₱${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const processApproval = (action) => {
    // Validate admin notes for rejections
    if (action === 'reject' && !approvalForm.admin_notes.trim()) {
        // Store current modal data
        pendingModalData.value = {
            request: selectedRequest.value,
            type: approvalForm.type,
            admin_notes: approvalForm.admin_notes,
        };
        
        // Close the modal
        showReviewModal.value = false;
        
        // Show alert after modal closes
        setTimeout(() => {
            Swal.fire({
                icon: 'warning',
                title: 'Notes Required',
                text: 'Please provide admin notes when rejecting a request.',
                confirmButtonText: 'OK',
            }).then(() => {
                // Reopen the modal after user clicks OK
                if (pendingModalData.value) {
                    selectedRequest.value = pendingModalData.value.request;
                    approvalForm.type = pendingModalData.value.type;
                    approvalForm.admin_notes = pendingModalData.value.admin_notes;
                    showReviewModal.value = true;
                    pendingModalData.value = null;
                }
            });
        }, 200);
        return;
    }

    approvalForm.action = action;
    approvalForm.patch(route('admin.approvals.update', selectedRequest.value.id), {
        onSuccess: () => {
            closeModal();
            pendingModalData.value = null; // Clear any pending data
            // Small delay to ensure modal closes before showing alert
            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: action === 'approve' ? 'Request Approved!' : 'Request Rejected',
                    text: `The ${approvalForm.type === 'correction' ? 'correction request' : 'financial report'} has been ${action === 'approve' ? 'approved' : 'rejected'} successfully.`,
                    timer: 2000,
                    showConfirmButton: false,
                });
            }, 100);
        },
        onError: () => {
            // Store current modal data
            pendingModalData.value = {
                request: selectedRequest.value,
                type: approvalForm.type,
                admin_notes: approvalForm.admin_notes,
            };
            
            // Close the modal
            showReviewModal.value = false;
            
            // Show error alert after modal closes
            setTimeout(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to process the request. Please try again.',
                    confirmButtonText: 'OK',
                }).then(() => {
                    // Reopen the modal after user clicks OK
                    if (pendingModalData.value) {
                        selectedRequest.value = pendingModalData.value.request;
                        approvalForm.type = pendingModalData.value.type;
                        approvalForm.admin_notes = pendingModalData.value.admin_notes;
                        showReviewModal.value = true;
                        pendingModalData.value = null;
                    }
                });
            }, 200);
        },
    });
};
</script>

<template>
    <Head title="Pending Approvals" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Pending Approvals</h2>
                    <p class="text-gray-600 mt-1">Review and manage pending requests</p>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-orange-50 rounded-lg border border-orange-200 flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-orange-800 font-semibold">
                        {{ correctionRequests.length + financialReports.length }} Pending Items
                    </span>
                </div>
            </div>
        </template>
        
        <!-- Enhanced Tabs Navigation -->
        <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <nav class="flex">
                <button 
                    @click="activeTab = 'corrections'" 
                    :class="[
                        activeTab === 'corrections' 
                            ? 'bg-green-50 text-green-700 border-b-2 border-green-500 font-semibold' 
                            : 'text-gray-600 hover:bg-gray-50 transition-colors'
                    ]"
                    class="flex-1 px-6 py-4 text-center border-r border-gray-200 last:border-r-0 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span class="text-lg">Corrections</span>
                    <span v-if="correctionRequests.length > 0" class="ml-2 px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">
                        {{ correctionRequests.length }}
                    </span>
                </button>
                
                <button 
                    @click="activeTab = 'financial'" 
                    :class="[
                        activeTab === 'financial' 
                            ? 'bg-green-50 text-green-700 border-b-2 border-green-500 font-semibold' 
                            : 'text-gray-600 hover:bg-gray-50 transition-colors'
                    ]"
                    class="flex-1 px-6 py-4 text-center flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span class="text-lg">Reports</span>
                    <span v-if="financialReports.length > 0" class="ml-2 px-2 py-1 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">
                        {{ financialReports.length }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Content Area with Cards -->
        <div class="space-y-4">
            <!-- Data Correction List -->
            <div v-if="activeTab === 'corrections'">
                <div v-if="correctionRequests.length === 0" class="bg-white rounded-lg shadow-sm border-2 border-dashed border-gray-300 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 text-lg font-medium">No pending correction requests</p>
                    <p class="text-gray-400 text-sm mt-2">All correction requests have been processed</p>
                </div>

                <div v-else class="space-y-3">
                    <div 
                        v-for="req in correctionRequests" 
                        :key="req.id"
                        class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow overflow-hidden"
                    >
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-gray-900 font-semibold text-lg">{{ req.user.name }}</h3>
                                            <p class="text-gray-500 text-sm">{{ new Date(req.created_at).toLocaleString() }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500">
                                        <p class="text-gray-600 mb-2">
                                            <span class="text-gray-400 text-sm">Requested correction for:</span><br>
                                            <span class="font-bold text-gray-900">{{ req.request_type }} #{{ req.reference_id }}</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <PrimaryButton @click="openReviewModal(req, 'correction')" class="ml-4">
                                    Review Request
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Reports List -->
            <div v-if="activeTab === 'financial'">
                <div v-if="financialReports.length === 0" class="bg-white rounded-lg shadow-sm border-2 border-dashed border-gray-300 p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-500 text-lg font-medium">No submitted financial reports</p>
                    <p class="text-gray-400 text-sm mt-2">Financial reports awaiting review will appear here</p>
                </div>

                <div v-else class="space-y-3">
                    <div 
                        v-for="rep in financialReports" 
                        :key="rep.id"
                        class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow overflow-hidden"
                    >
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-gray-900 font-semibold text-lg">{{ rep.generated_by.name }}</h3>
                                            <p class="text-gray-500 text-sm">Submitted on {{ new Date(rep.created_at).toLocaleDateString() }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-green-500">
                                        <p class="text-gray-600">
                                            <span class="text-gray-400 text-sm">Period:</span><br>
                                            <span class="font-bold text-gray-900">
                                                {{ new Date(rep.start_date).toLocaleDateString() }} 
                                                <span class="text-gray-400">to</span> 
                                                {{ new Date(rep.end_date).toLocaleDateString() }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                
                                <PrimaryButton @click="openReviewModal(rep, 'financial')" class="ml-4">
                                    Review Report
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Review Modal -->
        <Modal :show="showReviewModal" @close="closeModal">
            <div v-if="selectedRequest" class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-t-lg">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Review {{ approvalForm.type === 'correction' ? 'Correction Request' : 'Financial Report' }}
                    </h2>
                    <p class="text-green-100 text-sm mt-1">
                        {{ approvalForm.type === 'correction' ? 'Review the data correction request and make a decision' : 'Review the financial report and approve or reject' }}
                    </p>
                </div>

                <!-- Modal Body -->
                <div class="p-6 max-h-[70vh] overflow-y-auto">
                    <!-- Request Information -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-gray-900 font-semibold text-lg">
                                    {{ approvalForm.type === 'correction' ? selectedRequest.user?.name : selectedRequest.generated_by?.name }}
                                </h3>
                                <p class="text-gray-500 text-sm">
                                    {{ approvalForm.type === 'correction' ? 'Requested on' : 'Submitted on' }} 
                                    {{ new Date(selectedRequest.created_at).toLocaleString() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Correction Request View -->
                    <div v-if="approvalForm.type === 'correction'" class="space-y-4">
                        <div class="bg-red-50 border-l-4 border-red-500 p-5 rounded-r-lg">
                            <h3 class="font-bold text-red-900 mb-3 flex items-center gap-2 text-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Original Error Description
                            </h3>
                            <p class="text-red-800 leading-relaxed">{{ selectedRequest.description_of_error }}</p>
                        </div>
                        
                        <div class="bg-green-50 border-l-4 border-green-500 p-5 rounded-r-lg">
                            <h3 class="font-bold text-green-900 mb-3 flex items-center gap-2 text-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Proposed Correction
                            </h3>
                            <p class="text-green-800 leading-relaxed">{{ selectedRequest.proposed_correction }}</p>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-500 p-5 rounded-r-lg">
                            <h3 class="font-bold text-blue-900 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Request Details
                            </h3>
                            <div class="text-blue-800 space-y-2">
                                <p><span class="font-semibold">Type:</span> {{ selectedRequest.request_type }}</p>
                                <p><span class="font-semibold">Reference ID:</span> #{{ selectedRequest.reference_id }}</p>
                                
                                <!-- Show current expense data if available -->
                                <div v-if="selectedRequest.related_data && approvalForm.type === 'correction' && selectedRequest.request_type === 'Expense Record'" class="mt-4 pt-4 border-t border-blue-200">
                                    <p class="font-semibold text-blue-900 mb-2">Current Expense Data:</p>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span class="font-medium">Amount:</span> 
                                            <span class="ml-1">₱{{ parseFloat(selectedRequest.related_data.amount || 0).toFixed(2) }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Receipt #:</span> 
                                            <span class="ml-1">{{ selectedRequest.related_data.receipt_number || 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Category:</span> 
                                            <span class="ml-1">{{ selectedRequest.related_data.category || 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Date:</span> 
                                            <span class="ml-1">{{ selectedRequest.related_data.expense_date ? new Date(selectedRequest.related_data.expense_date).toLocaleDateString() : 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div v-if="selectedRequest.related_data.receipt_image_url" class="mt-3">
                                        <span class="font-medium text-sm">Current Receipt Image:</span>
                                        <div class="mt-2">
                                            <a :href="selectedRequest.related_data.receipt_image_url" target="_blank" class="inline-block">
                                                <img :src="selectedRequest.related_data.receipt_image_url" alt="Current Receipt" class="h-20 w-auto object-contain rounded border border-blue-200 hover:border-blue-400 transition">
                                            </a>
                                        </div>
                                    </div>
                                    <div v-else class="mt-3">
                                        <span class="font-medium text-sm">Current Receipt Image:</span>
                                        <span class="ml-2 text-gray-500 text-sm">No image attached</span>
                                    </div>
                                    
                                    <!-- Uploaded Correct Receipt Image -->
                                    <div v-if="selectedRequest.uploaded_receipt_image_url" class="mt-4">
                                        <span class="font-medium text-sm text-green-700">Corrected Receipt Image (Uploaded by User):</span>
                                        <div class="mt-2">
                                            <a :href="selectedRequest.uploaded_receipt_image_url" target="_blank" class="inline-block">
                                                <img :src="selectedRequest.uploaded_receipt_image_url" alt="Corrected Receipt" class="h-20 w-auto object-contain rounded border-2 border-green-400 hover:border-green-600 transition">
                                            </a>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">This image will replace the current receipt image when approved.</p>
                                    </div>
                                </div>

                                <!-- Show current feed usage data if available -->
                                <div v-if="selectedRequest.related_data && approvalForm.type === 'correction' && selectedRequest.request_type === 'Feed Usage Record'" class="mt-4 pt-4 border-t border-blue-200">
                                    <p class="font-semibold text-blue-900 mb-2">Current Feed Usage Data:</p>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span class="font-medium">Quantity (kg):</span> 
                                            <span class="ml-1 font-bold text-red-600">{{ parseFloat(selectedRequest.related_data.quantity_kg || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} kg</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Recorded By:</span> 
                                            <span class="ml-1">{{ selectedRequest.related_data.recorded_by || 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Date:</span> 
                                            <span class="ml-1">{{ selectedRequest.related_data.created_at ? new Date(selectedRequest.related_data.created_at).toLocaleString() : 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Notes:</span> 
                                            <span class="ml-1">{{ selectedRequest.related_data.notes || '—' }}</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">Proposed correction will update the kg value and adjust feed stock accordingly.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Report View -->
                    <div v-if="approvalForm.type === 'financial'" class="space-y-4">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border-l-4 border-blue-500 p-5 rounded-r-lg">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-blue-900 text-lg flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                    Financial Summary Preview
                            </h3>
                                <button 
                                    @click="openFullReportModal(selectedRequest)"
                                    class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition duration-200 flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Full Report
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-white p-5 rounded-lg shadow-sm border-2 border-gray-200">
                                    <h4 class="text-gray-700 font-semibold text-sm mb-2 uppercase tracking-wide">Total Revenue</h4>
                                    <p class="text-3xl font-bold text-gray-900">
                                        ₱{{ parseFloat(selectedRequest.report_data?.totalRevenue || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </p>
                                </div>
                                <div class="bg-white p-5 rounded-lg shadow-sm border-2 border-gray-200">
                                    <h4 class="text-gray-700 font-semibold text-sm mb-2 uppercase tracking-wide">Total Expenses</h4>
                                    <p class="text-3xl font-bold text-gray-900">
                                        ₱{{ parseFloat(selectedRequest.report_data?.totalExpenses || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </p>
                                </div>
                                <div class="bg-white p-5 rounded-lg shadow-sm border-2 border-gray-200">
                                    <h4 class="text-gray-700 font-semibold text-sm mb-2 uppercase tracking-wide">Net Income</h4>
                                    <p class="text-3xl font-bold text-gray-900">
                                        ₱{{ parseFloat(selectedRequest.report_data?.netIncome || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-blue-200">
                                <p class="text-blue-800">
                                    <span class="font-semibold">Period:</span> 
                                    {{ new Date(selectedRequest.start_date).toLocaleDateString() }} 
                                    <span class="text-blue-600">to</span> 
                                    {{ new Date(selectedRequest.end_date).toLocaleDateString() }}
                                </p>
                            </div>
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="text-sm text-blue-800">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Click "View Full Report" above to see complete details including revenue breakdown, expense breakdown, and production summary.
                                </p>
                            </div>
                        </div>
                    </div>
                
                    <!-- Admin Notes Section -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Admin Notes
                            <span class="text-gray-400 font-normal text-xs ml-1">(Optional, but recommended for rejections)</span>
                        </label>
                        <textarea 
                            v-model="approvalForm.admin_notes" 
                            rows="4"
                            placeholder="Add any notes or remarks about this decision. Notes are recommended when rejecting a request."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none transition"
                        ></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 rounded-b-lg border-t border-gray-200">
                    <button 
                        @click="closeModal" 
                        :disabled="approvalForm.processing"
                        class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="processApproval('reject')" 
                        :disabled="approvalForm.processing"
                        class="px-6 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="approvalForm.processing">Processing...</span>
                        <span v-else>Reject</span>
                    </button>
                    <button
                        @click="processApproval('approve')" 
                        :disabled="approvalForm.processing"
                        class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="approvalForm.processing">Processing...</span>
                        <span v-else>Approve</span>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Full Financial Report Modal -->
        <Modal :show="showFullReportModal" @close="closeFullReportModal" max-width="5xl">
            <div v-if="selectedReportForView" class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 rounded-t-lg flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Financial Summary Report
                            </h2>
                            <p class="text-green-100 text-sm mt-1">
                                Period: {{ new Date(selectedReportForView.start_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }} - 
                                {{ new Date(selectedReportForView.end_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}
                            </p>
                        </div>
                        <button 
                            @click="closeFullReportModal"
                            class="text-white hover:text-gray-200 transition"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body - Scrollable -->
                <div class="p-6 overflow-y-auto flex-1">
                    <!-- Header with Logo and Company Info -->
                    <div class="text-center mb-8 border-b-2 border-gray-300 pb-6">
                        <div class="flex justify-center items-center mb-4">
                            <img src="/Image/logo.jpg" alt="United Farmers Association Logo" class="h-20 w-auto">
                        </div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">United Farmers Association of Baugo</h1>
                        <p class="text-sm text-gray-600 mb-1">Baugo, Maasin City, Southern Leyte, 6600, Philippines</p>
                        <h2 class="text-2xl font-bold text-gray-700 mt-4 mb-2">Financial Summary Report</h2>
                        <p class="text-md text-gray-500">For the period of {{ new Date(selectedReportForView.start_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }} - {{ new Date(selectedReportForView.end_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</p>
                    </div>

                    <!-- Income Statement Section -->
                    <div class="mb-8">
                        <h3 class="text-xl font-bold mb-3 text-gray-700">INCOME STATEMENT</h3>
                        <p class="text-sm text-gray-600 mb-4">This statement shows the company's profitability by detailing revenues and expenses for the period.</p>
                        <table class="w-full text-sm border-collapse">
                            <tbody>
                                <tr class="border-b">
                                    <td class="py-3 px-4 font-semibold">Total Revenue</td>
                                    <td class="py-3 px-4 text-right font-semibold text-gray-700">
                                        {{ formatCurrency(selectedReportForView.report_data?.totalRevenue || selectedReportForView.total_revenue) }}
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-3 px-4 font-semibold">Total Expenses</td>
                                    <td class="py-3 px-4 text-right font-semibold text-gray-700">
                                        ({{ formatCurrency(selectedReportForView.report_data?.totalExpenses || selectedReportForView.total_expenses) }})
                                    </td>
                                </tr>
                                <tr class="font-bold text-lg bg-gray-100">
                                    <td class="py-4 px-4">Net Income</td>
                                    <td class="py-4 px-4 text-right text-gray-700">
                                        {{ formatCurrency(selectedReportForView.report_data?.netIncome || selectedReportForView.net_income) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Revenue Breakdown -->
                    <div class="mb-8" v-if="selectedReportForView.report_data?.revenueBreakdown && Object.keys(selectedReportForView.report_data.revenueBreakdown).length > 0">
                        <h3 class="text-xl font-bold mb-3 text-gray-700">REVENUE BREAKDOWN</h3>
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-200 font-bold">
                                    <th class="border p-2 text-left">Product</th>
                                    <th class="border p-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(amount, product) in selectedReportForView.report_data.revenueBreakdown" :key="product" class="border-b">
                                    <td class="border p-2">{{ product }}</td>
                                    <td class="border p-2 text-right">{{ formatCurrency(amount) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td class="border p-2">Total Revenue</td>
                                    <td class="border p-2 text-right text-gray-700">
                                        {{ formatCurrency(selectedReportForView.report_data?.totalRevenue || selectedReportForView.total_revenue) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Expense Breakdown -->
                    <div class="mb-8" v-if="selectedReportForView.report_data?.expenseBreakdown && Object.keys(selectedReportForView.report_data.expenseBreakdown).length > 0">
                        <h3 class="text-xl font-bold mb-3 text-gray-700">EXPENSE BREAKDOWN</h3>
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-200 font-bold">
                                    <th class="border p-2 text-left">Category</th>
                                    <th class="border p-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(amount, category) in selectedReportForView.report_data.expenseBreakdown" :key="category" class="border-b">
                                    <td class="border p-2">{{ category || 'Uncategorized' }}</td>
                                    <td class="border p-2 text-right">{{ formatCurrency(amount) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td class="border p-2">Total Expenses</td>
                                    <td class="border p-2 text-right text-gray-700">
                                        {{ formatCurrency(selectedReportForView.report_data?.totalExpenses || selectedReportForView.total_expenses) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Production Summary -->
                    <div class="mb-8" v-if="selectedReportForView.report_data?.productionBreakdown && Object.keys(selectedReportForView.report_data.productionBreakdown).length > 0">
                        <h3 class="text-xl font-bold mb-3 text-gray-700">OPERATIONAL SUMMARY</h3>
                        <p class="text-sm text-gray-600 mb-4">Production data for the reporting period.</p>
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-200 font-bold">
                                    <th class="border p-2 text-left">Product</th>
                                    <th class="border p-2 text-right">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(quantity, product) in selectedReportForView.report_data.productionBreakdown" :key="product" class="border-b">
                                    <td class="border p-2">{{ product }}</td>
                                    <td class="border p-2 text-right">{{ quantity.toLocaleString() }} pcs</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg border-t border-gray-200 flex-shrink-0 flex justify-end gap-3">
                    <button 
                        @click="closeFullReportModal" 
                        class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
                    >
                        Close
                    </button>
                    <a 
                        :href="route('admin.financial-reports.download', selectedReportForView.id)"
                        class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download PDF
                    </a>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
