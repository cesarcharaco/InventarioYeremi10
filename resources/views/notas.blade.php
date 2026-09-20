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














SELECT v.*, c.nombre AS cliente_nombre FROM ventas v INNER JOIN clientes c ON v.id_cliente = c.id WHERE NOT EXISTS ( SELECT 1 FROM detalle_ventas dv WHERE dv.id_venta = v.id ) AND NOT EXISTS ( SELECT 1 FROM creditos cr WHERE cr.id_venta = v.id );



UPDATE ventas SET estado = 'completada', monto_credito_usd = 7.20 WHERE id = 430;
UPDATE ventas SET estado = 'completada', monto_credito_usd = 4.20 WHERE id = 434;
UPDATE ventas SET estado = 'completada', monto_credito_usd = 2.40 WHERE id = 436;
UPDATE ventas SET estado = 'completada', monto_credito_usd = 29.40 WHERE id = 442;


-- 1. Crédito ID 430 (CRD-8SSPAR - $7.20)
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 430, 17, 7.20, 7.20, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 430 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 430);

-- 2. Crédito ID 434 (CRD-WBTFYG - $4.20)
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 434, 17, 4.20, 4.20, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 434 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 434);

-- 3. Crédito ID 436 (CRD-CECOWI - $2.40)
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 436, 17, 2.40, 2.40, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 436 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 436);

-- 5. Crédito ID 442 (CRD-AZRAYT - $29.40)
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 442, 17, 29.40, 29.40, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 442 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 442);

UPDATE ventas 
SET estado = 'completada' 
WHERE id IN (431, 235, 432, 433, 435, 437, 438, 440);

-- 1. ID 431 (CRD-5NYLYZ - $9.10) | Cliente ID: 18
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 431, 18, 9.10, 9.10, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 431 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 431);

-- 2. ID 235 (CRD-YNUS87 - $25.90) | Cliente ID: 35
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 235, 35, 25.90, 25.90, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 235 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 235);

-- 3. ID 432 (CRD-VOF6RF - $1.20) | Cliente ID: 98
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 432, 98, 1.20, 1.20, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 432 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 432);

-- 4. ID 433 (CRD-RHQAD2 - $10.00) | Cliente ID: 99
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 433, 99, 10.00, 10.00, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 433 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 433);

-- 5. ID 435 (CRD-FPKTSW - $15.00) | Cliente ID: 105
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 435, 105, 15.00, 15.00, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 435 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 435);

-- 6. ID 437 (CRD-VE4C8T - $94.50) | Cliente ID: 119
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 437, 119, 94.50, 94.50, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 437 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 437);

-- 7. ID 438 (CRD-KR4SD9 - $10.00) | Cliente ID: 120
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 438, 120, 10.00, 10.00, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 438 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 438);

-- 8. ID 440 (CRD-BE0SQN - $6.00) | Cliente ID: 122
INSERT INTO creditos (id_venta, id_cliente, monto_inicial, saldo_pendiente, saldo_a_favor, fecha_vencimiento, estado, created_at, updated_at)
SELECT 440, 122, 6.00, 6.00, 0.00, DATE_ADD(created_at, INTERVAL 30 DAY), 'pendiente', created_at, updated_at 
FROM ventas WHERE id = 440 AND NOT EXISTS (SELECT 1 FROM creditos WHERE id_venta = 440);










protected function obtenerCajaActiva($id_local = null)
{
    // Si no enviamos un $id_local, calculamos el del usuario actual
    if (is_null($id_local)) {
        $user = auth()->user();
        $local = $user ? $user->localActual() : null;
        $id_local = $local ? $local->id : ($user->id_local ?? 1);
    }

    $caja = Caja::where('id_local', $id_local)
                ->where('estado', 'abierta')
                ->first();

    return $caja ? $caja->id : null;
}




// Si es admin y mandó un local, usamos ese. Si no, pasamos null para que use el por defecto.
$localDestino = (auth()->user()->esAdmin() && request()->filled('id_local')) 
                ? request('id_local') 
                : null;

$idCajaActiva = $this->obtenerCajaActiva($localDestino);

if (!$idCajaActiva) {
    throw new \Exception("No hay una caja abierta en la sucursal correspondiente para procesar este pago.");
}




<?php

namespace App\Http\Controllers;

use App\Models\Insumos;
use App\Models\InsumosC;
use App\Models\InsumoFoto;
use App\Models\Local;
use App\Models\Categoria;
use App\Models\ModeloVenta;
use App\Models\Gerencias;
use Illuminate\Http\Request;
use App\Http\Requests\InsumosRequest;
use App\Http\Requests\InsumosUpdateRequest;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;
use App\Imports\InsumosImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Notifications\StockBajoNotification;
use App\Models\User;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Barryvdh\DomPDF\Facade\Pdf;

class InsumosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        Gate::authorize('ver-logistica');
        // Se ajustó para obtener stock_min/max desde la tabla insumos
        // y la cantidad física desde la tabla pivot
        $insumos = DB::table('insumos')
            ->join('insumos_has_cantidades', 'insumos.id', '=', 'insumos_has_cantidades.id_insumo')
            ->join('local', 'local.id', '=', 'insumos_has_cantidades.id_local')
            ->select(
                'insumos.id',
                'insumos.serial',
                'insumos.producto',
                'insumos.descripcion',
                'insumos.estado',
                'insumos.stock_min', // Ahora viene de insumos
                'insumos.stock_max', // Ahora viene de insumos
                'insumos_has_cantidades.cantidad', // Columna unificada
                'insumos_has_cantidades.id_local',
                'insumos_has_cantidades.estado_local',
                'local.nombre as nombre_local'
            )
            ->get();

        return view('inventario.insumos.index', compact('insumos'));
    }

    public function precios()
    {
        Gate::authorize('ver-costos');
            $insumos = DB::table('insumos')
        ->join('categorias', 'insumos.categoria_id', '=', 'categorias.id')
        ->join('modelos_venta', 'insumos.modelo_venta_id', '=', 'modelos_venta.id')
        ->select(
            'insumos.id', // Aseguramos el ID del insumo
            'insumos.producto',
            'insumos.serial',
            'insumos.costo',
            'insumos.descripcion',
            'insumos.precio_venta_usd',
            'insumos.precio_venta_bs',
            'insumos.precio_venta_usdt',
            'categorias.categoria as nombre_categoria',
            'modelos_venta.modelo as nombre_modelo',
            'modelos_venta.tasa_bcv',
            'modelos_venta.tasa_binance', // Añadido
            'modelos_venta.factor_bcv',
            'modelos_venta.factor_usdt'
        )
        ->get();

    return view('inventario.insumos.precios', compact('insumos'));
    }

   
    public function actualizarCosto(Request $request) 
    {
        Gate::authorize('editar-datos-maestros');
        try {
            // 1. Validar que lleguen los datos
            if (!$request->id || !$request->costo) {
                return response()->json(['success' => false, 'error' => 'Datos incompletos'], 400);
            }

            // 2. Obtener datos con Join (usando nombres de tablas plurales)
            $insumoData = DB::table('insumos')
                ->join('modelos_venta', 'insumos.modelo_venta_id', '=', 'modelos_venta.id')
                ->where('insumos.id', $request->id)
                ->select(
                    'modelos_venta.tasa_bcv', 
                    'modelos_venta.tasa_binance', 
                    'modelos_venta.factor_bcv', 
                    'modelos_venta.factor_usdt',
                    'modelos_venta.porcentaje_extra'
                )
                ->first();

            if ($insumoData) {
                $costo = (float)$request->costo;
                
                // Convertir a float para evitar errores de división
                $tBcv = (float)$insumoData->tasa_bcv;
                $tBinance = (float)$insumoData->tasa_binance;
                $fBcv = (float)$insumoData->factor_bcv;
                $fUsdt = (float)$insumoData->factor_usdt;
                $extra = (float)$insumoData->porcentaje_extra;

                // --- CÁLCULOS (Corregidos con $) ---
                // Si tBcv es 0, evitamos división por cero
                if ($tBcv <= 0) $tBcv = 1; 

                // Cálculo Venta USD
                $usd = ($fBcv > 0) 
                       ? (($tBinance / $tBcv) / $fBcv) * $costo 
                       : $costo * (1 + $extra);

                // Cálculo Venta USDT
                $usdt = ($fUsdt > 0) 
                        ? $costo / $fUsdt 
                        : $costo * (1 + $extra);

                // Cálculo Venta BS
                $bs = $usd * $tBcv;

                // 3. Actualizar la tabla insumos
                DB::table('insumos')->where('id', $request->id)->update([
                    'costo' => $costo,
                    'precio_venta_usd' => round($usd, 2),
                    'precio_venta_bs' => round($bs, 2),
                    'precio_venta_usdt' => round($usdt, 2),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'precios' => [
                        'usd' => number_format($usd, 2),
                        'bs' => number_format($bs, 2),
                        'usdt' => number_format($usdt, 2)
                    ]
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Insumo no encontrado'], 404);

        } catch (\Exception $e) {
            // Esto devolverá el error real a la consola para que podamos verlo
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    public function create()
    {
        Gate::authorize('gestionar-insumos');
        $modelos = ModeloVenta::all();
        $locales = Local::all(); 
        $categorias = Categoria::orderBy('categoria', 'asc')->get();

        return view('inventario.insumos.create', compact('modelos', 'locales', 'categorias'));
    }

    public function store(Request $request)
    {
        $this->authorize('gestionar-insumos');
        $request->validate([
            'producto'        => 'required',
            'categoria_id'    => 'required|exists:categorias,id',
            'costo'           => 'required|numeric|min:0',
            'modelo_venta_id' => 'required|exists:modelos_venta,id',
        ]);

        $serial = $request->filled('serial') 
                ? $request->serial 
                : $this->generarSerialInsumo($request->categoria_id);

        $modelo = ModeloVenta::findOrFail($request->modelo_venta_id);
        $precios = $modelo->calcularPrecios($request->costo);
        
        DB::beginTransaction();
        try {
            $insumo = Insumos::create([
                'producto'          => $request->producto,
                'descripcion'       => $request->descripcion,
                'serial'            => $serial,
                'categoria_id'      => $request->categoria_id,
                'stock_min'         => $request->stock_min ?? 0,
                'stock_max'         => $request->stock_max ?? 0,
                'costo'             => $request->costo,
                'modelo_venta_id'   => $request->modelo_venta_id,
                'precio_venta_usd'  => $precios['precio_venta_usd'],
                'precio_venta_bs'   => $precios['precio_venta_bs'],
                'precio_venta_usdt' => $precios['precio_venta_usdt']
            ]);

            if ($request->has('id_local') && is_array($request->id_local)) {
                foreach ($request->id_local as $local_id) {
                    // Definimos explícitamente la cantidad asignada a este local
                    $cantidadLocal = $request->cantidad[$local_id] ?? 0;

                    InsumosC::create([
                        'id_insumo' => $insumo->id,
                        'id_local'  => $local_id,
                        'cantidad'  => $cantidadLocal,
                    ]);

                    // Evaluamos contra la variable recién asignada
                    if ($cantidadLocal <= ($request->stock_min ?? 0)) {
                        $gerentes = User::whereIn('role', ['admin', 'encargado','almacenista'])->get();
                        $detalles = [
                            'titulo'  => '¡Stock Inicial Bajo!',
                            'mensaje' => "El producto {$insumo->producto} inició con stock crítico ({$cantidadLocal}) en un local.",
                            'url'     => route('insumos.index'),
                            'icono'   => 'fas fa-exclamation-triangle'
                        ];
                        
                        foreach ($gerentes as $gerente) {
                            $gerente->notify(new StockBajoNotification($detalles));
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('insumos.index')->with('success', 'Insumo registrado con éxito');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $this->authorize('gestionar-insumos');
        // Se lee directamente de la tabla insumos ahora que centralizamos stock_min/max
        $insumo = Insumos::findOrFail($id);

        $categorias = Categoria::pluck('categoria', 'id');
        $modelos = ModeloVenta::pluck('modelo', 'id');

        return view('inventario.insumos.edit', compact('insumo', 'categorias', 'modelos'));
    }

    public function update(Request $request, $id)
        {
            $this->authorize('gestionar-insumos');
            try {
                DB::beginTransaction();

                $insumoActual = Insumos::findOrFail($id);
                $modelo = ModeloVenta::findOrFail($request->modelo_venta_id);
                
                if ($request->filled('serial') && $request->serial !== $insumoActual->serial) {
                    // 1. Prioridad: Serial manual escrito/escaneado por el usuario
                    $serialFinal = $request->serial;
                } elseif ($insumoActual->categoria_id != $request->categoria_id) {
                    // 2. Cambió la categoría y no escribió manual: Regenera por categoría
                    $serialFinal = $this->generarSerialInsumo($request->categoria_id);
                } else {
                    // 3. Sin cambios en categoría ni serial manual: Mantiene el existente
                    $serialFinal = $insumoActual->serial;
                }

                $costo = $insumoActual->costo;
                
                // Reemplazamos la división manual por el método seguro del modelo
                $precios = $modelo->calcularPrecios($costo);

                $insumoActual->update([
                    'producto'          => $request->producto,
                    'descripcion'       => $request->descripcion,
                    'categoria_id'      => $request->categoria_id,
                    'modelo_venta_id'   => $request->modelo_venta_id,
                    'serial'            => $serialFinal,
                    'precio_venta_usd'  => $precios['precio_venta_usd'],
                    'precio_venta_bs'   => $precios['precio_venta_bs'],
                    'precio_venta_usdt' => $precios['precio_venta_usdt'],
                    'stock_min'         => $request->stock_min, // Actualizado en insumos
                    'stock_max'         => $request->stock_max, // Actualizado en insumos
                ]);

                // --- LÓGICA DE NOTIFICACIÓN AUTOMÁTICA AL ACTUALIZAR ---
                // Consultamos las cantidades actuales en todos los locales para este insumo
                $stocksLocales = DB::table('insumos_has_cantidades')
                    ->join('local', 'local.id', '=', 'insumos_has_cantidades.id_local')
                    ->where('id_insumo', $id)
                    ->get();

                foreach ($stocksLocales as $stockLocal) {
                    if ($stockLocal->cantidad <= $request->stock_min) {
                        // Notificar a los administradores/gerentes
                        $gerentes = User::whereIn('role', ['admin', 'encargado','almacenista'])->get();
                        $detalles = [
                            'titulo'  => 'Stock Crítico tras Actualización',
                            'mensaje' => "El producto {$request->producto} está por debajo del nuevo mínimo en {$stockLocal->nombre}.",
                            'url'     => route('insumos.index'),
                            'icono'   => 'fas fa-sync-alt'
                        ];

                        foreach ($gerentes as $gerente) {
                            $gerente->notify(new StockBajoNotification($detalles));
                        }
                    }
                }
                // -------------------------------------------------------
                DB::commit();
                
                $mensaje = "Insumo actualizado correctamente.";
                if ($serialFinal != $insumoActual->serial) {
                    $mensaje .= " Se ha generado un nuevo serial: " . $serialFinal;
                }

                return redirect()->route('insumos.index')->with('success', $mensaje);

            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
            }
        }

    public function destroy(Request $request)
    {
        Gate::authorize('editar-datos-maestros');
        $this->authorize('gestionar-insumos');
        $insumo = Insumos::find($request->id_insumo);

        if ($insumo && $insumo->delete()) {
            return redirect()->back()->with('success', 'El Insumo fue eliminado exitosamente!');
        } else {
            return redirect()->back()->with('error', 'El Insumo no pudo ser eliminado!');
        }
    }

    private function generarSerialInsumo($categoriaId)
    {
        $prefix = str_pad($categoriaId, 3, '0', STR_PAD_LEFT);
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return "INS-{$prefix}-{$random}";
    }
    public function listarPorLocal($id_local)
    {
        $local = \App\Models\Local::findOrFail($id_local);

        // Consultamos los insumos filtrados por el ID del local
        $stock = DB::table('insumos_has_cantidades')
            ->join('insumos', 'insumos_has_cantidades.id_insumo', '=', 'insumos.id')
            ->select(
                'insumos.producto',
                'insumos.serial',
                'insumos.descripcion',
                'insumos.stock_min', // Corregido: pertenece a la tabla insumos
                'insumos.stock_max', // Corregido: pertenece a la tabla insumos
                'insumos_has_cantidades.cantidad' // Stock actual en ese local
            )
            ->where('insumos_has_cantidades.id_local', $id_local)
            ->get();

        return view('inventario.insumos.por_local', compact('stock', 'local'));
    }
    public function cambiarEstadoInsumo(Request $request)
    {
        $id_local = $request->id_local;
        
        if ($request->tipo === 'global') {
            Gate::authorize('gestionar-estado-global');
        } else {
            Gate::authorize('gestionar-estado-local', $id_local);
        }
        try {
        $idInsumo = $request->id;
        $nuevoEstado = $request->estado;
        $tipoCambio = $request->tipo; // 'global' o 'local'
        $idLocal = $request->id_local; // El local actual donde estamos parados

        if ($tipoCambio === 'global') {
            $insumo = Insumos::findOrFail($idInsumo);
            $insumo->estado = $nuevoEstado;
            $insumo->save();
            $mensaje = "Estado global actualizado.";
        } else {
            // Actualizamos solo para el local actual
            $x=InsumosC::where('id_insumo', $idInsumo)
                ->where('id_local', $idLocal)
                ->update(['estado_local' => $nuevoEstado]);

            $mensaje = "Estado actualizado solo para este local.";
        }

        return response()->json(['success' => true, 'message' => $mensaje]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => 'Error al procesar el cambio.'], 500);
    }
    }


    public function getInsumosData(Request $request)
    {
        Gate::authorize('ver-logistica');

        $query = DB::table('insumos')
            ->join('insumos_has_cantidades', 'insumos.id', '=', 'insumos_has_cantidades.id_insumo')
            ->join('local', 'local.id', '=', 'insumos_has_cantidades.id_local')
            ->select([
                'insumos.id',
                'insumos.serial',
                'insumos.producto',
                'insumos.descripcion',
                'insumos.estado as estado_global',
                'insumos.stock_min',
                'insumos.stock_max',
                'insumos_has_cantidades.cantidad',
                'insumos_has_cantidades.id_local',
                'insumos_has_cantidades.estado_local',
                'local.nombre as nombre_local'
            ]);
        // --- APLICACIÓN DE FILTROS PERSONALIZADOS ---
        if ($request->filled('filtro_producto')) {
            $query->where('insumos.producto', 'LIKE', '%' . $request->filtro_producto . '%');
        }

        if ($request->filled('filtro_estado_general')) {
            $query->where('insumos.estado', $request->filtro_estado_general);
        }

        if ($request->filled('filtro_estado_local')) {
            $query->where('insumos_has_cantidades.estado_local', $request->filtro_estado_local);
        }

        if ($request->filled('filtro_ubicacion')) {
            $query->where('local.nombre', $request->filtro_ubicacion);
        }
        // ------------------------------------------
        return DataTables::of($query)
            // --- MAPEADO DE BÚSQUEDA: ESTO ELIMINA LOS ERRORES DE LAS IMÁGENES ---
            ->filterColumn('estado_global', function($q, $kw) {
                $q->whereRaw("insumos.estado LIKE ?", ["%{$kw}%"]);
            })
            ->filterColumn('estado_local', function($q, $kw) {
                $q->whereRaw("insumos_has_cantidades.estado_local LIKE ?", ["%{$kw}%"]);
            })
            ->filterColumn('cantidad', function($q, $kw) {
                $q->whereRaw("insumos_has_cantidades.cantidad LIKE ?", ["%{$kw}%"]);
            })
            ->filterColumn('nombre_local', function($q, $kw) {
                $q->whereRaw("local.nombre LIKE ?", ["%{$kw}%"]);
            })

            // --- FORMATEO VISUAL: IGUAL A TU IMAGEN ORIGINAL ---
            ->editColumn('serial', function($row) {
                return '<span class="badge badge-secondary">' . e($row->serial) . '</span>';
            })
            ->editColumn('producto', function($row) {
                return '<strong>' . e($row->producto) . '</strong>';
            })
            ->editColumn('estado_global', function($row) {
                $class = $row->estado_global === 'En Venta' ? 'success' : 'dark';
                return '<span class="badge badge-'.$class.'"><i class="fas fa-globe"></i> ' . e($row->estado_global) . '</span>';
            })
            ->editColumn('estado_local', function($row) {
                $class = $row->estado_local === 'Disponible' ? 'success' : 'danger';
                return '<span class="badge badge-'.$class.'"><i class="fas fa-store"></i> ' . e($row->estado_local) . '</span>';
            })
            // Colores de stock amarillos y oscuros como pediste
            ->editColumn('stock_min', function($row) {
                return '<span class="badge badge-warning" style="background-color: #ffe066; color: #000;">' . $row->stock_min . '</span>';
            })
            ->editColumn('stock_max', function($row) {
                return '<span class="badge badge-dark">' . $row->stock_max . '</span>';
            })
            ->editColumn('cantidad', function($row) {
                return '<span class="text-primary font-weight-bold" style="font-size: 1.1em;">' . $row->cantidad . '</span>';
            })
            ->editColumn('nombre_local', function($row) {
                return '<i class="fa fa-map-marker-alt text-danger"></i> ' . e($row->nombre_local);
            })
            ->addColumn('acciones', function($row) {
                return '
                <div class="btn-group">
                    <a href="'.route('insumos.edit', $row->id).'" class="btn btn-info btn-sm"><i class="fa fa-edit"></i></a>
                    <a href="'.route('insumos.album', $row->id).'" class="btn btn-warning btn-sm" title="Álbum de Fotos"><i class="fa fa-image"></i></a>
                    <button class="btn btn-success btn-sm" onclick="detalles(\''.$row->producto.'\',\''.addslashes($row->descripcion).'\',\''.$row->serial.'\','.$row->stock_min.','.$row->stock_max.','.$row->cantidad.',\''.$row->nombre_local.'\')" data-toggle="modal" data-target="#detalles"><i class="fa fa-eye"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="eliminar('.$row->id.')" data-toggle="modal" data-target="#eliminar_insumo"><i class="fa fa-trash"></i></button>
                    <a href="'.route('insumos.barcode_pdf', $row->id).'" target="_blank" class="btn btn-dark btn-sm" title="Imprimir Código de Barras">
                        <i class="fa fa-barcode"></i>
                    </a>
                </div>';
            })
            ->rawColumns(['serial', 'producto', 'estado_global', 'estado_local', 'stock_min', 'stock_max', 'cantidad', 'nombre_local', 'acciones'])
            ->make(true);
    }

    public function storeRapido(Request $request)
    {
        // 1. Autorización y Validación
        $this->authorize('gestionar-insumos');
        
        $request->validate([
            'producto'        => 'required|string|max:255',
            'descripcion'     => 'nullable|string',
            'categoria_id'    => 'required|exists:categorias,id',
            'costo'           => 'required|numeric|min:0',
            'modelo_venta_id' => 'required|exists:modelos_venta,id',
            'id_local'        => 'required|exists:local,id',
            'cantidad'        => 'required|integer|min:1',
        ]);

        // 2. Serial y Cálculo de Precios
        $serial = $request->filled('serial') 
                ? $request->serial 
                : $this->generarSerialInsumo($request->categoria_id);

        $modelo = ModeloVenta::findOrFail($request->modelo_venta_id);
        $precios = $modelo->calcularPrecios($request->costo);

        $stockMinimo = 10;
        $stockMaximo = 100;

        DB::beginTransaction();
        try {
            // 3. Creación del Insumo
            $insumo = Insumos::create([
                'producto'          => $request->producto,
                'descripcion'       => $request->descripcion ?? $request->producto,
                'serial'            => $serial,
                'categoria_id'      => $request->categoria_id,
                'stock_min'         => $stockMinimo,
                'stock_max'         => $stockMaximo,
                'costo'             => $request->costo,
                'modelo_venta_id'   => $request->modelo_venta_id,
                'precio_venta_usd'  => $precios['precio_venta_usd'],
                'precio_venta_bs'   => $precios['precio_venta_bs'],
                'precio_venta_usdt' => $precios['precio_venta_usdt']
            ]);

            // 4. Existencia Inicial en InsumosC
            $cantidadInicial = (int) $request->cantidad;

            InsumosC::create([
                'id_insumo' => $insumo->id,
                'id_local'  => $request->id_local,
                'cantidad'  => $cantidadInicial,
            ]);

            // 5. Notificación Protegida
            if ($cantidadInicial <= $stockMinimo) {
                try {
                    $gerentes = User::whereIn('role', ['admin', 'encargado','almacenista'])->get();
                    $detalles = [
                        'titulo'  => '¡Stock Inicial Bajo!',
                        'mensaje' => "El producto {$insumo->producto} inició con stock crítico ({$cantidadInicial}) en el local.",
                        'url'     => route('insumos.index'),
                        'icono'   => 'fas fa-exclamation-triangle'
                    ];
                    
                    foreach ($gerentes as $gerente) {
                        $gerente->notify(new StockBajoNotification($detalles));
                    }
                } catch (\Exception $eNotif) {
                    // Silenciamos fallo puntual de correo/notificación
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Insumo creado y asignado con éxito',
                'insumo'  => $insumo,
                'stock'   => $cantidadInicial
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar insumo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generarCodigoBarrasPdf($id)
    {
        $insumo = DB::table('insumos')->where('id', $id)->first();

        if (!$insumo) {
            abort(404, 'El insumo no existe.');
        }

        $generator = new BarcodeGeneratorPNG();
        $barcodeBase64 = base64_encode(
            $generator->getBarcode($insumo->serial, $generator::TYPE_CODE_128)
        );

        // Actualizado a 32 para aprovechar la grilla 4x8 en la hoja Carta
        $cantidadEtiquetas = 32;

        $pdf = Pdf::loadView('inventario.insumos.pdf_barcode', compact('insumo', 'barcodeBase64', 'cantidadEtiquetas'))
                  ->setPaper('letter', 'portrait');

        return $pdf->stream("etiquetas_{$insumo->serial}.pdf");
    }

    // 1. Cargar la vista principal del carrito
    public function etiquetasView()
    {
        return view('inventario.insumos.etiquetas');
    }

    // 2. Buscador en tiempo real para el selector
    public function buscarInsumosAjax(Request $request)
    {
        $search = trim($request->get('q'));

        $insumos = DB::table('insumos')
            ->where(function ($query) use ($search) {
                $query->where('serial', 'LIKE', "%{$search}%")
                      ->orWhere('producto', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%");
            })
            ->select('id', 'serial', 'producto', 'descripcion')
            ->limit(20)
            ->get();

        return response()->json($insumos);
    }

    // 3. Procesar el formulario del carrito y generar el PDF
    public function generarCodigosBarrasPdfMultiple(Request $request)
    {
        $items = $request->input('items', []); // Array de ['id' => X, 'hojas' => Y]

        if (empty($items)) {
            return back()->with('error', 'No hay insumos en la cola de impresión.');
        }

        $generator = new BarcodeGeneratorPNG();
        $listaImpresion = [];

        foreach ($items as $item) {
            $insumo = DB::table('insumos')->where('id', $item['id'])->first();
            if ($insumo) {
                $barcodeBase64 = base64_encode(
                    $generator->getBarcode($insumo->serial, $generator::TYPE_CODE_128)
                );

                // Cada "hoja" contiene 24 stickers del mismo insumo
                $hojas = max(1, intval($item['hojas'] ?? 1));

                for ($h = 0; $h < $hojas; $h++) {
                    $listaImpresion[] = [
                        'insumo' => $insumo,
                        'barcodeBase64' => $barcodeBase64
                    ];
                }
            }
        }

        $pdf = Pdf::loadView('inventario.insumos.pdf_barcode_multiple', compact('listaImpresion'))
                  ->setPaper('letter', 'portrait');

        return $pdf->stream("etiquetas_lote.pdf");
    }

    public function albumIndex($id)
    {
        Gate::authorize('ver-logistica');
        
        $insumo = Insumos::with('fotos')->findOrFail($id);
        
        return view('inventario.insumos.album', compact('insumo'));
    }

    // Actualización del método en InsumosController.php para recibir el campo de título opcional
    public function albumStoreMultiple(Request $request, $id)
    {
        Gate::authorize('gestionar-insumos');
        
        // Aumentamos el límite de validación a 20MB (20480 KB) para permitir fotos profesionales de alta calidad
        $request->validate([
            'fotos'   => 'required',
            'fotos.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:20480',
            'titulo'  => 'nullable|string|max:150'
        ]);

        $insumo = Insumos::with('fotos')->findOrFail($id);

        if ($request->hasFile('fotos')) {
            $files = $request->file('fotos');
            $tienePrincipal = $insumo->fotos->where('es_principal', true)->count() > 0;
            $randomIndex = (!$tienePrincipal) ? rand(0, count($files) - 1) : -1;
            $tituloBase = $request->input('titulo');

            // Directorio para miniaturas de carga rápida
            $thumbPathDir = public_path('albumes/thumbs');
            if (!file_exists($thumbPathDir)) {
                mkdir($thumbPathDir, 0755, true);
            }

            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    continue;
                }

                // Nombre único compartido para el original y su miniatura
                $nombreArchivo = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // 1. GUARDAR LA FOTO ORIGINAL INTACTA en public/albumes
                $file->move(public_path('albumes'), $nombreArchivo);
                $pathOriginal = 'albumes/' . $nombreArchivo;

                // 2. GENERAR MINIATURA LIGERA (400x400px) para que la galería vuele sin afectar el original
                $this->generarMiniatura(public_path($pathOriginal), $thumbPathDir . '/' . $nombreArchivo, 400, 400, 75);

                $esPrincipal = (!$tienePrincipal && $index === $randomIndex);
                
                if ($esPrincipal) {
                    $tienePrincipal = true;
                }

                if (!empty($tituloBase)) {
                    $tituloFinal = count($files) > 1 ? "{$tituloBase} (" . ($index + 1) . ")" : $tituloBase;
                } else {
                    $tituloFinal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                }

                $insumo->fotos()->create([
                    'ruta'         => $pathOriginal,
                    'titulo'       => $tituloFinal,
                    'es_principal' => $esPrincipal
                ]);
            }
        }

        return redirect()->route('insumos.album', $id)->with('success', 'Fotografías de alta calidad agregadas con éxito.');
    }

    public function albumUpdateFoto(Request $request, $fotoId)
    {
        Gate::authorize('gestionar-insumos');
        $request->validate(['titulo' => 'nullable|string|max:150']);
        
        $foto = InsumoFoto::findOrFail($fotoId);
        $foto->update(['titulo' => $request->titulo]);

        return response()->json(['success' => true, 'message' => 'Título actualizado correctamente.']);
    }

    public function albumDestroyFoto($fotoId)
    {
        Gate::authorize('gestionar-insumos');
        $foto = InsumoFoto::findOrFail($fotoId);
        
        // 1. Eliminar archivo original físico
        if (file_exists(public_path($foto->ruta))) {
            unlink(public_path($foto->ruta));
        }

        // 2. Eliminar miniatura asociada si existe
        $nombreArchivo = basename($foto->ruta);
        $rutaThumb = public_path('albumes/thumbs/' . $nombreArchivo);
        if (file_exists($rutaThumb)) {
            unlink($rutaThumb);
        }

        $insumoId = $foto->insumo_id;
        $eraPrincipal = $foto->es_principal;
        
        $foto->delete();

        if ($eraPrincipal) {
            $siguienteFoto = InsumoFoto::where('insumo_id', $insumoId)->inRandomOrder()->first();
            if ($siguienteFoto) {
                $siguienteFoto->update(['es_principal' => true]);
            }
        }

        return redirect()->back()->with('success', 'Foto eliminada correctamente.');
    }

    public function albumSetPrincipal($fotoId)
    {
        Gate::authorize('gestionar-insumos');
        $foto = InsumoFoto::findOrFail($fotoId);

        InsumoFoto::where('insumo_id', $foto->insumo_id)->update(['es_principal' => false]);
        $foto->update(['es_principal' => true]);

        return redirect()->back()->with('success', 'Foto establecida como principal exitosamente.');
    }

    public function albumGeneral(Request $request)
    {
        // Optimizamos seleccionando únicamente las columnas necesarias de la relación
        $fotos = InsumoFoto::with(['insumo' => function($query) {
            $query->select('id', 'producto', 'serial', 'descripcion');
        }])->latest()->paginate(20);

        if ($request->ajax()) {
            $fotosData = $fotos->map(function($foto) {
                $nombreArchivo = basename($foto->ruta);
                $rutaThumb = asset('albumes/thumbs/' . $nombreArchivo);

                return [
                    'id' => $foto->id,
                    'ruta' => asset($foto->ruta),
                    'thumb' => $rutaThumb,
                    'titulo' => $foto->titulo ?: 'Sin título',
                    'producto' => optional($foto->insumo)->producto ?? 'Sin producto',
                    'serial' => optional($foto->insumo)->serial ?? 'N/A',
                    'descripcion' => optional($foto->insumo)->descripcion ?? 'Sin descripción registrada.',
                    'es_principal' => $foto->es_principal
                ];
            });

            return response()->json([
                'html' => view('inventario.insumos.partials.grid_items', compact('fotos'))->render(),
                'fotos' => $fotosData,
                'has_more' => $fotos->hasMorePages()
            ]);
        }

        return view('inventario.insumos.album_general', compact('fotos'));
    }

    private function generarMiniatura($rutaOrigen, $rutaDestino, $nuevoAncho, $nuevoAlto, $calidad)
    {
        $info = @getimagesize($rutaOrigen);
        if (!$info) return;

        list($anchoOriginal, $altoOriginal, $tipo) = $info;

        switch ($tipo) {
            case IMAGETYPE_JPEG:
                $imgOriginal = @imagecreatefromjpeg($rutaOrigen);
                break;
            case IMAGETYPE_PNG:
                $imgOriginal = @imagecreatefrompng($rutaOrigen);
                if ($imgOriginal) {
                    imagepalettetotruecolor($imgOriginal);
                    imagealphablending($imgOriginal, true);
                    imagesavealpha($imgOriginal, true);
                }
                break;
            case IMAGETYPE_WEBP:
                $imgOriginal = @imagecreatefromwebp($rutaOrigen);
                break;
            default:
                return;
        }

        if (!$imgOriginal) return;

        $ratio = $anchoOriginal / $altoOriginal;
        if ($nuevoAncho / $nuevoAlto > $ratio) {
            $nuevoAncho = $nuevoAlto * $ratio;
        } else {
            $nuevoAlto = $nuevoAncho / $ratio;
        }

        $imgMiniatura = imagecreatetruecolor((int)$nuevoAncho, (int)$nuevoAlto);

        if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_WEBP) {
            imagecolortransparent($imgMiniatura, imagecolorallocatealpha($imgMiniatura, 0, 0, 0, 127));
            imagealphablending($imgMiniatura, false);
            imagesavealpha($imgMiniatura, true);
        }

        imagecopyresampled($imgMiniatura, $imgOriginal, 0, 0, 0, 0, (int)$nuevoAncho, (int)$nuevoAlto, $anchoOriginal, $altoOriginal);

        $extension = strtolower(pathinfo($rutaDestino, PATHINFO_EXTENSION));
        if ($extension == 'png') {
            imagepng($imgMiniatura, $rutaDestino, 6);
        } else {
            imagejpeg($imgMiniatura, $rutaDestino, $calidad);
        }

        imagedestroy($imgOriginal);
        imagedestroy($imgMiniatura);
    }

    public function verificarDescripcion(Request $request)
    {
        $texto = $request->input('query');

        if (!$texto) {
            return response()->json(['coincidencias' => []]);
        }

        // Separar el texto por espacios y eliminar elementos vacíos
        $palabras = array_filter(explode(' ', $texto));

        // Iniciar la consulta
        $query = DB::table('insumos')->select('producto', 'descripcion');

        // Buscar cada palabra dentro del campo descripción
        foreach ($palabras as $palabra) {
            $query->where('descripcion', 'LIKE', '%' . $palabra . '%');
        }

        // Limitamos a 5 para no saturar la vista si hay muchas coincidencias parciales
        $coincidencias = $query->limit(5)->get();

        return response()->json([
            'coincidencias' => $coincidencias
        ]);
    }
}

