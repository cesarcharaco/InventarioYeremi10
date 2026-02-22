<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

<!-- Font Awesome -->
<link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

<!-- AdminLTE CSS -->
<link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">

<!-- OverlayScrollbars CSS -->
<link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}">

<!-- Google Font, FontAwesome, AdminLTE, OverlayScrollbars (igual) -->

<!-- ✅ PLUGINS LOCALES -->
<link rel="stylesheet" href="{{ asset('vendor/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

<!-- Tus estilos -->
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<style>
    .celda-editable { cursor: pointer; position: relative; transition: background 0.2s; }
    .celda-editable:hover { background-color: #f1f1f1 !important; }
    .text-orange { color: #fd7e14 !important; font-weight: bold; }
    .btn-xs { padding: 1px 5px; font-size: 12px; line-height: 1.5; border-radius: 3px; }
    /* Quitar flechas del input number */
    .input-costo::-webkit-inner-spin-button, 
    .input-costo::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

    /* Forzar que se vean las flechas en Chrome, Safari, Edge y Opera */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: inner-spin-button !important;
        opacity: 1 !important;
    }

    /* Forzar en Firefox */
    input[type=number] {
        -moz-appearance: number-input !important;
    }
    @media (max-width: 768px) {
        .modal-dialog {
            margin: 0.5rem;
        }
        .h5 {
            font-size: 1.1rem;
        }
    }
</style>