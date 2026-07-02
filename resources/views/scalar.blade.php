<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}: API Docs</title>
</head>
<body>
    <div id="app"></div>
    <script src="{{ $driverCfg['cdn'] }}"
            integrity="{{ $driverCfg['sri']['scalar.js'] }}"
            crossorigin="anonymous"
            nonce="{{ $nonce }}"></script>
    <script nonce="{{ $nonce }}">
        Scalar.createApiReference('#app', {
            url:         @json($specUrl),
            theme:       @json($driverOptions['theme']),
            layout:      @json($driverOptions['layout']),
            darkMode:    {{ $driverOptions['dark_mode'] ? 'true' : 'false' }},
            hideModels:  {{ $driverOptions['hide_models'] ? 'true' : 'false' }},
            showSidebar: {{ $driverOptions['show_sidebar'] ? 'true' : 'false' }},
            @if(!empty($driverOptions['agent_key']))
            agent: { key: @json($driverOptions['agent_key']) },
            @endif
        })
    </script>
</body>
</html>
