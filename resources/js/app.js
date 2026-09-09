//import './bootstrap';
import "../css/app.css";
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import axios from "axios";
import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

import swalPlugin from "./plugins/dialogs";
import permissionDirective from "./directives/permission";


axios.defaults.baseURL = import.meta.env.VITE_API_URL || "/api/";
//axios.defaults.withCredentials = true

// Interceptor para agregar el token en cada petición
axios.interceptors.request.use(
  (config) => {
    try {
      const token = localStorage.getItem("token");
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    } catch (error) {
      console.error("Error al obtener el token:", error);
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  },
);

const app = createApp(App);
app.use(router);
app.use(swalPlugin);
app.directive("permission", permissionDirective);
app.component("VueDatePicker", VueDatePicker);
app.config.globalProperties.$axios = axios;
app.mount("#app");
