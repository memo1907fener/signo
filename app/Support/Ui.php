<?php

namespace App\Support;

/** Kurze Bedientexte der Gäste-Seiten (Handy, Bildschirm) in mehreren Sprachen. */
class Ui
{
    private const TEXTS = [
        'de' => ['cart' => 'Warenkorb', 'your_order' => 'Ihre Bestellung', 'total' => 'Summe', 'table' => 'Tischnummer', 'name' => 'Ihr Name (für die Abholung)', 'note' => 'Anmerkung (optional)', 'send' => 'Jetzt bestellen', 'back' => 'Weiter auswählen', 'error' => 'Bestellung fehlgeschlagen. Bitte erneut versuchen.',
            'soldout' => 'Ausverkauft', 'today' => 'Heute', 'no_menu' => 'Heute kein Menü', 'menu' => 'Menü', 'until' => 'bis', 'filter' => 'Filter', 'only' => 'Nur', 'without' => 'Ohne', 'allergens' => 'Allergene', 'reset' => 'Zurücksetzen',
            'feedback' => 'Wie war es bei uns?', 'stamps' => 'Stempelkarte'],
        'en' => ['cart' => 'Cart', 'your_order' => 'Your order', 'total' => 'Total', 'table' => 'Table number', 'name' => 'Your name (for pickup)', 'note' => 'Note (optional)', 'send' => 'Place order', 'back' => 'Continue browsing', 'error' => 'Order failed. Please try again.',
            'soldout' => 'Sold out', 'today' => 'Today', 'no_menu' => 'No menu today', 'menu' => 'Menu', 'until' => 'until', 'filter' => 'Filter', 'only' => 'Only', 'without' => 'Without', 'allergens' => 'Allergens', 'reset' => 'Reset',
            'feedback' => 'How was your visit?', 'stamps' => 'Stamp card'],
        'it' => ['cart' => 'Carrello', 'your_order' => 'Il tuo ordine', 'total' => 'Totale', 'table' => 'Numero del tavolo', 'name' => 'Il tuo nome (per il ritiro)', 'note' => 'Nota (facoltativa)', 'send' => 'Ordina ora', 'back' => 'Continua', 'error' => 'Ordine non riuscito. Riprova.',
            'soldout' => 'Esaurito', 'today' => 'Oggi', 'no_menu' => 'Oggi nessun menù', 'menu' => 'Menù', 'until' => 'fino alle', 'filter' => 'Filtro', 'only' => 'Solo', 'without' => 'Senza', 'allergens' => 'Allergeni', 'reset' => 'Azzera',
            'feedback' => 'Com\'è andata da noi?', 'stamps' => 'Carta fedeltà'],
        'fr' => ['cart' => 'Panier', 'your_order' => 'Votre commande', 'total' => 'Total', 'table' => 'Numéro de table', 'name' => 'Votre nom (pour le retrait)', 'note' => 'Remarque (facultatif)', 'send' => 'Commander', 'back' => 'Continuer', 'error' => 'Échec de la commande. Veuillez réessayer.',
            'soldout' => 'Épuisé', 'today' => 'Aujourd\'hui', 'no_menu' => 'Pas de menu aujourd\'hui', 'menu' => 'Menu', 'until' => 'jusqu\'à', 'filter' => 'Filtrer', 'only' => 'Seulement', 'without' => 'Sans', 'allergens' => 'Allergènes', 'reset' => 'Réinitialiser',
            'feedback' => 'Comment était votre visite ?', 'stamps' => 'Carte de fidélité'],
        'es' => ['cart' => 'Carrito', 'your_order' => 'Tu pedido', 'total' => 'Total', 'table' => 'Número de mesa', 'name' => 'Tu nombre (para recoger)', 'note' => 'Nota (opcional)', 'send' => 'Pedir ahora', 'back' => 'Seguir', 'error' => 'El pedido falló. Inténtalo de nuevo.',
            'soldout' => 'Agotado', 'today' => 'Hoy', 'no_menu' => 'Hoy no hay menú', 'menu' => 'Menú', 'until' => 'hasta', 'filter' => 'Filtrar', 'only' => 'Solo', 'without' => 'Sin', 'allergens' => 'Alérgenos', 'reset' => 'Restablecer',
            'feedback' => '¿Qué tal tu visita?', 'stamps' => 'Tarjeta de sellos'],
        'tr' => ['cart' => 'Sepet', 'your_order' => 'Siparişiniz', 'total' => 'Toplam', 'table' => 'Masa numarası', 'name' => 'Adınız (teslim için)', 'note' => 'Not (isteğe bağlı)', 'send' => 'Sipariş ver', 'back' => 'Devam et', 'error' => 'Sipariş başarısız. Lütfen tekrar deneyin.',
            'soldout' => 'Tükendi', 'today' => 'Bugün', 'no_menu' => 'Bugün menü yok', 'menu' => 'Menü', 'until' => 'saat', 'filter' => 'Filtre', 'only' => 'Sadece', 'without' => 'İçermeyen', 'allergens' => 'Alerjenler', 'reset' => 'Sıfırla',
            'feedback' => 'Bizi nasıl buldunuz?', 'stamps' => 'Damga kartı'],
    ];

    // Reihenfolge wie config('signo.allergens'): A B C D E F G H L M N O P R
    private const ALLERGENS = [
        'en' => ['Gluten', 'Crustaceans', 'Egg', 'Fish', 'Peanuts', 'Soy', 'Milk', 'Tree nuts', 'Celery', 'Mustard', 'Sesame', 'Sulphites', 'Lupin', 'Molluscs'],
        'it' => ['Glutine', 'Crostacei', 'Uova', 'Pesce', 'Arachidi', 'Soia', 'Latte', 'Frutta a guscio', 'Sedano', 'Senape', 'Sesamo', 'Solfiti', 'Lupini', 'Molluschi'],
        'fr' => ['Gluten', 'Crustacés', 'Œuf', 'Poisson', 'Arachides', 'Soja', 'Lait', 'Fruits à coque', 'Céleri', 'Moutarde', 'Sésame', 'Sulfites', 'Lupin', 'Mollusques'],
        'es' => ['Gluten', 'Crustáceos', 'Huevo', 'Pescado', 'Cacahuetes', 'Soja', 'Leche', 'Frutos secos', 'Apio', 'Mostaza', 'Sésamo', 'Sulfitos', 'Altramuces', 'Moluscos'],
        'tr' => ['Gluten', 'Kabuklular', 'Yumurta', 'Balık', 'Yer fıstığı', 'Soya', 'Süt', 'Sert kabuklu yemişler', 'Kereviz', 'Hardal', 'Susam', 'Sülfitler', 'Acı bakla', 'Yumuşakçalar'],
    ];

    // Reihenfolge wie config('signo.marks'): vegan veg hot halal bio new
    private const MARKS = [
        'en' => ['Vegan', 'Vegetarian', 'Spicy', 'Halal', 'Organic', 'New'],
        'it' => ['Vegano', 'Vegetariano', 'Piccante', 'Halal', 'Bio', 'Novità'],
        'fr' => ['Végan', 'Végétarien', 'Épicé', 'Halal', 'Bio', 'Nouveau'],
        'es' => ['Vegano', 'Vegetariano', 'Picante', 'Halal', 'Ecológico', 'Nuevo'],
        'tr' => ['Vegan', 'Vejetaryen', 'Acılı', 'Helal', 'Organik', 'Yeni'],
    ];

    public static function mobile(?string $lang): array
    {
        if (! $lang || $lang === 'de') return self::TEXTS['de'];
        return self::TEXTS[$lang] ?? self::TEXTS['en'];
    }

    /** Bezeichnungen aller Kennzeichen in der Sprache des Gastes: ['A' => 'Gluten', 'vegan' => 'Vegan', …]. */
    public static function tagLabels(?string $lang): array
    {
        $allergens = config('signo.allergens');
        $marks = array_map(fn ($m) => $m['label'], config('signo.marks'));
        if ($lang && $lang !== 'de') {
            $l = isset(self::ALLERGENS[$lang]) ? $lang : 'en';
            $allergens = array_combine(array_keys($allergens), self::ALLERGENS[$l]);
            $marks = array_combine(array_keys($marks), self::MARKS[$l]);
        }
        return $allergens + $marks;
    }

    /** Wochentag ausgeschrieben, 0 = Montag. */
    public static function weekday(int $index, ?string $lang): string
    {
        try {
            return \Illuminate\Support\Carbon::create(2024, 1, 1 + $index)->locale($lang ?: 'de')->isoFormat('dddd');
        } catch (\Throwable $e) {
            return ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'][$index] ?? '';
        }
    }
}
