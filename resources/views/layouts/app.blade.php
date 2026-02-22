<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
