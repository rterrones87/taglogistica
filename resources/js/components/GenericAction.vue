<script setup>
	import { defineProps, defineEmits } from 'vue';
	
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

	const emit = defineEmits(['click']);

	const handleClick = (event) => {
		emit('click', event);
	};
</script>
<template>
	<router-link 
		v-if="route"
		class="my-2 py-2 px-3 flex items-center bg-[#edefed] hover:bg-[#eaeceb] border-2 border-black/5 rounded font-semibold shadow-sm text-dynamic-color" 
		:to="{ path: route }" 
	>
		<img 
			v-if="icon"
			:src="`/img/${icon}`"
			class="w-6 h-6 me-2 rounded-full bg-dynamic-color" 
		/>
		{{ title }}
	</router-link>
	<button
		v-else
		type="button"
		class="my-2 py-2 px-3 flex items-center bg-[#edefed] hover:bg-[#eaeceb] border-2 border-black/5 rounded font-semibold shadow-sm text-dynamic-color cursor-pointer"
		@click="handleClick"
	>
		<img 
			v-if="icon"
			:src="`/img/${icon}`"
			class="w-6 h-6 me-2 rounded-full bg-dynamic-color" 
		/>
		{{ title }}
	</button>
</template>
