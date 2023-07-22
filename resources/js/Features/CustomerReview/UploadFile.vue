<script setup>
import { useForm } from '@inertiajs/vue3'
import Spinner from "@/Components/Spinner.vue";

const form = useForm({
    users: null
})

function submit() {
    form.post('/api/reputation/upload',{
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
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
            The following tool sends a communication to users so they can leave a review on reputation sites like Google or Facebook. Start by <strong>uploading</strong> a CSV file below.
        </p>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <input type="file" accept=".csv" @input="form.users = $event.target.files[0]"/>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-[#230056] hover:bg-[#6401f6]"
                    :disabled="form.processing"
            >
                <Spinner v-if="form.progress"/>
                Send Communications
            </button>
        </form>
    </div>
</template>
