<template>
  <div class="py-4 px-4 bg-white shadow-md z-10 sticky top-[55px] left-0"> <!---->
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li
          v-for="(item, index) in items"
          :key="index"
          class="breadcrumb-item"
          :class="{ active: isActive(index) }"
        >
          <router-link v-if="item.path && !isActive(index)" :to="item.path">
            {{ item.title }}
          </router-link>
          <span v-else>{{ item.title }}</span>
        </li>
      </ol>
    </nav>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue';

// Definir props con validación
const props = defineProps({
  items: {
    type: Array,
    required: true,
    validator: (value) =>
      value.every(
        (item) =>
          item &&
          typeof item.title === "string" &&
          (typeof item.path === "string" || item.path === undefined)
      ),
  },
});

// Computed para verificar el último elemento (activo)
const isActive = (index) => index === props.items.length - 1;
</script>

<style scoped>
.breadcrumb {
  display: flex;
  list-style: none;
  padding: 0;
  margin: 0;
  background: none;
}

.breadcrumb-item {
  font-size: 1rem;
  color: #6c757d;
  opacity: .75;
  font-weight: bold;
}

.breadcrumb-item a {
  text-decoration: none;
  color: inherit;
  transition: color 0.3s ease-in-out;
}

.breadcrumb-item a:hover {
  opacity: 1;
}

.breadcrumb-item + .breadcrumb-item::before {
  content: "/";
  margin: 0 8px;
  color: #6c757d;
}

.breadcrumb-item.active {
  font-weight: normal;
  color: #6c757d;
  pointer-events: none;
}
</style>
