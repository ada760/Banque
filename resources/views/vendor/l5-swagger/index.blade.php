<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{config('l5-swagger.documentations.'.$documentation.'.api.title')}}</title>
    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-32x32.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-16x16.png') }}" sizes="16x16"/>
    <style>
    html
    {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
    }
    *,
    *:before,
    *:after
    {
        box-sizing: inherit;
    }

    body {
      margin:0;
      background: #fafafa;
    }
    </style>
</head>

<body>
<div id="swagger-ui"></div>

<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-standalone-preset.js') }}"></script>
<script>
    window.onload = function() {
        // Build a system
        const ui = SwaggerUIBundle({
            dom_id: '#swagger-ui',
            url: "{!! $urlToDocs !!}",
            operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
            configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
            validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
            oauth2RedirectUrl: "{{ route('l5-swagger.'.$documentation.'.oauth2_callback', [], $useAbsolutePath) }}",

            requestInterceptor: function(request) {
                request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                return request;
            },

            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],

            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],

            layout: "StandaloneLayout",
            docExpansion : "{!! config('l5-swagger.defaults.ui.display.doc_expansion', 'none') !!}",
            deepLinking: true,
            filter: {!! config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' !!},
            persistAuthorization: "{!! config('l5-swagger.defaults.ui.authorization.persist_authorization') ? 'true' : 'false' !!}",

            // Activer l'authentification Bearer
            onComplete: function() {
                console.log('=== SWAGGER UI DEBUG ===');
                console.log('Swagger UI chargé, recherche du bouton Authorize...');
                console.log('Spec security:', ui.spec().security);

                // Auto-expand auth section if security is defined
                if (ui.spec().security && ui.spec().security.length > 0) {
                    console.log('Sécurité définie, recherche du bouton...');

                    // Ouvrir automatiquement la section d'autorisation
                    setTimeout(function() {
                        console.log('=== RECHERCHE BOUTON AUTHORIZE ===');

                        // Essayer différents sélecteurs pour le bouton Authorize
                        var selectors = [
                            '.btn.authorize',
                            '.authorize',
                            '[data-cy="authorize-btn"]',
                            'button.authorize',
                            '.swagger-ui .authorize',
                            '.scheme-container .authorize',
                            '.auth-wrapper .authorize',
                            '.btn.btn-primary.authorize',
                            '.authorize-btn',
                            '.auth-btn',
                            '.authorization__btn',
                            '.btn.modal-btn.auth',
                            '.authorize-button',
                            '.swagger-ui .topbar .download-url-wrapper .btn.authorize',
                            '.swagger-ui .auth-wrapper .authorize',
                            '.swagger-ui .scheme-container .authorize',
                            '.swagger-ui .information-container .authorize',
                            '.swagger-ui .topbar .authorize',
                            '.swagger-ui .btn.authorize',
                            '.swagger-ui button.authorize',
                            '.swagger-ui .authorize button',
                            '.swagger-ui .auth-btn',
                            '.swagger-ui .authorization-btn'
                        ];

                        var authBtn = null;
                        for (var i = 0; i < selectors.length; i++) {
                            authBtn = document.querySelector(selectors[i]);
                            if (authBtn) {
                                console.log('Bouton trouvé avec sélecteur:', selectors[i]);
                                break;
                            }
                        }

                        if (authBtn) {
                            console.log('Bouton Authorize trouvé:', authBtn.className, authBtn.textContent);
                            console.log('Clic automatique sur le bouton...');
                            authBtn.click();
                        } else {
                            console.log('Bouton Authorize non trouvé, recherche manuelle...');
                            // Chercher tous les boutons et afficher leurs classes
                            var buttons = document.querySelectorAll('button');
                            console.log('Nombre total de boutons:', buttons.length);
                            buttons.forEach(function(btn, index) {
                                console.log('Bouton ' + index + ': classes="' + btn.className + '" text="' + btn.textContent + '"');
                                if (btn.textContent.toLowerCase().includes('authorize') ||
                                    btn.textContent.toLowerCase().includes('auth') ||
                                    btn.textContent.toLowerCase().includes('autoriser') ||
                                    btn.textContent.toLowerCase().includes('autorisation')) {
                                    console.log('*** BOUTON AUTH TROUVÉ:', index, btn.className, btn.textContent);
                                    console.log('Clic automatique...');
                                    btn.click();
                                }
                            });
                        }
                    }, 3000); // Attendre 3 secondes
                } else {
                    console.log('Aucune sécurité définie dans la spec');
                }
            }

        })

        window.ui = ui

        @if(in_array('oauth2', array_column(config('l5-swagger.defaults.securityDefinitions.securitySchemes'), 'type')))
        ui.initOAuth({
            usePkceWithAuthorizationCodeGrant: "{!! (bool)config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant') !!}"
        })
        @endif
    }
</script>
</body>
</html>
