<!-- 1️⃣ jQuery -->
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

<!-- 2️⃣ Bootstrap JS -->
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

<!-- 3️⃣ PLUGINS LOCALES (ORDEN IMPORTANTE) -->
<script src="{{ asset('vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('vendor/select2/js/select2.min.js') }}"></script>

<!-- 4️⃣ OverlayScrollbars y AdminLTE -->
<script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<!-- 5️⃣ TU SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('.datepick').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true,
        language: 'es'
    });

    $('.sampleTable').DataTable({
        "language": { "url": "{{ asset('vendor/datatables/lang/es-ES.json') }}" }
    });

    $('.select2').select2({
        placeholder: 'Buscar...',
        allowClear: true,
        width: '100%'
    });
});
</script>

<!-- <script src="{{ asset('js/app.js') }}"></script> -->
@yield('scripts')
