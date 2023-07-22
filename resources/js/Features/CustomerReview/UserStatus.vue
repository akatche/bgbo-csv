<script setup>
import { computed , toRaw } from 'vue'
import Spinner from "@/Components/Spinner.vue";

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

const processing = computed(() => {
    const user = toRaw(props.user);

    if(user.sent){
        return false;
    }

    return user.reason === null;
});

const isSuccessful = computed(() => {
    const user = toRaw(props.user);

    return user.sent;
});


const failureReason = computed(() => {
    const user = toRaw(props.user);

    let reason = '';

    switch (user.reason) {
        case 'already notified':
            reason = "User cannot be notified more than once";
            break;
        case 'beyond days to compare':
            reason = "User can only be notified within 7 days of transaction";
            break;
    }

    return reason;
});

</script>

<template>
    <span v-if="processing">
        <Spinner class="w-6 h-6" />
    </span>
    <div v-else>
        <p v-if="isSuccessful">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 fill-green-600">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
            </svg>

        </p>
        <p v-else :title="failureReason">
            <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 fill-red-700">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
            </svg>
        </p>
    </div>
</template>
