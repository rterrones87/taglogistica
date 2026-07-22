<script setup>
	import { defineProps } from 'vue';
	
	const props = defineProps({
		title: {
			type: String,
			default: ''
		},
		icon: {
			type: String,
			default: ''
		},
		route : {
			type: String,
			default: ''
		}
	});

</script>
<template>
	<button 
		type="submit" 
		class="my-2 py-2 px-3 flex items-center hover:opacity-80 border-2 border-white/15 rounded font-semibold shadow-sm text-white bg-dynamic-color" 
		:to="{ path: route }" 
	>
		<img 
			v-if="icon"
			:src="`/img/${icon}`"
			class="w-6 h-6 me-2 rounded-full bg-dynamic-color" 
		/>
		{{ title }}
	</button>
</template>
