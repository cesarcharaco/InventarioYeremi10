<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>SAYER! | @yield('title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.css')
    @yield('css')

</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    
    @include('layouts.header')
    @include('layouts.sidebar')
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header (Breadcrumb) -->
        <div class="content-header">
            <div class="container-fluid">
                @yield('content_header')
            </div>
        </div>
        
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>
</div>

@include('layouts.scripts')
@yield('scripts')
</body>
</html>
