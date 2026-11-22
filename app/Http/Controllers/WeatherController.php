<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Wetter über Open-Meteo (kostenlos, ohne Schlüssel), 20 Minuten zwischengespeichert. */
class WeatherController extends Controller
{
    public function __invoke(Request $request)
    {
        $city = mb_substr(trim((string) $request->query('city')), 0, 80);
        if ($city === '') return response()->json(['temp' => null]);

        $data = Cache::remember('weather:' . mb_strtolower($city), now()->addMinutes(20), function () use ($city) {
            try {
                $geo = Cache::remember('geo:' . mb_strtolower($city), now()->addDays(30), function () use ($city) {
                    $r = Http::timeout(8)->get('https://geocoding-api.open-meteo.com/v1/search', ['name' => $city, 'count' => 1, 'language' => 'de']);
                    $g = $r->json('results.0');
                    return $g ? ['lat' => $g['latitude'], 'lon' => $g['longitude']] : null;
                });
                if (! $geo) return ['temp' => null];
                $w = Http::timeout(8)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $geo['lat'], 'longitude' => $geo['lon'], 'current' => 'temperature_2m,weather_code', 'timezone' => 'auto',
                ])->json('current');
                return ['temp' => $w['temperature_2m'] ?? null, 'code' => $w['weather_code'] ?? 0];
            } catch (\Throwable $e) {
                return ['temp' => null];
            }
        });

        return response()->json($data)->header('Cache-Control', 'public, max-age=600');
    }
}
