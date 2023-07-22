<script setup>

import { onMounted, ref } from 'vue'
import UserStatus from "@/Features/CustomerReview/UserStatus.vue";
const users = ref([])

const resultsUrl = ref('');

onMounted(() => {
    Echo.channel('customer-review')
        .listen('.review-completed', (e) => {
            console.log("review completed");
           console.log(e);
           resultsUrl.value = e.url;
        })
        .listen('.user.processed', (e) => {
            users.value.push(e.user);
        })
        .listen('.user.has.been.processed', (e) => {
            users.value = getUpdatedUsers(e.user);
        });
})

const getUpdatedUsers = (user) => {
    return users.value.map((u) => {
        if(u.id === user.id){
            return {
                ...u,
                sent:user.sent,
                reason:user.reason
            };
        }
        return u;
    })
}

</script>

<template>
    <div class="flex flex-col overflow-x-auto gap-4 py-4 " v-if="users.length > 0">
        <div class="flex justify-between items-center">
            <h2>Processed Results</h2>
            <a
                v-if="resultsUrl"
                class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-[#230056] hover:bg-[#6401f6] gap-2"
                :href="resultsUrl"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Download Results
            </a>
        </div>
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 ">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">
                    # Customer
                </th>
                <th scope="col" class="px-6 py-3">
                    Name
                </th>
                <th scope="col" class="px-6 py-3">
                    Email
                </th>
                <th scope="col" class="px-6 py-3">
                    Phone
                </th>
                <th scope="col" class="px-6 py-3">
                    Transaction
                </th>
                <th scope="col" class="px-6 py-3">
                    Date
                </th>
                <th scope="col" class="px-6 py-3">
                    Sent By
                </th>
                <th scope="col" class="px-6 py-3">
                    Status
                </th>
            </tr>
            </thead>
            <tbody>
            <tr class="bg-white border-b" v-for="user in users">
                <td class="px-6 py-4">
                    {{ user.number }}
                </td>
                <td class="px-6 py-4">
                    {{ user.name }}
                </td>
                <td class="px-6 py-4">
                    {{ user.email }}
                </td>
                <td class="px-6 py-4">
                    {{ user.phone }}
                </td>
                <td class="px-6 py-4">
                    {{ user.type }}
                </td>
                <td class="px-6 py-4">
                    {{ user.date }}
                </td>
                <td class="px-6 py-4">
                    {{ user.sent_by }}
                </td>
                <td class="px-6 py-4">
                    <UserStatus :user="user" />
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
