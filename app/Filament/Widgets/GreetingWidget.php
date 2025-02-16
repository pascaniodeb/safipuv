<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class GreetingWidget extends Widget
{
    protected static string $view = 'filament.widgets.greeting-widget';

    public $verse;

    public function mount()
    {
        $verses = [
            'Juan 3:16 - "Pues Dios amó tanto al mundo que dio a su único Hijo, para que todo el que crea en él no se pierda, sino que tenga vida eterna."',
            'Salmos 23:1 - "El Señor es mi pastor; tengo todo lo que necesito."',
            'Filipenses 4:13 - "Pues todo lo puedo hacer por medio de Cristo, quien me da las fuerzas."',
            'Proverbios 3:5 - "Confía en el Señor con todo tu corazón; no dependas de tu propio entendimiento."',
            'Isaías 41:10 - "No tengas miedo, porque yo estoy contigo; no te desalientes, porque yo soy tu Dios. Te daré fuerzas y te ayudaré; te sostendré con mi mano derecha victoriosa."',
        ];

        $this->verse = $verses[array_rand($verses)];
    }
}

