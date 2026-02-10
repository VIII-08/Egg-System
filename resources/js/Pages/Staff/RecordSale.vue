<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import StaffLayout from '@/Layouts/StaffLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    products: Array,
});

// Local state for the POS interface
const selectedProduct = ref(null);
const currentQuantity = ref('');
const cart = ref([]);
const paymentType = ref('pay_now'); // 'pay_now' or 'collectible'

// The main form that will be sent to the backend
const form = useForm({
    customer_name: '',
    items: [],
    amount_paid_now: '',
});

// --- Computed Properties for Live Totals ---
const subtotal = computed(() => cart.value.reduce((acc, item) => acc + (item.price * item.quantity), 0));
const tax = computed(() => 0); // As per design, tax is 0
const totalAmount = computed(() => subtotal.value + tax.value);

// Watch payment type and auto-fill amount
watch(paymentType, (newType) => {
    if (newType === 'pay_now' && totalAmount.value > 0) {
        form.amount_paid_now = totalAmount.value.toFixed(2);
    } else if (newType === 'collectible') {
        form.amount_paid_now = '0';
    }
});

// Watch total amount and update if pay_now is selected
watch(totalAmount, (newTotal) => {
    if (paymentType.value === 'pay_now' && newTotal > 0) {
        form.amount_paid_now = newTotal.toFixed(2);
    }
});

// --- Methods for POS interactivity ---
function selectProductForSale(product) {
    if (product.stock_quantity === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Out of Stock',
            text: `${product.name} is currently out of stock. Please add stock before recording a sale.`,
        });
        return;
    }
    selectedProduct.value = product;
    currentQuantity.value = '1'; // Default to 1 for user convenience
}

function addToCart() {
    // 2. UPGRADE VALIDATION ALERTS
    if (!selectedProduct.value || !currentQuantity.value || currentQuantity.value <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Input',
            text: 'Please select an item and enter a valid quantity.',
        });
        return;
    }
    
    if (currentQuantity.value > selectedProduct.value.stock_quantity) {
        Swal.fire({
            icon: 'warning',
            title: 'Insufficient Stock',
            text: `Only ${selectedProduct.value.stock_quantity} ${selectedProduct.value.name} are available.`,
        });
        return;
    }

    const existingItem = cart.value.find(item => item.id === selectedProduct.value.id);
    if (existingItem) {
        existingItem.quantity = parseInt(existingItem.quantity) + parseInt(currentQuantity.value);
    } else {
        cart.value.push({
            ...selectedProduct.value,
            quantity: parseInt(currentQuantity.value),
        });
    }
    
    selectedProduct.value = null;
    currentQuantity.value = '';
}

function removeFromCart(productId) {
    cart.value = cart.value.filter(item => item.id !== productId);
}

// --- Final Submission ---
function completeSale() {
    if (!form.customer_name || form.customer_name.trim() === "") {
        Swal.fire({
            icon: "error",
            title: "Customer Name Required",
            text: "Please enter the customer's name before completing the sale.",
        });
        return;
    }

    if (cart.value.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Empty Sale',
            text: 'You cannot complete a sale with no items in the cart.',
        });
        return;
    }
    
    // Validate amount paid doesn't exceed total
    const amountPaid = parseFloat(form.amount_paid_now || 0);
    if (amountPaid > totalAmount.value) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: `Amount paid (₱${amountPaid.toFixed(2)}) cannot exceed total amount (₱${totalAmount.value.toFixed(2)}).`,
        });
        return;
    }

    // Confirmation alert
    Swal.fire({
        title: 'Confirm Sale',
        html: `You are about to complete a sale totaling <br><strong style="font-size: 1.5em;">₱${totalAmount.value.toFixed(2)}</strong>. <br><br> Proceed?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981', // Emerald Green
        cancelButtonColor: '#EF4444', // Red
        confirmButtonText: 'Yes, complete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            form.items = cart.value.map(item => ({ id: item.id, quantity: item.quantity }));
            
            // Handle payment based on payment type
            if (paymentType.value === 'pay_now') {
                // Full payment - use total amount
                form.amount_paid_now = totalAmount.value.toFixed(2);
            } else if (paymentType.value === 'collectible') {
                // Collectible - use entered amount (defaults to 0 if empty)
                if (!form.amount_paid_now || form.amount_paid_now === '') {
                    form.amount_paid_now = '0';
                }
            }
    
            form.post(route('sales.store'), {
                onSuccess: () => {
                    cart.value = [];
                    form.reset();
                    form.amount_paid_now = '';
                    paymentType.value = 'pay_now';
                    Swal.fire({
                        icon: 'success',
                        title: 'Sale Completed!',
                        text: 'The sale has been successfully recorded.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
                onError: (errors) => {
                    const msg = errors.items;
                    Swal.fire({
                        icon: 'error',
                        title: 'Sale Failed',
                        text: Array.isArray(msg) ? msg[0] : msg || 'An error occurred. Please check stock and try again.',
                    });
                }
            });
        }
    });
}
</script>

<template>
    <Head title="Record New Sale" />

    <StaffLayout>
        <template #header>Record New Sale</template>

         <!-- Success Message -->
        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <span>{{ $page.props.flash.success }}</span>
        </div>
        
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Panel: Product Selection (takes up more space) -->
            <div class="flex-grow lg:w-3/5 bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold text-gray-800">Select Items to Sell</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                    <div v-for="product in products" :key="product.id" @click="selectProductForSale(product)"
                       :class="{
                           'ring-2 ring-green-500': selectedProduct && selectedProduct.id === product.id,
                           'opacity-60 cursor-not-allowed': product.stock_quantity === 0
                       }"
                       class="p-4 border rounded-lg cursor-pointer hover:bg-gray-50 text-center">
                       <h3 class="font-bold text-gray-900">{{ product.name }}</h3>
                       <p class="text-sm text-gray-600">₱{{ parseFloat(product.price).toFixed(2) }} / pc</p>
                       <p :class="product.stock_quantity === 0 ? 'text-xs font-semibold text-red-600 mt-2' : 'text-xs font-semibold text-green-700 mt-2'">
                           Stock: {{ product.stock_quantity }} pcs
                           <span v-if="product.stock_quantity === 0" class="block text-red-600 font-bold">(Out of Stock)</span>
                       </p>
                    </div>
                </div>
                
                <div class="mt-8 border-t pt-6" v-if="selectedProduct">
                    <h3 class="text-lg font-semibold text-gray-700">Add <span class="text-green-600">{{ selectedProduct.name }}</span> to Sale</h3>
                    <div class="flex items-center space-x-2 mt-2">
                         <input v-model="currentQuantity" type="number" placeholder="Enter quantity (pcs)" class="flex-grow block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-lg p-3">
                         <PrimaryButton @click="addToCart" class="text-lg py-3 px-6">
                            Add to Sale
                        </PrimaryButton>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Current Sale (takes up less space) -->
            <div class="lg:w-2/5 bg-white p-6 rounded-lg shadow">
                 <h2 class="text-xl font-bold text-gray-800">Current Sale</h2>
                 <div class="space-y-4 mt-4">
                     <div>
                        <label class="block text-sm font-medium text-gray-700">Customer Name</label>
                        <input v-model="form.customer_name" type="text" placeholder="e.g., John Dela Cruz" class="mt-1 block w-full rounded-md border-gray-300">
                     </div>
                     
                     <!-- Payment Type Selector -->
                     <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Type</label>
                        <div class="flex gap-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" v-model="paymentType" value="pay_now" class="form-radio text-green-600">
                                <span class="ml-2 font-semibold text-gray-700">Pay Now (Full Payment)</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" v-model="paymentType" value="collectible" class="form-radio text-orange-600">
                                <span class="ml-2 font-semibold text-gray-700">Collectible (Credit/Utang)</span>
                            </label>
                        </div>
                     </div>
                     
                     <!-- Amount Paid Now (conditional display) -->
                     <div v-if="paymentType === 'pay_now'">
                        <label class="block text-sm font-medium text-gray-700">Amount Paid</label>
                        <input v-model="form.amount_paid_now" type="number" min="0" step="0.01" :max="totalAmount" class="mt-1 block w-full rounded-md border-gray-300 bg-green-50" readonly>
                        <p class="text-xs text-green-600 mt-1">✓ Full payment: ₱{{ totalAmount.toFixed(2) }}</p>
                     </div>
                     
                     <div v-if="paymentType === 'collectible'">
                        <label class="block text-sm font-medium text-gray-700">Amount Paid Now (Optional Partial Payment)</label>
                        <input v-model="form.amount_paid_now" type="number" min="0" step="0.01" :max="totalAmount" :placeholder="`Enter 0 for full credit, or partial amount up to ₱${totalAmount.toFixed(2)}`" class="mt-1 block w-full rounded-md border-gray-300">
                        <p class="text-xs text-orange-600 mt-1">
                            <span v-if="form.amount_paid_now && parseFloat(form.amount_paid_now) > 0">
                                Partial payment: ₱{{ parseFloat(form.amount_paid_now || 0).toFixed(2) }} | Remaining: ₱{{ (totalAmount - parseFloat(form.amount_paid_now || 0)).toFixed(2) }}
                            </span>
                            <span v-else>Full credit/utang: ₱{{ totalAmount.toFixed(2) }} will be added to collectibles</span>
                        </p>
                     </div>
                     
                     <div>
                        <label class="block text-sm font-medium text-gray-700">Date of Sale</label>
                        <input type="text" :value="new Date().toLocaleString()" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100">
                     </div>
                 </div>

                 <div class="mt-6 border-t pt-4">
                    <div v-if="cart.length === 0" class="text-center text-gray-500 py-8">
                         No items in this sale yet.
                    </div>
                    <ul v-else class="space-y-3 max-h-48 overflow-y-auto">
                        <li v-for="item in cart" :key="item.id" class="flex justify-between items-center">
                            <div>
                                <p class="font-semibold">{{ item.name }}</p>
                                <p class="text-sm text-gray-600">{{ item.quantity }} pcs @ ₱{{ parseFloat(item.price).toFixed(2) }}</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <p class="font-semibold">₱{{ (item.price * item.quantity).toFixed(2) }}</p>
                                <button @click="removeFromCart(item.id)" class="text-red-500 hover:text-red-700">✖</button>
                            </div>
                        </li>
                    </ul>
                 </div>

                <div v-if="cart.length > 0" class="mt-4 border-t pt-4 space-y-2">
                     <div class="flex justify-between text-gray-700">
                        <span>Subtotal</span>
                        <span>₱{{ subtotal.toFixed(2) }}</span>
                     </div>
                    <div class="flex justify-between text-gray-700">
                         <span>Tax (0%)</span>
                         <span>₱{{ tax.toFixed(2) }}</span>
                    </div>
                     <div class="flex justify-between font-bold text-2xl text-gray-900 mt-2">
                         <span>Total Amount</span>
                         <span>₱{{ totalAmount.toFixed(2) }}</span>
                    </div>
                </div>

                 <div class="mt-6">
                     <PrimaryButton @click="completeSale" :disabled="form.processing || cart.length === 0" class="w-full text-xl py-4">
                        Complete Sale
                     </PrimaryButton>
                 </div>
            </div>
        </div>
    </StaffLayout>
</template>