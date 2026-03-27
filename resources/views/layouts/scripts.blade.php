<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

<script src="{{ asset('vendor/select2/js/select2.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

<script>
$(document).ready(function() {
    // 1. Token CSRF Global
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // 2. Traducción DataTables
    var lenguajeEspanol = {
        "decimal": "",
        "emptyTable": "No hay información",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(Filtrado de _MAX_ entradas totales)",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ entradas",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "Sin resultados encontrados",
        "paginate": {
            "first": "Primero",
            "last": "Último",
            "next": "Siguiente",
            "previous": "Anterior"
        }
    };

    // 3. Inicializadores
    $('.datepick').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true
    });

    $('.sampleTable').DataTable({
        "language": lenguajeEspanol,
        "responsive": true
    });

    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccione una opción',
        allowClear: true,
        width: '100%'
    });



}); 
// Variable para llevar la cuenta actual
let currentNotificationCount = {{ auth()->check() ? auth()->user()->unreadNotifications->count() : 0 }};

function checkNotifications() {
    @guest
        return;
    @endguest
    $.ajax({
        url: "{{ route('notifications.count') }}",
        method: "GET",
        success: function(data) {
            // Si el nuevo conteo es mayor al que teníamos, algo nuevo llegó
            if (data.count > currentNotificationCount) {
                playNotificationSound();
                
                // Opcional: Actualizar el número en la campanita visualmente
                $('.navbar-badge').text(data.count).show();
                
                // Actualizamos nuestra variable local
                currentNotificationCount = data.count;
            } else {
                currentNotificationCount = data.count;
            }
        }
    });
}

function playNotificationSound() {
    const audio = new Audio("{{ asset('sounds/notification.mp3') }}");
    
    // Los navegadores modernos bloquean el sonido si el usuario no ha interactuado
    // con la página primero. Esto intenta reproducirlo y maneja el error.
    audio.play().catch(function(error) {
        console.log("El navegador bloqueó el sonido automático hasta que hagas clic en algo.");
    });
}

// Ejecutar cada 30 segundos
setInterval(checkNotifications, 30000);

</script>

