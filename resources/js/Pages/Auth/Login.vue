<script setup>
import { ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue'; // We still use this as a base
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';



defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Password visibility toggle
const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.post(route('login'), {
        onError: (errors) => {
            // Check if it's a throttle/lockout error
            const emailError = Array.isArray(errors.email) ? errors.email[0] : errors.email;
            if (emailError && (emailError.includes('Too many login attempts') || emailError.toLowerCase().includes('throttle'))) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Too Many Login Attempts',
                    html: 'You have exceeded the maximum number of login attempts.<br>Please wait for <strong>3 minutes</strong> before trying again.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3085d6',
                    timer: null,
                });
            } else {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: 'Invalid email or password. Please try again.',
                showConfirmButton: true,
                confirmButtonColor: '#3085d6',
            });
            }
        },
        onFinish: () => form.reset('password'),
    });
};


</script>

<template>
    <Head title="Log in" />
    <div class="min-h-screen bg-gray-100 text-gray-900 flex justify-center">
        <div class="max-w-screen-xl m-0 sm:m-10 bg-white shadow sm:rounded-lg flex justify-center flex-1">

            <!-- Left Side: The Form -->
            <div class="lg:w-1/2 xl:w-5/12 p-6 sm:p-12">
                <div>
                    <!-- Your Logo -->
                    <img src="/Image/logo.jpg" class="w-32 mx-auto" />
                </div>
                <div class="mt-8 flex flex-col items-center">
                    <h1 class="text-2xl xl:text-3xl font-extrabold text-brand-dark">Log in to Your Account</h1>
                    <div class="w-full flex-1 mt-8">
                         <form @submit.prevent="submit">
                            <div class="mx-auto max-w-xs">
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full text-lg p-3"
                                    placeholder="Email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />

                                <div class="mt-4 relative">
                                <TextInput
                                    id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="block w-full text-lg p-3 pr-12"
                                    placeholder="Password"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                />
                                    <button
                                        type="button"
                                        @click="togglePasswordVisibility"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                                        tabindex="-1"
                                    >
                                        <!-- Eye Icon (when password is hidden) -->
                                        <svg
                                            v-if="!showPassword"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                            class="w-6 h-6"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                        </svg>
                                        <!-- Eye Slash Icon (when password is visible) -->
                                        <svg
                                            v-else
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                            class="w-6 h-6"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m0 0a2 2 0 112.828 2.828M6.228 6.228L21.75 21.75"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <PrimaryButton
                                    class="mt-8 w-full bg-brand-green hover:bg-brand-dark text-lg py-5 justify-center"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Log In
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Side: The Green Panel -->
            <div class="flex-1 bg-brand-light-green text-center hidden lg:flex" style="background-image: url('your-optional-geometric-background-image.svg');">
                <div class="m-12 xl:m-16 w-full bg-contain bg-center bg-no-repeat">
                   <div class="w-full h-full flex flex-col justify-center items-center">
                        <h2 class="text-3xl font-bold text-white">Welcome to UNIFAB</h2>
                        <p class="text-white mt-4">A Poultry Egg for United Farmer Association of Baugo</p>
                       
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>