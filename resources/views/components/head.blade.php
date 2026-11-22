@props(['title' => null])
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title . ' · Signo' : 'Signo – Digitale Anzeigen in Minuten' }}</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<meta name="theme-color" content="#f7f4ef">
@vite(['resources/css/app.css', 'resources/js/app.js'])
