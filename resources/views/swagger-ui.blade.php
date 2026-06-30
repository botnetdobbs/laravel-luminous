<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}: API Docs</title>
    <link rel="stylesheet"
        href="{{ $uiConfig['cdn']['swagger_ui'] }}/swagger-ui.css"
        @if(!empty($sri['swagger-ui.css'])) integrity="{{ $sri['swagger-ui.css'] }}" crossorigin="anonymous" @endif />
    <style nonce="{{ $nonce }}">
        html { box-sizing: border-box; overflow-y: scroll; }
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="{{ $uiConfig['cdn']['swagger_ui'] }}/swagger-ui-bundle.js"
    @if(!empty($sri['swagger-ui-bundle.js'])) integrity="{{ $sri['swagger-ui-bundle.js'] }}" crossorigin="anonymous" @endif></script>
<script src="{{ $uiConfig['cdn']['swagger_ui'] }}/swagger-ui-standalone-preset.js"
    @if(!empty($sri['swagger-ui-standalone-preset.js'])) integrity="{{ $sri['swagger-ui-standalone-preset.js'] }}" crossorigin="anonymous" @endif></script>
<script nonce="{{ $nonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        SwaggerUIBundle({
            url:                      @json($specUrl),
            dom_id:                   '#swagger-ui',
            presets:                  [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
            layout:                   'StandaloneLayout',
            persistAuthorization:     {{ $uiConfig['persist_authorization'] ? 'true' : 'false' }},
            displayRequestDuration:   {{ $uiConfig['display_request_duration'] ? 'true' : 'false' }},
            defaultModelsExpandDepth: {{ (int) $uiConfig['default_models_expand_depth'] }},
            tryItOutEnabled:          {{ $uiConfig['try_it_out_enabled'] ? 'true' : 'false' }},
            syntaxHighlight:          { theme: @json($uiConfig['syntax_highlight_theme']) },
            validatorUrl:             null,
        });
    });
</script>
</body>
</html>
