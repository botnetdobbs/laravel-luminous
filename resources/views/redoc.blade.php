<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}: API Docs</title>
    <style nonce="{{ $nonce }}">body { margin: 0; padding: 0; }</style>
    @php
        $preset = $driverCfg['themes'][$driverOptions['theme'] ?? 'default'] ?? ['theme' => [], 'css' => ''];
        $resolvedTheme = $preset['theme'] ?? [];
        $themeCSS = $preset['css'] ?? '';
        $themeFonts = $preset['fonts'] ?? '';
        $opts = array_merge([
            'hideDownloadButton'  => $driverOptions['hide_download_button'] ?? false,
            'expandResponses'     => $driverOptions['expand_responses'] ?? '',
            'nativeScrollbars'    => $driverOptions['native_scrollbars'] ?? false,
            'pathInMiddlePanel'   => $driverOptions['path_in_middle_panel'] ?? false,
        ], $preset['opts'] ?? []);
        if (!empty($resolvedTheme)) {
            $opts['theme'] = $resolvedTheme;
        }
    @endphp
    @if($themeFonts)
    <link rel="stylesheet" href="{{ $themeFonts }}">
    @endif
    @if($themeCSS)
    <style nonce="{{ $nonce }}">{!! $themeCSS !!}</style>
    @endif
</head>
<body>
    <div id="redoc-container"></div>
    <script src="{{ $driverCfg['cdn'] }}"
            integrity="{{ $driverCfg['sri']['redoc.standalone.js'] }}"
            crossorigin="anonymous"
            nonce="{{ $nonce }}"></script>
    <script nonce="{{ $nonce }}">
        var opts = @json($opts);
        @if(!empty($driverOptions['hide_schema_pattern']))
        opts.hideSchemaPattern = new RegExp(@json($driverOptions['hide_schema_pattern']));
        @endif
        Redoc.init(@json($specUrl), opts, document.getElementById('redoc-container'));
    </script>
</body>
</html>
