<script setup>

const props = defineProps({
  href: String,
  icon: String,
})

</script>

<template>
    <a
        :href="href"
        class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
    >
        <div class="inline-flex items-center ">
            <img v-if="icon" :src="icon" class="mx-2 w-[20px]"/>
            <slot />
        </div>
</a>
</template>
