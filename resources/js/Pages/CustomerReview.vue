<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Customer Review" />

    <div
        class="relative sm:flex sm:justify-center min-h-screen bg-center bg-gray-100"
    >

        <div class="max-w-7xl mx-auto p-6 lg:p-8">
            <div class="flex justify-center">
                <img src="/images/logo.png" alt="Logo" />
            </div>

            <div class="mt-16">
                <div class="grid grid-cols-1">
                    <div
                        class="p-6 bg-white rounded-lg shadow-md shadow-[#a366fc] flex flex-col"
                    >
                        <div class="flex gap-4">
                            <div
                                class="h-16 w-16 bg-[#f4edff] flex items-center justify-center rounded-full"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                </svg>
                            </div>

                            <h2 class="mt-6 text-xl font-semibold text-gray-900 dark:text-white">Customer Review</h2>
                        </div>


                        <div class="flex flex-col gap-4">
                            <p class="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
                                The following tool sends a communication to users so they can leave a review on reputation sites like Google or Facebook. Start by <strong>uploading</strong> the provided CSV file below.
                            </p>

                            <input type="file" accept=".csv" />

                            <button type="button" class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-[#230056] hover:bg-[#6401f6] transition ease-in-out duration-150 cursor-not-allowed" disabled="">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Process List
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
