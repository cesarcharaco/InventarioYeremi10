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
