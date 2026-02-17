<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Insumos;
use App\Models\Local;
use App\Models\Incidencias;
use Illuminate\Support\Facades\Http;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        // Tus contadores actuales
        $i = Insumos::count();
        $in = Incidencias::count();

        // --- Lógica para obtener Tasas ---
        $tasa_bcv = 0;
        $tasa_binance = 0;

        try {
            // 1. Obtener BCV (Scraping simple)
            // Desactivamos verificación SSL porque a veces el certificado del BCV da problemas
            $responseBcv = Http::withOptions(['verify' => false])->get('https://www.bcv.org.ve/');
            if ($responseBcv->successful()) {
                preg_match('/id="dolar".*?<strong>\s*(.*?)\s*<\/strong>/s', $responseBcv->body(), $matches);
                $tasa_bcv = isset($matches[1]) ? (float) str_replace(',', '.', trim($matches[1])) : 0;
            }

            // 2. Obtener Binance (API P2P con Headers)
                $responseBinance = Http::withOptions([
                    'verify' => false, // ESTO ES CLAVE EN WAMP
                ])->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search', [
                    "asset"         => "USDT",
                    "tradeType"     => "BUY", // Queremos ver a cuánto están vendiendo los comerciantes
                    "fiat"          => "VES",
                    "transAmount"   => "0",
                    "order"         => "",
                    "page"          => 1,
                    "rows"          => 5, // Traemos los primeros 5 para promediar
                    "filterType"    => "all"
                ]);

                if ($responseBinance->successful()) {
                    $data = $responseBinance->json();
                    
                    if (isset($data['data']) && count($data['data']) > 0) {
                        // Sacamos los precios de los anuncios recibidos
                        $precios = [];
                        foreach ($data['data'] as $anuncio) {
                            $precios[] = (float) $anuncio['adv']['price'];
                        }
                        
                        // Calculamos el promedio de los primeros anuncios para ser más exactos
                        $tasa_binance = array_sum($precios) / count($precios);
                    }
                }
        } catch (\Exception $e) {
            // Si algo falla, las tasas quedan en 0 para no romper la vista
        }

        return view('home', compact('i', 'in', 'tasa_bcv', 'tasa_binance'));
    
    }
}
