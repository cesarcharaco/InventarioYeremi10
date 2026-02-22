<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proveedor;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            [
                'nombre' => 'Repuestos y Rodamientos El Gocho, C.A.',
                'rif' => 'J-31245678-0',
                'telefono' => '0246-4315566',
                'email' => 'ventas@elgocho.com',
                'direccion' => 'Zona Industrial, San Juan de los Morros'
            ],
            [
                'nombre' => 'Inversiones Global Moto, S.A.',
                'rif' => 'J-40556789-1',
                'telefono' => '0414-5552211',
                'email' => 'globalmoto@gmail.com',
                'direccion' => 'Av. Bolívar, Local 45, Caracas'
            ],
            [
                'nombre' => 'Lubricantes y Filtros del Centro',
                'rif' => 'J-30998877-5',
                'telefono' => '0424-3334455',
                'email' => 'distribucion@lucentro.com',
                'direccion' => 'Valencia, Edo. Carabobo'
            ],
            [
                'nombre' => 'Corporación Akita Repuestos',
                'rif' => 'J-50123123-9',
                'telefono' => '0212-9998877',
                'email' => 'info@akita.com.ve',
                'direccion' => 'La Yaguara, Calle 4, Caracas'
            ],
            [
                'nombre' => 'Cauchos Guárico, C.A.',
                'rif' => 'J-30554422-3',
                'telefono' => '0246-4321199',
                'email' => 'cauchosguarico@hotmail.com',
                'direccion' => 'Vía Villa de Cura, San Juan'
            ],
            [
                'nombre' => 'Suministros Industriales Yerem',
                'rif' => 'J-29887766-4',
                'telefono' => '0412-1112233',
                'email' => 'almacen@yerem.com',
                'direccion' => 'Cerca del terminal de pasajeros'
            ],
            [
                'nombre' => 'Importadora de Motores Japan, S.A.',
                'rif' => 'J-40112233-8',
                'telefono' => '0243-2334455',
                'email' => 'ventas@japanmoto.com',
                'direccion' => 'Maracay, Edo. Aragua'
            ],
            [
                'nombre' => 'Baterías Duncan S.A.S.',
                'rif' => 'J-00012345-6',
                'telefono' => '0800-DUNCAN-0',
                'email' => 'servicios@duncan.com.ve',
                'direccion' => 'Distribuidora Principal Caracas'
            ],
            [
                'nombre' => 'Frenos y Bandas El Frenazo',
                'rif' => 'J-31445566-7',
                'telefono' => '0416-7778899',
                'email' => 'frenazo_ventas@yahoo.es',
                'direccion' => 'Calle Paéz, local 12'
            ],
            [
                'nombre' => 'Herramientas y Tornillos San Juan',
                'rif' => 'V-15887744-2',
                'telefono' => '0246-4310000',
                'email' => 'tornillos_sj@gmail.com',
                'direccion' => 'Centro Comercial Colonial'
            ],
        ];

        foreach ($proveedores as $p) {
            Proveedor::create($p);
        }
    }
}