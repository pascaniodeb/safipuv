<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Error')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Fuente y estilos --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
            color: #1f2937;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

         .code {
            font-size: 9rem;
            font-weight: 800;
            color: #4f46e5;
            position: relative;
            z-index: 2;
            top: 25%;
        }

         .message {
            font-size: 2.25rem;
            margin-top: 2rem;
            position: relative;
            z-index: 2;
            top: 45%;
        }

         .button {
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            background-color: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
            position: relative;
            z-index: 2;
            top: 45%;
        }

        .button:hover {
            background-color: #4338ca;
        }

        .svg-background {
            position: absolute;
            width: 620px;
            height: auto;
            opacity: 50;
            z-index: 1;
            top: 42%;
            transform: translateY(-50%);
        }

        .watermark {
            position: absolute;
            font-size: 2rem;
            font-weight: 600;
            color: #d1d5db;
            opacity: 0.9;
            z-index: 2;
            top: 85%;
        }

        footer {
            margin-top: auto;
            padding-bottom: 1rem;
            width: 100%;
            text-align: center;
            font-size: 0.75rem;
            color: #6b7280;
            z-index: 2;
        }

        /* Responsive para tablets */
        @media (max-width: 768px) {
            .code {
                font-size: 9rem;
                font-weight: 800;
                color: #4f46e5;
                position: relative;
                z-index: 2;
                top: 24%;
            }

            .message {
                font-size: 1.25rem;
                margin-top: 2rem;
                position: relative;
                z-index: 2;
                top: 38%;
            }

            .svg-background {
                position: absolute;
                width: 500px;
                height: auto;
                opacity: 100%;
                z-index: 1;
                top: 40%;
                left: 50%;
                transform: translate(-50%, -50%);
            }

            .button {
                margin-top: 2rem;
                padding: 0.75rem 1.5rem;
                background-color: #4f46e5;
                color: white;
                text-decoration: none;
                border-radius: 0.5rem;
                font-weight: 600;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                transition: background-color 0.3s ease;
                position: relative;
                z-index: 2;
                top: 38%;
            }

            .watermark {
                position: absolute;
                font-size: 1.25rem;
                font-weight: 600;
                color: #d1d5db;
                opacity: 0.9;
                z-index: 2;
                top: 75%;
            }
        }

        /* Responsive para móviles */
        @media (max-width: 480px) {
            .code {
                font-size: 9rem;
                font-weight: 800;
                color: #4f46e5;
                position: relative;
                z-index: 2;
                top: 24%;
            }

            .message {
                font-size: 1.25rem;
                margin-top: 2rem;
                position: relative;
                z-index: 2;
                top: 38%;
            }

            .svg-background {
                position: absolute;
                width: 500px;
                height: auto;
                opacity: 100%;
                z-index: 1;
                top: 40%;
                left: 50%;
                transform: translate(-50%, -50%);
            }

            .button {
                margin-top: 2rem;
                padding: 0.75rem 1.5rem;
                background-color: #4f46e5;
                color: white;
                text-decoration: none;
                border-radius: 0.5rem;
                font-weight: 600;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                transition: background-color 0.3s ease;
                position: relative;
                z-index: 2;
                top: 38%;
            }

            .watermark {
                position: absolute;
                font-size: 1.25rem;
                font-weight: 600;
                color: #d1d5db;
                opacity: 0.9;
                z-index: 2;
                top: 75%;
            }
    </style>
</head>
<body>

    {{-- Imagen SVG de fondo detrás del código --}}
    <img src="{{ asset('images/oops-error.svg') }}" alt="Error gráfico" class="svg-background">

    {{-- Contenido principal --}}
    <div class="code">@yield('code')</div>
    <div class="message">@yield('message')</div>
    <a href="{{ filament()->getUrl() }}" class="button">Volver al Panel</a>

    {{-- Marca de agua horizontal --}}
    <div class="watermark">
        Sistema Administrativo y Financiero de la Iglesia Pentecostal Unida de Venezuela
    </div>

    {{-- Footer siempre visible abajo --}}
    <footer class="my-4 text-sm text-center text-gray-500 dark:text-gray-400">
        <p>
            Copyright &copy; 2020-{{ date('Y') }}
            <a href="https://safipuv.com" class="hover:underline text-gray-500 dark:text-blue-400 text-center" target="_blank"><strong>SAFIPUV</strong></a>
            | Todos los Derechos Reservados
        </p>
        <p class="mt-2 text-gray-400 text-center">
            Desarrollado por:
            <a href="https://www.facebook.com/pabloascanio" target="_blank" class="text-gray-400 hover:underline transition">
                Pablo José Ascanio
            </a>
        </p>
    </footer>
</body>
</html>
