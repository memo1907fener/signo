<?php

namespace App\Support;

/** Texte der Feedback- und Stempelkarten-Seiten für Gäste. */
class GuestTexts
{
    private const TEXTS = [
        'de' => [
            'fb_question' => 'Wie hat es Ihnen gefallen?', 'fb_comment' => 'Möchten Sie uns noch etwas sagen? (optional)', 'fb_improve' => 'Was können wir besser machen?',
            'fb_contact' => 'E-Mail oder Telefon, falls wir uns melden dürfen (optional)', 'fb_send' => 'Absenden', 'fb_thanks' => 'Vielen Dank!',
            'fb_thanks_text' => 'Ihre Rückmeldung ist direkt bei uns angekommen.', 'fb_google' => 'Auf Google bewerten', 'fb_google_text' => 'Sie helfen uns sehr, wenn Sie Ihre Meinung auch öffentlich teilen.',
            'fb_pick' => 'Bitte Sterne antippen.', 'error' => 'Das hat nicht geklappt. Bitte erneut versuchen.',
            'st_of' => ':n von :goal Stempeln', 'st_get' => 'Stempel holen', 'st_hand' => 'Bitte geben Sie Ihr Handy kurz dem Personal.', 'st_pin' => 'Code des Personals',
            'st_count' => 'Anzahl Stempel', 'st_confirm' => 'Stempeln', 'st_cancel' => 'Abbrechen', 'st_wrong' => 'Der Code stimmt nicht.', 'st_locked' => 'Zu viele Versuche. Bitte später erneut.',
            'st_limit' => 'Heute sind keine weiteren Stempel möglich.', 'st_ready' => 'Karte voll – Belohnung wartet!', 'st_redeem' => 'Belohnung einlösen', 'st_redeemed' => 'Eingelöst. Guten Appetit!',
            'st_reward' => 'Belohnung', 'st_save' => 'Tipp: Diese Seite zum Startbildschirm hinzufügen, dann ist Ihre Karte immer dabei.', 'st_off' => 'Die Stempelkarte ist derzeit nicht aktiv.',
        ],
        'en' => [
            'fb_question' => 'How did you like it?', 'fb_comment' => 'Anything you would like to tell us? (optional)', 'fb_improve' => 'What can we do better?',
            'fb_contact' => 'Email or phone if we may get back to you (optional)', 'fb_send' => 'Send', 'fb_thanks' => 'Thank you!',
            'fb_thanks_text' => 'Your feedback has reached us directly.', 'fb_google' => 'Review us on Google', 'fb_google_text' => 'It helps us a lot if you share your opinion publicly as well.',
            'fb_pick' => 'Please tap the stars.', 'error' => 'That did not work. Please try again.',
            'st_of' => ':n of :goal stamps', 'st_get' => 'Get a stamp', 'st_hand' => 'Please hand your phone to the staff for a moment.', 'st_pin' => 'Staff code',
            'st_count' => 'Number of stamps', 'st_confirm' => 'Stamp', 'st_cancel' => 'Cancel', 'st_wrong' => 'The code is not correct.', 'st_locked' => 'Too many attempts. Please try again later.',
            'st_limit' => 'No more stamps possible today.', 'st_ready' => 'Card complete – your reward is waiting!', 'st_redeem' => 'Redeem reward', 'st_redeemed' => 'Redeemed. Enjoy!',
            'st_reward' => 'Reward', 'st_save' => 'Tip: add this page to your home screen so your card is always with you.', 'st_off' => 'The stamp card is currently not active.',
        ],
        'tr' => [
            'fb_question' => 'Bizi nasıl buldunuz?', 'fb_comment' => 'Bize iletmek istediğiniz bir şey var mı? (isteğe bağlı)', 'fb_improve' => 'Neyi daha iyi yapabiliriz?',
            'fb_contact' => 'Size dönmemiz için e-posta veya telefon (isteğe bağlı)', 'fb_send' => 'Gönder', 'fb_thanks' => 'Teşekkür ederiz!',
            'fb_thanks_text' => 'Geri bildiriminiz doğrudan bize ulaştı.', 'fb_google' => 'Google\'da değerlendir', 'fb_google_text' => 'Görüşünüzü herkese açık olarak da paylaşırsanız bize çok yardımcı olursunuz.',
            'fb_pick' => 'Lütfen yıldızlara dokunun.', 'error' => 'Bir sorun oluştu. Lütfen tekrar deneyin.',
            'st_of' => ':goal damgadan :n', 'st_get' => 'Damga al', 'st_hand' => 'Lütfen telefonunuzu kısa süreliğine personele verin.', 'st_pin' => 'Personel kodu',
            'st_count' => 'Damga sayısı', 'st_confirm' => 'Damgala', 'st_cancel' => 'İptal', 'st_wrong' => 'Kod doğru değil.', 'st_locked' => 'Çok fazla deneme. Lütfen daha sonra tekrar deneyin.',
            'st_limit' => 'Bugün daha fazla damga verilemez.', 'st_ready' => 'Kart doldu – ödülünüz hazır!', 'st_redeem' => 'Ödülü kullan', 'st_redeemed' => 'Kullanıldı. Afiyet olsun!',
            'st_reward' => 'Ödül', 'st_save' => 'İpucu: Bu sayfayı ana ekranınıza ekleyin, kartınız hep yanınızda olsun.', 'st_off' => 'Damga kartı şu anda aktif değil.',
        ],
        'it' => [
            'fb_question' => 'Com\'è andata?', 'fb_comment' => 'Vuole dirci qualcos\'altro? (facoltativo)', 'fb_improve' => 'Cosa possiamo migliorare?',
            'fb_contact' => 'E-mail o telefono per ricontattarla (facoltativo)', 'fb_send' => 'Invia', 'fb_thanks' => 'Grazie mille!',
            'fb_thanks_text' => 'Il suo riscontro ci è arrivato direttamente.', 'fb_google' => 'Recensisci su Google', 'fb_google_text' => 'Ci aiuta molto se condivide la sua opinione anche pubblicamente.',
            'fb_pick' => 'Tocchi le stelle.', 'error' => 'Non ha funzionato. Riprovi.',
            'st_of' => ':n timbri su :goal', 'st_get' => 'Ottieni un timbro', 'st_hand' => 'Consegni un attimo il telefono al personale.', 'st_pin' => 'Codice del personale',
            'st_count' => 'Numero di timbri', 'st_confirm' => 'Timbra', 'st_cancel' => 'Annulla', 'st_wrong' => 'Il codice non è corretto.', 'st_locked' => 'Troppi tentativi. Riprovi più tardi.',
            'st_limit' => 'Oggi non sono possibili altri timbri.', 'st_ready' => 'Carta completa – il premio la aspetta!', 'st_redeem' => 'Riscatta il premio', 'st_redeemed' => 'Riscattato. Buon appetito!',
            'st_reward' => 'Premio', 'st_save' => 'Consiglio: aggiunga questa pagina alla schermata Home per avere la carta sempre con sé.', 'st_off' => 'La carta fedeltà al momento non è attiva.',
        ],
        'fr' => [
            'fb_question' => 'Comment avez-vous trouvé votre visite ?', 'fb_comment' => 'Souhaitez-vous ajouter quelque chose ? (facultatif)', 'fb_improve' => 'Que pouvons-nous améliorer ?',
            'fb_contact' => 'E-mail ou téléphone si nous pouvons vous recontacter (facultatif)', 'fb_send' => 'Envoyer', 'fb_thanks' => 'Merci beaucoup !',
            'fb_thanks_text' => 'Votre avis nous est parvenu directement.', 'fb_google' => 'Donner un avis sur Google', 'fb_google_text' => 'Partager votre avis publiquement nous aide beaucoup.',
            'fb_pick' => 'Veuillez toucher les étoiles.', 'error' => 'Cela n\'a pas fonctionné. Veuillez réessayer.',
            'st_of' => ':n tampons sur :goal', 'st_get' => 'Obtenir un tampon', 'st_hand' => 'Veuillez confier votre téléphone un instant au personnel.', 'st_pin' => 'Code du personnel',
            'st_count' => 'Nombre de tampons', 'st_confirm' => 'Tamponner', 'st_cancel' => 'Annuler', 'st_wrong' => 'Le code est incorrect.', 'st_locked' => 'Trop de tentatives. Réessayez plus tard.',
            'st_limit' => 'Plus de tampons possibles aujourd\'hui.', 'st_ready' => 'Carte complète – votre récompense vous attend !', 'st_redeem' => 'Utiliser la récompense', 'st_redeemed' => 'Utilisée. Bon appétit !',
            'st_reward' => 'Récompense', 'st_save' => 'Astuce : ajoutez cette page à votre écran d\'accueil pour avoir toujours votre carte.', 'st_off' => 'La carte de fidélité n\'est pas active actuellement.',
        ],
        'es' => [
            'fb_question' => '¿Qué le ha parecido?', 'fb_comment' => '¿Quiere contarnos algo más? (opcional)', 'fb_improve' => '¿Qué podemos mejorar?',
            'fb_contact' => 'Correo o teléfono por si podemos contactarle (opcional)', 'fb_send' => 'Enviar', 'fb_thanks' => '¡Muchas gracias!',
            'fb_thanks_text' => 'Su opinión nos ha llegado directamente.', 'fb_google' => 'Valorar en Google', 'fb_google_text' => 'Nos ayuda mucho si comparte su opinión también públicamente.',
            'fb_pick' => 'Toque las estrellas.', 'error' => 'No ha funcionado. Inténtelo de nuevo.',
            'st_of' => ':n de :goal sellos', 'st_get' => 'Conseguir sello', 'st_hand' => 'Entregue un momento su móvil al personal.', 'st_pin' => 'Código del personal',
            'st_count' => 'Número de sellos', 'st_confirm' => 'Sellar', 'st_cancel' => 'Cancelar', 'st_wrong' => 'El código no es correcto.', 'st_locked' => 'Demasiados intentos. Inténtelo más tarde.',
            'st_limit' => 'Hoy no se pueden añadir más sellos.', 'st_ready' => 'Tarjeta completa: ¡su premio le espera!', 'st_redeem' => 'Canjear premio', 'st_redeemed' => 'Canjeado. ¡Que aproveche!',
            'st_reward' => 'Premio', 'st_save' => 'Consejo: añada esta página a la pantalla de inicio para llevar siempre su tarjeta.', 'st_off' => 'La tarjeta de sellos no está activa en este momento.',
        ],
    ];

    public static function for(?string $lang): array
    {
        return self::TEXTS[$lang] ?? self::TEXTS['de'];
    }

    /** Sprache aus ?lang oder dem Browser; Deutsch als Standard. */
    public static function pick(\Illuminate\Http\Request $request): string
    {
        $q = strtolower((string) $request->query('lang'));
        if (isset(self::TEXTS[$q])) return $q;
        foreach (explode(',', (string) $request->header('Accept-Language')) as $part) {
            $code = strtolower(substr(trim($part), 0, 2));
            if (isset(self::TEXTS[$code])) return $code;
        }
        return 'de';
    }
}
