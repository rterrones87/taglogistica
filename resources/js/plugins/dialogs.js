import Swal from "sweetalert2";

export default {
    install: (app) => {
        // Crear una instancia de SweetAlert2 con configuración global
        const globalSwal = Swal.mixin({
            heightAuto: false // Deshabilita ajuste automático de altura
        });

        // Proveer Swal globalmente en la app
        app.provide("swal", globalSwal);
    }
};

