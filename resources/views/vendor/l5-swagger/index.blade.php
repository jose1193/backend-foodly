<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Foodly APIs</title>
    <meta name="theme-color" content="#79005d" />
    <link rel="stylesheet" type="text/css" href="{{ env('APP_URL') }}/swagger/swagger-ui.min.css">
    <link rel="icon" type="image/png" href="{{ env('APP_URL') }}/favicon/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="{{ env('APP_URL') }}/favicon/favicon-16x16.png" sizes="16x16" />


    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }


        .swagger-ui .topbar-wrapper {
            background-color: #79005d;
        }

        .swagger-ui .topbar-wrapper img {
            background-color: #79005d;
            content: url('{{ env('APP_URL') }}/img/logo-white.png');
        }



        .swagger-ui .topbar {
            background-color: #79005d;
        }
    </style>
</head>

<body>

    <div id="swagger-ui"></div>


    <script>
        window.onload = function() {
            // Espera a que Swagger UI se cargue completamente
            setTimeout(function() {
                var serversContainer = document.querySelector(
                    '.servers'); // Asume que el contenedor tiene la clase 'servers'
                if (serversContainer) {
                    var copyButton = document.createElement('button');
                    copyButton.id = 'copyButton';
                    copyButton.innerText = 'Copiar URL del Servidor';
                    copyButton.onclick = function() {
                        copyToClipboard();
                    };
                    copyButton.style.marginLeft = '10px'; // Añade margen a la izquierda para separar del select

                    // Añadir el botón al DOM, al lado del selector
                    serversContainer.appendChild(copyButton);
                }
            }, 1000); // Ajusta este tiempo según la carga de tu Swagger UI

            // Función para copiar la URL
            function copyToClipboard() {
                var url = document.querySelector('.servers select').value;
                navigator.clipboard.writeText(url).then(function() {
                    alert('URL copiada: ' + url);
                }, function(err) {
                    console.error('Error al copiar la URL: ', err);
                });
            }
        };
    </script>




    <script src="{{ env('APP_URL') }}/swagger/swagger-ui-bundle.js"></script>
    <script src="{{ env('APP_URL') }}/swagger/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Build a system
            const url = "{{ env('APP_URL') }}/docs/api-docs.json";
            const ui = SwaggerUIBundle({
                dom_id: '#swagger-ui',
                url: url,
                operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
                configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
                validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
                oauth2RedirectUrl: "{{ route('l5-swagger.' . $documentation . '.oauth2_callback', [], $useAbsolutePath) }}",

                requestInterceptor: function(request) {
                    request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                    request.headers['Cache-Control'] = 'no-cache'; // Esta línea deshabilita la caché
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
                docExpansion: "{!! config('l5-swagger.defaults.ui.display.doc_expansion', 'none') !!}",
                deepLinking: true,
                filter: {!! config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' !!},
                persistAuthorization: "{!! config('l5-swagger.defaults.ui.authorization.persist_authorization') ? 'true' : 'false' !!}",

            })


            window.ui = ui

            @if (in_array('oauth2', array_column(config('l5-swagger.defaults.securityDefinitions.securitySchemes'), 'type')))
                ui.initOAuth({
                    usePkceWithAuthorizationCodeGrant: "{!! (bool) config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant') !!}"
                })
            @endif
        }
    </script>
</body>

</html>
