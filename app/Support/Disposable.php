<?php

namespace App\Support;

class Disposable
{
    private const DOMAINS = [
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.de', 'sharklasers.com', '10minutemail.com', '10minutemail.de',
        'tempmail.com', 'temp-mail.org', 'temp-mail.io', 'tempmail.de', 'yopmail.com', 'yopmail.fr', 'trashmail.com',
        'trashmail.de', 'throwawaymail.com', 'getnada.com', 'nada.email', 'dispostable.com', 'maildrop.cc', 'mintemail.com',
        'fakeinbox.com', 'mohmal.com', 'emailondeck.com', 'spamgourmet.com', 'mailnesia.com', 'wegwerfmail.de',
        'wegwerfmail.net', 'einrot.com', 'byom.de', 'mytemp.email', 'tempail.com', 'burnermail.io', 'mail.tm',
        'tmail.ws', 'tmpmail.org', 'emailfake.com', 'inboxkitten.com', 'discard.email', 'spambog.com', 'jetable.org',
        'mvrht.net', 'moakt.com', 'tempr.email', 'harakirimail.com', '33mail.com', 'anonaddy.me', 'grr.la',
        'guerrillamailblock.com', 'pokemail.net', 'spam4.me', 'trbvm.com', 'cuvox.de', 'dayrep.com', 'fleckens.hu',
        'gustr.com', 'jourrapide.com', 'rhyta.com', 'superrito.com', 'teleworm.us', 'armyspy.com',
    ];

    public static function is(string $email): bool
    {
        $domain = strtolower(trim(substr(strrchr($email, '@') ?: '', 1)));
        if ($domain === '') return true;
        foreach (self::DOMAINS as $d) {
            if ($domain === $d || str_ends_with($domain, '.' . $d)) return true;
        }
        return false;
    }
}
