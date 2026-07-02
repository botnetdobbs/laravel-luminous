<?php

return [
    'swagger' => [
        'view' => 'swagger-ui',
        'cdn' => 'https://unpkg.com/swagger-ui-dist@5.32.8',
        'sri' => [
            'swagger-ui.css' => 'sha256-yiOPfXws9EgMHnepw7nakVqyFulv/TVOaQdlYMZQxt4=',
            'swagger-ui-bundle.js' => 'sha256-l/A82ui58J+PM7YE7UeWvTGMN46QdjtGi7SqCGC9kKc=',
            'swagger-ui-standalone-preset.js' => 'sha256-O178AUxnFi016ghwqN2wCWwLxQkrcssn/0iiiez7iYM=',
        ],
    ],
    'redoc' => [
        'view' => 'redoc',
        'cdn' => 'https://cdn.jsdelivr.net/npm/redoc@2.5.3/bundles/redoc.standalone.js',
        'sri' => [
            'redoc.standalone.js' => 'sha256-EyD0QhUcV8RH07cMf/xsT4bQhGQCD+NMjMXTFk6ZRPA=',
        ],
        'themes' => [
            'default' => ['theme' => [], 'css' => ''],

            'dark' => [
                'theme' => [
                    'spacing' => [
                        'unit' => 5,
                        'sectionHorizontal' => 40,
                        'sectionVertical' => 40,
                    ],
                    'breakpoints' => [
                        'small' => '50rem',
                        'medium' => '85rem',
                        'large' => '105rem',
                    ],
                    'colors' => [
                        'tonalOffset' => 0.2,
                        'primary' => ['main' => '#58a6ff', 'light' => '#79b8ff', 'dark' => '#388bfd', 'contrastText' => '#0f1419'],
                        'success' => ['main' => '#3fb950', 'light' => '#56d364', 'dark' => '#2ea043', 'contrastText' => '#0f1419'],
                        'warning' => ['main' => '#d29922', 'light' => '#e3b341', 'dark' => '#bb8009', 'contrastText' => '#0f1419'],
                        'error' => ['main' => '#f85149', 'light' => '#ff7b72', 'dark' => '#da3633', 'contrastText' => '#ffffff'],
                        'text' => ['primary' => '#e6edf3', 'secondary' => '#9da7b3'],
                        'border' => ['dark' => '#30363d', 'light' => '#21262d'],
                        'responses' => [
                            'success' => ['color' => '#3fb950', 'backgroundColor' => 'rgba(63,185,80,0.1)', 'tabTextColor' => '#3fb950'],
                            'error' => ['color' => '#f85149', 'backgroundColor' => 'rgba(248,81,73,0.1)', 'tabTextColor' => '#f85149'],
                            'redirect' => ['color' => '#d29922', 'backgroundColor' => 'rgba(210,153,34,0.1)', 'tabTextColor' => '#d29922'],
                            'info' => ['color' => '#58a6ff', 'backgroundColor' => 'rgba(88,166,255,0.1)', 'tabTextColor' => '#58a6ff'],
                        ],
                        'http' => [
                            'get' => '#3fb950',
                            'post' => '#58a6ff',
                            'put' => '#d29922',
                            'patch' => '#bc8cff',
                            'delete' => '#f85149',
                            'options' => '#39c5cf',
                            'head' => '#9da7b3',
                            'basic' => '#9da7b3',
                            'link' => '#39c5cf',
                        ],
                    ],
                    'typography' => [
                        'fontSize' => '15px',
                        'lineHeight' => '1.6em',
                        'fontWeightRegular' => '400',
                        'fontWeightBold' => '600',
                        'fontWeightLight' => '300',
                        'fontFamily' => '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                        'smoothing' => 'antialiased',
                        'optimizeSpeed' => true,
                        'headings' => [
                            'fontFamily' => '"Inter", -apple-system, sans-serif',
                            'fontWeight' => '600',
                            'lineHeight' => '1.4em',
                        ],
                        'code' => [
                            'fontSize' => '13px',
                            'fontFamily' => '"JetBrains Mono", "Fira Code", Menlo, monospace',
                            'fontWeight' => '400',
                            'color' => '#ff7b72',
                            'backgroundColor' => 'rgba(110,118,129,0.15)',
                            'wrap' => true,
                        ],
                        'links' => [
                            'color' => '#58a6ff',
                            'visited' => '#58a6ff',
                            'hover' => '#79b8ff',
                            'textDecoration' => 'none',
                            'hoverTextDecoration' => 'underline',
                        ],
                    ],
                    'sidebar' => [
                        'width' => '280px',
                        'backgroundColor' => '#0b0e13',
                        'textColor' => '#9da7b3',
                        'activeTextColor' => '#58a6ff',
                        'groupItems' => [
                            'activeBackgroundColor' => '#161b22',
                            'activeTextColor' => '#e6edf3',
                            'textTransform' => 'uppercase',
                        ],
                        'level1Items' => [
                            'activeBackgroundColor' => '#161b22',
                            'activeTextColor' => '#58a6ff',
                            'textTransform' => 'none',
                        ],
                        'arrow' => ['size' => '1.3em', 'color' => '#9da7b3'],
                    ],
                    'logo' => ['gutter' => '16px'],
                    'rightPanel' => [
                        'backgroundColor' => '#161b22',
                        'width' => '40%',
                        'textColor' => '#e6edf3',
                        'servers' => [
                            'overlay' => ['backgroundColor' => '#21262d', 'textColor' => '#e6edf3'],
                            'url' => ['backgroundColor' => '#0b0e13'],
                        ],
                    ],
                    'codeBlock' => ['backgroundColor' => '#0b0e13'],
                    'fab' => ['backgroundColor' => '#21262d', 'color' => '#58a6ff'],
                    'schema' => [
                        'linesColor' => '#30363d',
                        'typeNameColor' => '#9da7b3',
                        'typeTitleColor' => '#79b8ff',
                        'requireLabelColor' => '#f85149',
                        'nestedBackground' => '#11151c',
                    ],
                ],
                'css' => implode(' ', [
                    'body, .redoc-wrap, .api-content { background: #0f1419; }',
                    '.api-content h1, .api-content h2, .api-content h3, .api-content h4, .api-content h5 { color: #e6edf3 !important; }',
                    '.api-content table th, .api-content table td { border-color: #30363d; }',
                    '[role="search"] input { background: #0b0e13; color: #e6edf3; border-color: #30363d; }',
                ]),
            ],

            'stripe' => [
                'theme' => [
                    'spacing' => [
                        'unit' => 5,
                        'sectionHorizontal' => 40,
                        'sectionVertical' => 24,
                    ],
                    'breakpoints' => [
                        'small' => '50rem',
                        'medium' => '85rem',
                        'large' => '105rem',
                    ],
                    'colors' => [
                        'tonalOffset' => 0.2,
                        'primary' => ['main' => '#635bff', 'light' => '#7a73ff', 'dark' => '#5851ec', 'contrastText' => '#ffffff'],
                        'success' => ['main' => '#228403', 'light' => '#3fa71e', 'dark' => '#1a6602', 'contrastText' => '#ffffff'],
                        'warning' => ['main' => '#b13600', 'light' => '#d97917', 'dark' => '#8f2b00', 'contrastText' => '#ffffff'],
                        'error' => ['main' => '#cd3d64', 'light' => '#e5628a', 'dark' => '#b13154', 'contrastText' => '#ffffff'],
                        'text' => ['primary' => '#2a2f45', 'secondary' => '#697386'],
                        'border' => ['dark' => '#e3e8ee', 'light' => '#f0f4f8'],
                        'responses' => [
                            'success' => ['color' => '#228403', 'backgroundColor' => 'rgba(34,132,3,0.07)', 'tabTextColor' => '#228403'],
                            'error' => ['color' => '#cd3d64', 'backgroundColor' => 'rgba(205,61,100,0.07)', 'tabTextColor' => '#cd3d64'],
                            'redirect' => ['color' => '#b13600', 'backgroundColor' => 'rgba(177,54,0,0.07)', 'tabTextColor' => '#b13600'],
                            'info' => ['color' => '#635bff', 'backgroundColor' => 'rgba(99,91,255,0.07)', 'tabTextColor' => '#635bff'],
                        ],
                        'http' => [
                            'get' => '#228403',
                            'post' => '#635bff',
                            'put' => '#b13600',
                            'patch' => '#0d7ea2',
                            'delete' => '#cd3d64',
                            'options' => '#0d7ea2',
                            'head' => '#697386',
                            'basic' => '#697386',
                            'link' => '#0d7ea2',
                        ],
                    ],
                    'typography' => [
                        'fontSize' => '15px',
                        'lineHeight' => '1.6em',
                        'fontWeightRegular' => '400',
                        'fontWeightBold' => '600',
                        'fontWeightLight' => '300',
                        'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                        'smoothing' => 'antialiased',
                        'optimizeSpeed' => true,
                        'headings' => [
                            'fontFamily' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                            'fontWeight' => '600',
                            'lineHeight' => '1.35em',
                        ],
                        'code' => [
                            'fontSize' => '13px',
                            'fontFamily' => 'ui-monospace, "SF Mono", "Cascadia Code", Menlo, Consolas, monospace',
                            'fontWeight' => '500',
                            'color' => '#635bff',
                            'backgroundColor' => '#f6f8fa',
                            'wrap' => true,
                        ],
                        'links' => [
                            'color' => '#635bff',
                            'visited' => '#635bff',
                            'hover' => '#0a2540',
                            'textDecoration' => 'none',
                            'hoverTextDecoration' => 'none',
                        ],
                    ],
                    'sidebar' => [
                        'width' => '280px',
                        'backgroundColor' => '#f6f9fc',
                        'textColor' => '#425466',
                        'activeTextColor' => '#635bff',
                        'groupItems' => [
                            'activeBackgroundColor' => '#eef2f7',
                            'activeTextColor' => '#0a2540',
                            'textTransform' => 'uppercase',
                        ],
                        'level1Items' => [
                            'activeBackgroundColor' => '#eef2f7',
                            'activeTextColor' => '#635bff',
                            'textTransform' => 'none',
                        ],
                        'arrow' => ['size' => '1.2em', 'color' => '#8792a2'],
                    ],
                    'logo' => ['gutter' => '16px'],
                    'rightPanel' => [
                        'backgroundColor' => '#0a2540',
                        'width' => '40%',
                        'textColor' => '#f6f9fc',
                        'servers' => [
                            'overlay' => ['backgroundColor' => '#12314f', 'textColor' => '#f6f9fc'],
                            'url' => ['backgroundColor' => '#061b30'],
                        ],
                    ],
                    'codeBlock' => ['backgroundColor' => '#061b30'],
                    'fab' => ['backgroundColor' => '#635bff', 'color' => '#ffffff'],
                    'schema' => [
                        'linesColor' => '#e3e8ee',
                        'typeNameColor' => '#697386',
                        'typeTitleColor' => '#635bff',
                        'requireLabelColor' => '#cd3d64',
                        'nestedBackground' => '#f6f9fc',
                    ],
                ],
                'opts' => [
                    'nativeScrollbars'      => true,
                    'expandResponses'       => '200,201',
                    'hideDownloadButton'    => true,
                    'requiredPropsFirst'    => true,
                    'jsonSampleExpandLevel' => 3,
                ],
                'css' => implode(' ', [
                    // sidebar
                    '.menu-content { border-right: 1px solid #e3e8ee; }',
                    '.menu-content li a.active, .menu-content label.active { border-radius: 6px; margin: 0 8px; }',
                    // method badges
                    '.operation-type { border-radius: 4px; font-weight: 600; letter-spacing: 0.02em; }',
                    // code panels
                    '.redoc-json, [data-section-id] pre { font-size: 13px; line-height: 1.55; }',
                    '.react-tabs__tab--selected { border-radius: 4px; background: #12314f; color: #7ee2a8; border-color: #2d4a68; }',
                    // section headers: subtle, no full-width rule
                    '.api-content h1, .api-content h2 { letter-spacing: -0.01em; color: #0a2540; }',
                    '.api-content h5 { border-bottom: none; color: #8792a2; font-size: 12px; letter-spacing: 0.06em; }',
                    // schema table rows: light separator, not editable-field underline
                    'table.security-details td, [class*="PropertiesTable"] td, .api-content table td { border-bottom: 1px solid #f0f4f8; }',
                    // constraint pills: quiet gray badge, not red error; narrowed to warning type to avoid boxing schema type labels
                    'span[class*="ConstraintItem"], .api-content td span[type="warning"] { background: #f6f9fc; border: 1px solid #e3e8ee; border-radius: 4px; color: #697386; font-size: 12px; padding: 1px 6px; }',
                    // required label stays red, no override
                    '[class*="RequiredLabel"] { font-size: 10px; font-weight: 600; letter-spacing: 0.04em; }',
                    // right panel scrollbar: dark thumb on navy, no light chrome
                    '[class*="RightPanel"] ::-webkit-scrollbar, .redoc-wrap ::-webkit-scrollbar { width: 10px; }',
                    '[class*="RightPanel"] ::-webkit-scrollbar-thumb { background: #2d4a68; border-radius: 5px; }',
                    '[class*="RightPanel"] ::-webkit-scrollbar-track { background: transparent; }',
                    // response rows: card treatment with breathing room (rows are <button> elements)
                    '[data-section-id] button[class*="ResponseTitle"], .api-content div[class*="ResponseView"] > button { border-radius: 6px; margin-bottom: 6px; }',
                    // server dropdown: role-based selectors, stable across Redoc versions
                    // path is a bare text node in the container div, so color + box live on the same rule
                    'div[role="button"] > div { color: #a5b4fc !important; border: 1px solid #2d4a68; border-radius: 6px; background: #061b30; }',
                    // base URL span (http://localhost): muted gray, keeps visual emphasis on path
                    'div[role="button"] > div > span { color: #8792a2 !important; }',
                    // "Local" label above the URL
                    'div[class*="ServersOverlay"] p, .servers p { color: #f6f9fc; }',
                    // server dropdown overlay: shadow so it lifts off the panel instead of merging into it
                    'div[class*="ServersOverlay"] { box-shadow: 0 6px 24px rgba(0,0,0,0.5); border-radius: 0 0 8px 8px; }',
                ]),
            ],
        ],
    ],
    'scalar' => [
        'view' => 'scalar',
        'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.62.1/dist/browser/standalone.js',
        'sri' => [
            'scalar.js' => 'sha256-SCzsbmT+Cneg5FPiNdimjq3rdQQIXYF2lFyC9h1ePK0=',
        ],
    ],
];
