<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @if($driverOptions['dark_mode'] ?? false)
    <meta name="color-scheme" content="dark">
    @endif
    <title>{{ $title }}: API Docs</title>
    <link rel="stylesheet"
        href="{{ $driverCfg['cdn'] }}/swagger-ui.css"
        @if(!empty($driverCfg['sri']['swagger-ui.css'])) integrity="{{ $driverCfg['sri']['swagger-ui.css'] }}" crossorigin="anonymous" @endif />
    <style nonce="{{ $nonce }}">
        html { box-sizing: border-box; overflow-y: scroll; }
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="{{ $driverCfg['cdn'] }}/swagger-ui-bundle.js"
    @if(!empty($driverCfg['sri']['swagger-ui-bundle.js'])) integrity="{{ $driverCfg['sri']['swagger-ui-bundle.js'] }}" crossorigin="anonymous" @endif></script>
<script src="{{ $driverCfg['cdn'] }}/swagger-ui-standalone-preset.js"
    @if(!empty($driverCfg['sri']['swagger-ui-standalone-preset.js'])) integrity="{{ $driverCfg['sri']['swagger-ui-standalone-preset.js'] }}" crossorigin="anonymous" @endif></script>
<script nonce="{{ $nonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        @if($driverOptions['dark_mode'] ?? false)
        document.documentElement.classList.add('dark-mode');
        @endif
        SwaggerUIBundle({
            url:                      @json($specUrl),
            dom_id:                   '#swagger-ui',
            presets:                  [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
            layout:                   'StandaloneLayout',
            persistAuthorization:     {{ $driverOptions['persist_authorization'] ? 'true' : 'false' }},
            displayRequestDuration:   {{ $driverOptions['display_request_duration'] ? 'true' : 'false' }},
            defaultModelsExpandDepth: {{ (int) $driverOptions['default_models_expand_depth'] }},
            tryItOutEnabled:          {{ $driverOptions['try_it_out_enabled'] ? 'true' : 'false' }},
            syntaxHighlight:          { activated: true, theme: @json($driverOptions['syntax_highlight_theme']) },
            validatorUrl:             null,
        });
    });
</script>
</body>
</html>
