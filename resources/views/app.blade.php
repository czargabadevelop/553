<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
    />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}" />
    <meta name="reverb-host" content="{{ config('broadcasting.client.host') }}" />
    <meta name="reverb-port" content="{{ config('broadcasting.client.port') }}" />
    <meta name="reverb-scheme" content="{{ config('broadcasting.client.scheme') }}" />
    <title inertia>{{ config('app.name', 'Pepito') }}</title>
    <script>
      // Applied before paint so dark mode does not flash on first load.
      try {
        if (JSON.parse(localStorage.getItem('darkMode') || 'false') === true) {
          document.documentElement.classList.add('dark');
        }
      } catch (e) {}
    </script>
    @viteReactRefresh
    @vite(['resources/css/style.css', 'resources/js/app.jsx'])
    @inertiaHead
  </head>
  <body class="bg-gray-50 dark:bg-gray-900">
    @inertia
  </body>
</html>
