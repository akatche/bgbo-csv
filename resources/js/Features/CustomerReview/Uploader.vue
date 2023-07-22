<script setup>

import UploadResults from "@/Features/CustomerReview/UploadResults.vue";
import UploadFile from "@/Features/CustomerReview/UploadFile.vue";
</script>

<template>
    <div class="mt-16 flex flex-row1">
        <div class="grid grid-cols-1">
            <div
                class="p-6 bg-white rounded-lg shadow-md shadow-[#a366fc] flex flex-col gap-6 divide-y divide-[#a366fc]"
            >
                <UploadFile />
                <UploadResults />
            </div>
        </div>
    </div>
</template>
