// assets/js/helpers.js

const GesDocHelpers = {
    // Objeto de idioma en Español para DataTables
    dataTablesLang: {
        "sProcessing":     "Procesando...",
        "sLengthMenu":     "Mostrar _MENU_ registros",
        "sZeroRecords":    "No se encontraron resultados",
        "sEmptyTable":     "Ningún dato disponible en esta tabla",
        "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
        "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
        "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
        "sInfoPostFix":    "",
        "sSearch":         "Buscar:",
        "sUrl":            "",
        "sInfoThousands":  ",",
        "sLoadingRecords": "Cargando...",
        "oPaginate": {
            "sFirst":    "Primero",
            "sLast":     "Último",
            "sNext":     "Siguiente",
            "sPrevious": "Anterior"
        },
        "oAria": {
            "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
            "sSortDescending": ": Activar para ordenar la columna de manera descendente"
        }
    },

    /**
     * Formatea un número como moneda usando la coma (,) para miles y el punto (.) para decimales.
     * Ejemplo: 1234567.89 -> 1,234,567.89
     */
    formatCurrency: function(amount) {
        if (amount === null || amount === undefined || amount === '') return '';
        const number = parseFloat(amount);
        if (isNaN(number)) return amount;
        
        // Usamos la configuración 'en-US' nativa del navegador que cumple exactamente
        // con la regla de comas para miles y puntos para decimales.
        return number.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },

    /**
     * Manejador centralizado de errores para peticiones AJAX.
     * Evalúa si es un error 401 (No autorizado) para enviar al Login.
     */
    handleApiError: function(xhr) {
        if (xhr.status === 401) {
            Swal.fire({
                icon: 'warning',
                title: 'Sesión expirada',
                text: 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.',
                confirmButtonColor: '#0A4275'
            }).then(() => {
                window.location.href = 'login.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error de servidor',
                text: 'Ocurrió un error inesperado al procesar la solicitud.',
                confirmButtonColor: '#0A4275'
            });
        }
    }
};
