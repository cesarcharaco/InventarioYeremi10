el credito automaticamente pasará con un 10% extra

el admin manualmente indexará un porcenta en los creditos existentes

establecimiento de modelos de venta, de forma global, por categoria o por local

PruebaW123
https://api.whatsapp.com/send?phone=971507032874&text=Hola.


text/x-generic .htaccess ( UTF-8 Unicode text )
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# php -- BEGIN cPanel-generated handler, do not edit
# Configure el paquete “ea-php82” como el lenguaje de programación predeterminado “PHP”.
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# --- INICIO CORRECCIÓN VERSIÓN PHP ---
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php82 .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit




APP_NAME=InventarioYeremi
APP_ENV=production
APP_KEY=base64:RPtGYG1Roeo6L79G4b+xRur6YRktn7UHM9c4NoNq3Xc=
APP_DEBUG=true
APP_URL=https://sayer.orionintlcorp.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=orio0522_yermotors
DB_USERNAME=orio0522_yermotors
DB_PASSWORD=Yermotors26

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

WHATSAPP_INSTANCE_ID=instanceXXXXX
WHATSAPP_TOKEN=tu_token_aqui
WHATSAPP_BOSS_PHONE=584140863107


<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>



modulo de cuentas con proveedores

descuentos en bolivares por venta en dolares en porcentajes 10,15,20,25, 30, 35, 40, 45, 50(si el cliente va a pagar con dolares se le hace un descuento y el vendedor elige el monto de esas opciones)

verificar cuentas por cobrar del cliente:si el cliente compra y tiene credito se verifica y se pregunta si se agrega como abono y se paga de una vez en esa venta

en la venta hay que agregar las referencias de los pagomoviles y transferencias

Desglose de IVA: separar la Base Imponible del Monto del IVA (16%). El SENIAT exige que cada factura detalle cuánto es impuesto. poder imprimir una NOTA DE ENTREGA(0000001)
si el cliente lo solicita

acomodar tabla de detalles de venta(vista)

reporte de historial de venta con filtros(pdf)


Entiendo perfectamente. Estás buscando robustecer el sistema de ventas para cumplir tanto con estrategias comerciales (descuentos) como con normativas legales (IVA/SENIAT) y controles administrativos (cuentas por cobrar).

Antes de tocar una sola línea de código, establezcamos la **lógica de negocio** y el flujo de datos para cada punto:

---

## 1. Lógica de Descuentos por Pago en Divisas

El objetivo es incentivar el pago en efectivo/divisas ofreciendo un descuento que el vendedor aplica manualmente según el margen del producto o el cliente.

* **Condición de Activación:** El descuento solo debe permitirse si el método de pago seleccionado es **Dólares (Efectivo/Zelle)**.
* **Cálculo:** El descuento se aplica sobre el **Subtotal** de la venta en dólares.
* **Variables necesarias:**
* `Monto_Bruto_USD`: Suma de los productos.
* `Porcentaje_Descuento`: Elegido de la lista (10%, 15%... 50%).
* `Monto_Descuento`: `Monto_Bruto_USD * (Porcentaje / 100)`.
* `Total_Final_USD`: `Monto_Bruto_USD - Monto_Descuento`.


* **Regla de Negocio:** El sistema debe registrar cuánto se descontó para reportes de pérdida de margen a final de mes.

---

## 2. Verificación de Cuentas por Cobrar (CxC)

Esta es una lógica de **intersección de módulos** (Ventas + Cobranzas).

* **Disparador:** Al seleccionar al cliente en la pantalla de ventas, el sistema debe hacer una consulta rápida a la tabla de `cuentas_por_cobrar`.
* **Interfaz:** Si el saldo es `> 0`, debe aparecer una alerta visual (ej. un banner amarillo) indicando el "Saldo Pendiente".
* **Lógica de Abono:**
1. Si el cliente decide abonar, el vendedor ingresa el monto.
2. Ese monto **no suma** a los productos de la factura actual, pero sí se suma al **Total a Pagar** de la transacción.
3. **Resultado en DB:** Se genera un recibo de pago vinculado a la deuda vieja y se procesa la venta nueva simultáneamente.



---

## 3. Registro de Referencias de Pago

Vital para la conciliación bancaria y evitar fraudes.

* **Campos Requeridos:**
* `Metodo_Pago`: (Pago Móvil / Transferencia).
* `Banco_Origen`: (Opcional, pero recomendado).
* `Referencia`: Los últimos 4 o 6 dígitos (validación de longitud).


* **Lógica:** Si el monto total se paga con varios métodos (multimoneda), el sistema debe permitir agregar **múltiples referencias**. Ejemplo: 20$ efectivo + 500 Bs Pago Móvil (Ref: 1234).

---

## 4. Desglose de IVA (16%) y Documentación

Para cumplir con el SENIAT y tener control interno.

### El Cálculo Fiscal:

Aunque el sistema maneje dólares como base, el IVA siempre se calcula y registra sobre el valor en Bolívares a la tasa oficial.

* **Base Imponible:** Total de productos ÷ 1.16.
* **IVA (16%):** Base Imponible × 0.16.
* **Total:** Base Imponible + IVA.

### Diferenciación de Documentos:

1. **Nota de Entrega:** Es un documento interno. No tiene validez fiscal absoluta pero sirve para el despacho. Debe llevar un correlativo independiente (ej. `NE-0000001`).
2. **Factura:** Cumple con providencia administrativa.

* **Lógica de Impresión:** Al finalizar la venta, el sistema debe preguntar: "¿Imprimir Factura o Nota de Entrega?".

---

## Resumen del Flujo Lógico en la Venta:

1. **Selección de Cliente** → *Verificación de Deuda.*
2. **Carga de Productos** → *Cálculo de Subtotal.*
3. **Selección de Pago** → *Si es USD, habilitar selector de Descuento (10-50%).*
4. **Ingreso de Referencias** → *Capturar números de transacción.*
5. **Cálculo Final** → *Separar Base e IVA (16%).*
6. **Cierre** → *Elegir formato de impresión (Nota o Factura) y restar inventario.*

**¿Te parece bien esta estructura para empezar a trabajar el código de la base de datos y los controladores, o quieres ajustar algún porcentaje o regla?** Solo dime y procedemos con la implementación en PHP/Laravel.




<?php

namespace App\Imports;

use App\Models\InsumosMayor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InsumosImport implements ToModel, WithHeadingRow
{
    private $lista_id;
    private $incremento;

    // Solo añadimos el constructor para recibir el ID
    public function __construct($lista_id,$incremento)
    {
        $this->lista_id = $lista_id;
        $this->incremento = $incremento;

    }
    // Esto le dice al paquete que el encabezado está en la fila 9
    public function headingRow(): int
    {
        return 10; 
    }
    public function model(array $row)
    {
        //dd($row);
        // Aplicamos el incremento (ejemplo: 10% adicional)
        $costo = (float) ($row[3] ?? 0); // Ajusta 'precio' al nombre exacto de la columna en tu Excel
        
        
        return new InsumosMayor([
            'lista_oferta_id' => $this->lista_id,
            'codigo'      => (string) $row[0],
            'descripcion' => $row[1],
            'aplicativo'  => $row[2],
            'costo_usd'   => $costo,
            'venta_usd'   => round(($costo / $this->incremento), 2),
            'estado'      => 'activo'
        ]);
    }
}


//-----------------------------------------------------------------------------------------------------
@extends('layouts.app')

@section('title') Detalle de Venta #{{ $venta->codigo_factura }} @endsection

@section('css')
<style>
    /* Estilos generales para asegurar que nada se desborde */
    .table-responsive {
        border: none !important;
    }

    @media (max-width: 768px) {
        .content-wrapper, .app-content {
            padding: 5px !important;
            overflow-x: hidden !important;
        }

        .invoice {
            margin: 0 !important;
            padding: 10px !important;
            width: 100% !important;
        }

        /* Forzamos a que los números no se rompan en varias líneas */
        .text-right, .font-weight-bold {
            white-space: nowrap !important;
        }

        /* Reducción de fuentes para ganar espacio en móvil */
        .page-header {
            font-size: 1.1rem !important;
        }

        /* Ocultar columnas no vitales en móvil mediante CSS como respaldo */
        .table thead th:nth-child(3), 
        .table tbody td:nth-child(3),
        .table thead th:nth-child(4),
        .table tbody td:nth-child(4) {
            display: none !important;
        }

        /* Ajuste de anchos para que el total se vea claro */
        .table thead th:nth-child(1) { width: 15%; } /* Cantidad */
        .table thead th:nth-child(2) { width: 50%; } /* Producto */
        .table thead th:nth-child(5) { width: 35%; } /* Subtotal */

        .invoice-info .col-12 {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
    }
</style>
@endsection

@section('content')
<main class="app-content">
    <div class="app-title d-none d-md-flex"> {{-- Oculto en móvil para ahorrar espacio --}}
        <div>
            <h1><i class="fa fa-file-text-o"></i> Detalle de Factura</h1>
            <p>Comprobante de transacción interna</p>
        </div>
        <ul class="app-breadcrumb breadcrumb">
            <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
            <li class="breadcrumb-item"><a href="{{ route('ventas.index') }}">Ventas</a></li>
            <li class="breadcrumb-item">Detalle</li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="tile">
                <section class="invoice">
                    {{-- Encabezado --}}
                    <div class="row mb-4">
                        <div class="col-8 col-md-6">
                            <h2 class="page-header"><i class="fa fa-motorcycle"></i> YERMOTOS</h2>
                        </div>
                        <div class="col-4 col-md-6">
                            <h5 class="text-right" style="font-size: 0.9rem;">{{ $venta->created_at->format('d/m/Y') }}</h5>
                        </div>
                    </div>
                    
                    {{-- Información de Factura --}}
                    <div class="row invoice-info">
                        <div class="col-12 col-md-4 mb-3">
                            <strong>De:</strong>
                            <address>
                                <strong>Sede: {{ $venta->local->nombre }}</strong><br>
                                Vendedor: {{ $venta->usuario->name }}<br>
                                @if($venta->estado == 'completada')
                                    <span class="badge badge-success">COMPLETADA</span>
                                @else
                                    <span class="badge badge-danger">ANULADA</span>
                                @endif
                            </address>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <strong>Para:</strong>
                            <address>
                                <strong>{{ $venta->cliente->nombre }}</strong><br>
                                ID: {{ $venta->cliente->identificacion }}<br>
                                Tel: {{ $venta->cliente->telefono ?? 'N/A' }}
                            </address>
                        </div>
                        <div class="col-12 col-md-4 mb-3">
                            <b>Factura #{{ $venta->codigo_factura }}</b><br>
                            <b>Tipo:</b> {{ $venta->monto_credito_usd > 0 ? 'Crédito' : 'Contado' }}<br>
                            <b>ID Venta:</b> {{ $venta->id }}
                        </div>
                    </div>

                    {{-- Tabla de Productos --}}
                    <div class="row">
                        <div class="col-12 p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Cant.</th>
                                            <th>Producto</th>
                                            <th class="d-none d-md-table-cell">Descripción</th>
                                            <th class="d-none d-md-table-cell text-right">Precio ($)</th>
                                            <th class="text-right">Subtotal ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($venta->detalles as $detalle)
                                        <tr>
                                            <td>{{ $detalle->cantidad }}</td>
                                            <td>{{ $detalle->insumo->producto }}</td>
                                            <td class="d-none d-md-table-cell">{{ $detalle->insumo->descripcion }}</td>
                                            <td class="d-none d-md-table-cell text-right">${{ number_format($detalle->precio_unitario, 2) }}</td>
                                            <td class="text-right"><strong>${{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</strong></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Pagos y Total --}}
                    <div class="row mt-3">
                        <div class="col-12 col-md-6 mb-3">
                            <p class="lead font-weight-bold">Métodos de Pago:</p>
                            <div class="table-responsive">
                                <table class="table table-sm border">
                                    <tbody>
                                        @if($venta->pago_usd_efectivo > 0)
                                            <tr>
                                                <th>Efectivo USD:</th>
                                                <td class="text-right">${{ number_format($venta->pago_usd_efectivo, 2) }}</td>
                                            </tr>
                                        @endif
                                        @if($venta->pago_bs_efectivo > 0)
                                            <tr>
                                                <th>Efectivo Bs:</th>
                                                <td class="text-right">{{ number_format($venta->pago_bs_efectivo, 2) }} Bs</td>
                                            </tr>
                                        @endif
                                        @if($venta->pago_punto_bs > 0)
                                            <tr>
                                                <th>Punto / Bio:</th>
                                                <td class="text-right">{{ number_format($venta->pago_punto_bs, 2) }} Bs</td>
                                            </tr>
                                        @endif
                                        @if($venta->pago_pagomovil_bs > 0)
                                            <tr>
                                                <th>Pago Móvil:</th>
                                                <td class="text-right">{{ number_format($venta->pago_pagomovil_bs, 2) }} Bs</td>
                                            </tr>
                                        @endif
                                        @if($venta->monto_credito_usd > 0)
                                            <tr class="table-warning">
                                                <th>Monto a Crédito:</th>
                                                <td class="text-right"><strong>${{ number_format($venta->monto_credito_usd, 2) }}</strong></td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6 text-center text-md-right">
                            <div class="p-3 bg-light border rounded shadow-sm">
                                <h4 class="text-muted" style="font-size: 1rem;">TOTAL FACTURADO</h4>
                                <h2 class="text-primary font-weight-bold">${{ number_format($venta->total_usd, 2) }}</h2>
                            </div>
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="row no-print mt-4">
                        <div class="col-12 text-right">
                            <button class="btn btn-secondary btn-block d-md-inline-block mb-2" style="max-width: 200px;" onclick="window.print();">
                                <i class="fa fa-print"></i> Imprimir
                            </button>
                            <a href="{{ route('ventas.index') }}" class="btn btn-primary btn-block d-md-inline-block mb-2" style="max-width: 200px;">
                                <i class="fa fa-list"></i> Volver al listado
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>
@endsection