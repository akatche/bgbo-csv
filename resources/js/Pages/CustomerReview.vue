<script setup>
import { Head } from '@inertiajs/vue3';
import Header from "@/Features/CustomerReview/Header.vue";
import Uploader from "@/Features/CustomerReview/Uploader.vue";
</script>

<template>
    <Head title="Customer Review" />

    <div
        class="relative sm:flex sm:justify-center min-h-screen bg-center bg-gray-100"
    >
        <div class="max-w-7xl mx-auto p-6 lg:p-8 ">
            <Header />
            <Uploader />
        </div>
    </div>
</template>
