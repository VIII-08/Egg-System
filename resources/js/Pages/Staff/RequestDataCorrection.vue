<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    recentRequests: Array,
    requestTypes: Array,
    reviewedCorrectionsCount: Number,
});

const hasViewedCorrections = ref(false);
const newCorrectionsCount = ref(0);

// Function to mark corrections as viewed
const markCorrectionsAsViewed = () => {
    if (typeof window !== 'undefined') {
        // Store the current count of reviewed corrections
        localStorage.setItem('staff_last_viewed_corrections_count', String(props.reviewedCorrectionsCount || 0));
        hasViewedCorrections.value = true;
        newCorrectionsCount.value = 0;
    }
};

// Check localStorage on mount and compare with current reviewed corrections count
onMounted(() => {
    if (typeof window !== 'undefined') {
        const lastViewedCount = parseInt(localStorage.getItem('staff_last_viewed_corrections_count') || '0', 10);
        const currentCount = props.reviewedCorrectionsCount || 0;
        
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
        
        // Mark as viewed after a short delay to ensure user sees the page
        setTimeout(() => {
            markCorrectionsAsViewed();
        }, 1000);
    }
});

const form = useForm({
    request_type: null,
    reference_id: null,
    description_of_error: '',
    proposed_correction: '',
    receipt_image: null,
});

const receiptPreview = ref(null);

// Function to handle the file selection
function handleFileChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    form.receipt_image = file;
    
    // Create a URL for image preview
    const reader = new FileReader();
    reader.onload = (e) => {
        receiptPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
}

const submit = () => {
    // Frontend validation before submitting
    if (!form.request_type) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please select the type of record that needs correction.',
        });
        return;
    }
    if (!form.reference_id) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please enter the Reference ID of the record.',
        });
        return;
    }
    if (!form.description_of_error.trim()) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please describe the error.',
        });
        return;
    }
    if (!form.proposed_correction.trim()) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please provide the proposed correction.',
        });
        return;
    }

    // Validate receipt image for expense corrections
    if (form.request_type === 'Expense Record' && !form.receipt_image) {
        Swal.fire({
            icon: 'warning',
            title: 'Receipt Image Recommended',
            text: 'For expense corrections, uploading the correct receipt image is recommended. Do you want to continue without an image?',
            showCancelButton: true,
            confirmButtonText: 'Yes, continue',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                submitForm();
            }
        });
        return;
    }

    submitForm();
};

const submitForm = () => {
    // Confirmation alert before sending the request
    Swal.fire({
        title: 'Submit this request?',
        text: "An administrator will review your request. This action cannot be undone.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#34D399', // A green color
        cancelButtonColor: '#EF4444', // A red color
        confirmButtonText: 'Yes, submit it!'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('data-correction.store'), {
                forceFormData: true, // Required for file uploads
                onSuccess: () => {
                    form.reset();
                    receiptPreview.value = null;
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Submitted!',
                        text: 'Your correction request has been sent for review.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
                onError: () => {
                    // This handles server-side validation errors
                    Swal.fire({
                        icon: 'error',
                        title: 'Submission Failed',
                        text: 'Please check the form for errors and try again.',
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Request Data Correction" />

    <StaffLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h1 class="text-3xl font-bold text-gray-800">Request Data Correction</h1>
                <div v-if="newCorrectionsCount > 0" class="flex items-center gap-2 px-4 py-2 bg-orange-50 rounded-lg border border-orange-200 flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-orange-800 font-semibold">
                        {{ newCorrectionsCount }} {{ newCorrectionsCount === 1 ? 'Request' : 'Requests' }} Reviewed
                    </span>
                </div>
            </div>
        </template>
        
        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline">{{ $page.props.flash.success }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Main Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold text-gray-800 mb-2">New Correction Request Form</h2>
                <p class="text-gray-500 mb-6">If you've made a mistake in a previous entry, you can request an administrator to correct it here.</p>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="request_type" class="block text-lg font-medium text-gray-700">1. What type of record needs correction?</label>
                        <select v-model="form.request_type" id="request_type" class="mt-2 block w-full text-base rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option :value="null" disabled>Select a record type...</option>
                            <option v-for="type in requestTypes" :key="type" :value="type">{{ type }}</option>
                        </select>
                         <div v-if="form.errors.request_type" class="text-sm text-red-600 mt-1">{{ form.errors.request_type }}</div>
                    </div>

                     <div>
                        <label for="reference_id" class="block text-lg font-medium text-gray-700">2. What is the Reference ID of the record?</label>
                         <p class="text-sm text-gray-500 mt-1">You can find this ID in the "View My Records" page for the specific log or expense.</p>
                        <input v-model="form.reference_id" type="number" id="reference_id" class="mt-2 block w-full text-base rounded-md border-gray-300 shadow-sm" placeholder="e.g., 123">
                         <div v-if="form.errors.reference_id" class="text-sm text-red-600 mt-1">{{ form.errors.reference_id }}</div>
                    </div>

                     <div>
                        <label for="description_of_error" class="block text-lg font-medium text-gray-700">3. Briefly describe the error.</label>
                         <textarea v-model="form.description_of_error" id="description_of_error" rows="4" class="mt-2 block w-full text-base rounded-md border-gray-300 shadow-sm" placeholder="e.g., 'I entered 50 eggs but it should have been 500.'"></textarea>
                         <div v-if="form.errors.description_of_error" class="text-sm text-red-600 mt-1">{{ form.errors.description_of_error }}</div>
                    </div>
                    
                     <div>
                        <label for="proposed_correction" class="block text-lg font-medium text-gray-700">4. What is the correct value?</label>
                         <p class="text-sm text-gray-500 mt-1 mb-2">
                             <span v-if="form.request_type === 'Expense Record'">For expenses:</span>
                             <span v-if="form.request_type === 'Expense Record'">Use phrases like <b>"change amount to 500"</b>, <b>"change receipt number to OR#12345"</b>, or <b>"change category to Feed"</b>.</span>
                             <span v-else>e.g., 'Please change the quantity to 500.'</span>
                         </p>
                         <textarea v-model="form.proposed_correction" id="proposed_correction" rows="4" class="mt-2 block w-full text-base rounded-md border-gray-300 shadow-sm" :placeholder="form.request_type === 'Expense Record' ? 'e.g., \'Please change receipt number to OR#12345\' or \'Please change amount to 500\'' : 'e.g., \'Please change the quantity to 500.\''"></textarea>
                         <div v-if="form.errors.proposed_correction" class="text-sm text-red-600 mt-1">{{ form.errors.proposed_correction }}</div>
                    </div>

                    <!-- Receipt Image Upload (Only for Expense Record) -->
                    <div v-if="form.request_type === 'Expense Record'">
                        <label class="block text-lg font-medium text-gray-700 mb-2">
                            5. Upload Correct Receipt Image <span class="text-gray-400 font-normal text-sm">(Recommended)</span>
                        </label>
                        <p class="text-sm text-gray-500 mb-2">If you're correcting the receipt image, please upload the correct receipt image here.</p>
                        <div class="mt-1 flex justify-center rounded-md border-2 border-dashed border-gray-300 px-6 pt-5 pb-6">
                            <div class="space-y-1 text-center">
                                <img v-if="receiptPreview" :src="receiptPreview" class="mx-auto h-32 w-auto object-contain">
                                <svg v-else class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="receipt-upload" class="relative cursor-pointer rounded-md bg-white font-medium text-green-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-green-500 focus-within:ring-offset-2 hover:text-green-500">
                                        <span>Click to browse</span>
                                        <input @change="handleFileChange" id="receipt-upload" name="receipt-upload" type="file" accept="image/*" class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag & drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                                <button v-if="receiptPreview" @click.prevent="form.receipt_image = null; receiptPreview = null; document.getElementById('receipt-upload').value = ''" type="button" class="mt-2 text-xs text-red-600 hover:text-red-800">Remove image</button>
                            </div>
                        </div>
                        <div v-if="form.errors.receipt_image" class="text-sm text-red-600 mt-1">{{ form.errors.receipt_image }}</div>
                    </div>
                    
                    <div class="pt-4">
                         <PrimaryButton :disabled="form.processing" class="w-full text-xl py-4">Submit Correction Request</PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Recent Requests -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                 <h2 class="text-xl font-bold text-gray-800 mb-6">My Recent Requests</h2>
                  <div class="space-y-4">
                    <div v-for="request in recentRequests" :key="request.id" class="p-4 rounded-md border"
                       :class="{
                           'bg-yellow-50 border-yellow-300': request.status === 'pending',
                           'bg-green-50 border-green-300': request.status === 'approved',
                           'bg-red-50 border-red-300': request.status === 'rejected'
                       }">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-gray-800">{{ request.request_type }} #{{ request.reference_id }}</p>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                               :class="{
                                   'bg-yellow-100 text-yellow-800': request.status === 'pending',
                                   'bg-green-100 text-green-800': request.status === 'approved',
                                   'bg-red-100 text-red-800': request.status === 'rejected'
                               }">
                               {{ request.status }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">Error: <span class="text-gray-800">{{ request.description_of_error }}</span></p>
                        
                        <!-- Admin Notes Section - Show for rejected or approved requests with notes -->
                        <div v-if="request.admin_notes && (request.status === 'rejected' || request.status === 'approved')" 
                             class="mt-3 p-3 rounded-md border-l-4"
                             :class="{
                                 'bg-red-100 border-red-500': request.status === 'rejected',
                                 'bg-green-100 border-green-500': request.status === 'approved'
                             }">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" 
                                     :class="request.status === 'rejected' ? 'text-red-600' : 'text-green-600'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold mb-1"
                                       :class="request.status === 'rejected' ? 'text-red-800' : 'text-green-800'">
                                        Admin Notes:
                                    </p>
                                    <p class="text-sm leading-relaxed"
                                       :class="request.status === 'rejected' ? 'text-red-700' : 'text-green-700'">
                                        {{ request.admin_notes }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-xs text-gray-400 mt-2">{{ new Date(request.created_at).toLocaleString() }}</p>
                    </div>
                    <div v-if="recentRequests.length === 0">
                       <p class="text-center text-gray-500">You haven't made any correction requests yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>