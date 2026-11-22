<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>Bestellung #{{ $order->number }} · {{ $org->name }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink">
<div class="mx-auto flex min-h-screen max-w-md flex-col px-5 py-10 text-center">
    <p class="text-sm font-semibold uppercase tracking-widest text-muted">{{ $org->name }}</p>
    <p class="mt-8 text-sm text-muted">Ihre Bestellnummer · Order number</p>
    <p class="mt-1 text-8xl font-extrabold tracking-tight">#{{ $order->number }}</p>

    <div id="state" class="mt-10 rounded-3xl bg-white p-6 ring-1 ring-black/5"></div>

    <div class="mt-8 rounded-3xl bg-white p-5 text-left text-sm ring-1 ring-black/5">
        @foreach ($order->items as $it)
            <div class="flex justify-between gap-3 py-1"><span><strong>{{ $it['qty'] }}×</strong> {{ $it['name'] }}</span><span class="tabular-nums">{{ \App\Models\Order::euro($it['price'] * $it['qty']) }}</span></div>
        @endforeach
        <div class="mt-2 flex justify-between border-t border-black/10 pt-2 font-bold"><span>Summe</span><span class="tabular-nums">{{ \App\Models\Order::euro($order->total_cents) }}</span></div>
        @if ($org->ordering_note)<p class="mt-3 text-muted">{{ $org->ordering_note }}</p>@endif
    </div>
    <p class="mt-6 text-xs text-muted">Diese Seite aktualisiert sich von selbst · This page updates automatically</p>
</div>
<script>
(function () {
    var texts = {
        new: ['⏳', 'Bestellung eingegangen', 'Order received'],
        preparing: ['👩‍🍳', 'Wird zubereitet', 'Being prepared'],
        ready: ['✅', 'Fertig – bitte abholen!', 'Ready – please collect!'],
        done: ['🙏', 'Guten Appetit!', 'Enjoy!'],
        canceled: ['✕', 'Bestellung storniert – bitte beim Personal melden', 'Order canceled – please ask staff']
    };
    var last = null;
    function render(s) {
        var t = texts[s] || texts.new;
        var el = document.getElementById('state');
        el.innerHTML = '<div style="font-size:48px">' + t[0] + '</div><p class="mt-3 text-2xl font-bold">' + t[1] + '</p><p class="mt-1" style="opacity:.7">' + t[2] + '</p>';
        el.className = 'mt-10 rounded-3xl p-6 ring-1 ' + (s === 'ready' ? 'bg-green-600 text-white ring-green-600' : 'bg-white ring-black/5');
        if (s === 'ready' && last && last !== 'ready') { if (navigator.vibrate) navigator.vibrate([300, 150, 300]); }
        last = s;
    }
    render(@json($order->status));
    function poll() {
        fetch(@json(route('order.status.json', $order->code)), { cache: 'no-store' })
            .then(function (r) { return r.json(); }).then(function (j) { if (j.status !== last) render(j.status); })
            .catch(function () {}).then(function () { if (last !== 'done' && last !== 'canceled') setTimeout(poll, 5000); });
    }
    setTimeout(poll, 5000);
})();
</script>
</body>
</html>
