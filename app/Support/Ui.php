<?php

namespace App\Support;

/** Kurze Bedientexte der Handy-Seite in mehreren Sprachen. */
class Ui
{
    private const TEXTS = [
        'de' => ['cart' => 'Warenkorb', 'your_order' => 'Ihre Bestellung', 'total' => 'Summe', 'table' => 'Tischnummer', 'name' => 'Ihr Name (für die Abholung)', 'note' => 'Anmerkung (optional)', 'send' => 'Jetzt bestellen', 'back' => 'Weiter auswählen', 'error' => 'Bestellung fehlgeschlagen. Bitte erneut versuchen.'],
        'en' => ['cart' => 'Cart', 'your_order' => 'Your order', 'total' => 'Total', 'table' => 'Table number', 'name' => 'Your name (for pickup)', 'note' => 'Note (optional)', 'send' => 'Place order', 'back' => 'Continue browsing', 'error' => 'Order failed. Please try again.'],
        'it' => ['cart' => 'Carrello', 'your_order' => 'Il tuo ordine', 'total' => 'Totale', 'table' => 'Numero del tavolo', 'name' => 'Il tuo nome (per il ritiro)', 'note' => 'Nota (facoltativa)', 'send' => 'Ordina ora', 'back' => 'Continua', 'error' => 'Ordine non riuscito. Riprova.'],
        'fr' => ['cart' => 'Panier', 'your_order' => 'Votre commande', 'total' => 'Total', 'table' => 'Numéro de table', 'name' => 'Votre nom (pour le retrait)', 'note' => 'Remarque (facultatif)', 'send' => 'Commander', 'back' => 'Continuer', 'error' => 'Échec de la commande. Veuillez réessayer.'],
        'es' => ['cart' => 'Carrito', 'your_order' => 'Tu pedido', 'total' => 'Total', 'table' => 'Número de mesa', 'name' => 'Tu nombre (para recoger)', 'note' => 'Nota (opcional)', 'send' => 'Pedir ahora', 'back' => 'Seguir', 'error' => 'El pedido falló. Inténtalo de nuevo.'],
        'tr' => ['cart' => 'Sepet', 'your_order' => 'Siparişiniz', 'total' => 'Toplam', 'table' => 'Masa numarası', 'name' => 'Adınız (teslim için)', 'note' => 'Not (isteğe bağlı)', 'send' => 'Sipariş ver', 'back' => 'Devam et', 'error' => 'Sipariş başarısız. Lütfen tekrar deneyin.'],
    ];

    public static function mobile(?string $lang): array
    {
        if (! $lang || $lang === 'de') return self::TEXTS['de'];
        return self::TEXTS[$lang] ?? self::TEXTS['en'];
    }
}
