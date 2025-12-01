<script setup>
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

const submit = () => {
    form.post(route('login'), {
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: 'Invalid email or password. Please try again.',
                showConfirmButton: true,
                confirmButtonColor: '#3085d6',
            });
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

                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-4 block w-full text-lg p-3"
                                    placeholder="Password"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                />

                                <PrimaryButton
                                    class="mt-8 w-full bg-brand-green hover:bg-brand-dark text-lg py-5"
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