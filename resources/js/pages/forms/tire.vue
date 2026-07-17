<template>
    <breadcrumb v-if="!isModal" :items="breadcrumbItems"/>
    <div class="m-4 bg-white p-4 rounded shadow-md">

        <h2 class="text-3xl my-4 font-bold text-center md:text-left">{{ isEditing ? "Editar Llanta" : "Nueva Llanta" }}</h2>
    
        <form @submit.prevent="saveItem">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div class="form-item">
                    <label>Unidad:</label>
                    <remoteselect
                        v-model="item.unit_id"
                        endpoint="units"
                        valueKey="id"
                        textKey="econame"
                    />
                    <p v-if="errors.unit_id" class="text-red-500 text-sm">{{ errors.unit_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Producto:</label>
                    <remoteselect
                        v-model="item.inventory_id"
                        endpoint="inventories"
                        valueKey="id"
                        textKey="name"
                        :textFormatter="formatInventoryText"
                        :filterFunction="filterOnlyTires"
                    />
                    <p v-if="errors.inventory_id" class="text-red-500 text-sm">{{ errors.inventory_id[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Serial/Marcaje:</label>
                    <input v-model="item.serial" type="text" required />
                    <p v-if="errors.serial" class="text-red-500 text-sm">{{ errors.serial[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Posición:</label>
                    <input v-model="item.position" type="text" placeholder="Ej: DELANTERO IZQUIERDO, TRASERO DERECHO" required />
                    <p v-if="errors.position" class="text-red-500 text-sm">{{ errors.position[0] }}</p>
                </div>
                <div class="form-item">
                    <label>Fecha:</label>
                    <input v-model="item.date" type="date" required />
                    <p v-if="errors.date" class="text-red-500 text-sm">{{ errors.date[0] }}</p>
                </div>
            </div>
            <div class="flex justify-end border-t mt-8 py-2">
                <FormAction
                    :title="isEditing ? 'Actualizar' : 'Guardar'"
                />
            </div>
        </form>
    </div>
</template>
  
<script setup>
    import { inject } from 'vue';
    import { upsert } from '../../composables/upsert';
    import breadcrumb from '../../components/breadcrumb.vue';
    import remoteselect from '../../components/remoteselect.vue';
    import FormAction from '@/components/FormAction.vue';
    
    const props = defineProps({
        isModal: {
            type: Boolean,
            default: false
        },
        idTire: {
            type: Number | null,
            default: null
        }
    });


    const dialogs = inject("swal");
    const emit = defineEmits(['saved']);

    const { item, isEditing, errors, saveItem } = upsert({
        endpoint: 'tires',
        data: { id: props.idTire, unit_id: 0, inventory_id: 0, serial: "", position: "", date: ""},
        dialogs,
        onCreatedListener: () => {
            emit('saved');
        },
        redirectOnCreate: props.isModal? null : 'tires'
    });

    // Formateador para mostrar brand: name en el selector de producto
    const formatInventoryText = (item) => {
        if (item && item.brand && item.name) {
            return `${item.brand}: ${item.name}`;
        }
        return item?.name || '';
    };

    // Filtro para mostrar solo productos que contengan "LLANTA" en el nombre y tengan existencia
    const filterOnlyTires = (item) => {
        return item.name && item.name.toUpperCase().includes('LLANTA') && item.quantity > 0;
    };

    // Breadcrumb personalizado
    const breadcrumbItems = [
        { title: 'Llantas', path: '/panel/tires' },
        { title: 'Administrar Llanta', path: '' }
    ];
</script>
  