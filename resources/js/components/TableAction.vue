<script setup>
	import { defineProps, defineEmits } from 'vue';
	import { useRouter } from 'vue-router';
	
	const router = useRouter();
	const emit = defineEmits(['click']);

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

	const handleClick = (event) => {
	  if (props.route) {
	    // Si tiene ruta definida, navega
	    router.push(props.route);
	  } else {
	    // Si no tiene ruta, emite el evento al padre
	    emit('click', event);
	  }
	};
	//:to="{ path: route }" 
		
</script>
<template>
	<button 
		@click="handleClick"
		:title="title"
		class="my-1 py-2 md:py-1 px-2 md:h-8 md:w-8 md:mx-1 pl-[28px] md:pl-2 flex items-center bg-[#edefed] bg-[length:20px_20px] bg-no-repeat hover:bg-[#eaeceb] border-2 border-black/5 w-full rounded shadow-sm " 
		:class="icon ? `bg-[position:4px_50%] md:bg-center` : ''"
		 :style="icon ? { backgroundImage: `url('/img/${icon}')` } : null"
	>
		
		<span class="md:hidden font-semiboldtext-dynamic-color">{{ title }}</span>
	</button>
</template>
