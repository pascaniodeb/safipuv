<?php

use App\Exports\PastorsExport;
use App\Exports\ChurchesExport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\HojaDeVidaDownloadController;
use App\Http\Controllers\ReporteMensualDownloadController;
use App\Http\Controllers\DistrictOfferingsReportController;
use App\Http\Controllers\TopChurchesReportController;
use App\Http\Controllers\NationalReportController;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect('/admin/login');
});


Route::get('/export-pastors-excel', function () {
    if (!Auth::check()) {
        abort(403, 'No autorizado');
    }

    return Excel::download(new PastorsExport(), 'pastors.xlsx');
})->name('export.pastors.excel');

Route::get('/export-churches-excel', function () {
    if (!Auth::check()) {
        abort(403, 'No autorizado');
    }

    return Excel::download(new ChurchesExport(), 'churches.xlsx');
})->name('export.churches.excel');


Route::get('/descargar-hojadevida/{pastor}', [HojaDeVidaDownloadController::class, 'download'])
    ->name('descargar.hojadevida');

Route::get('/descargar-reportemensual/{pastor}', [ReporteMensualDownloadController::class, 'download'])
    ->name('descargar.reportemensual');

Route::get('/reporte-sector/{sector}/{month}', [\App\Http\Controllers\SectorReportController::class, 'exportPdf'])
    ->name('sector.report.pdf');

Route::get('/reportes/distrito/pdf', [DistrictOfferingsReportController::class, 'generatePdf'])
        ->name('district.report.pdf');
        //->middleware(['auth']); // Asegúrate que esté protegida

Route::get('/reportes/region/pdf', [\App\Http\Controllers\RegionalOfferingsReportController::class, 'generatePdf'])
    ->name('region.report.pdf');

Route::get('/reporte/nacional/pdf', [NationalReportController::class, 'exportPdf'])
    ->name('national.report.pdf')
    ->middleware(['auth']);




// Solo mantener la ruta para servir archivos adjuntos
Route::middleware(['auth'])->group(function () {
    
    // Ruta para servir archivos adjuntos con autorización
    Route::get('/message-attachments/{attachment}', function ($attachmentId) {
        $attachment = \App\Models\MessageAttachment::findOrFail($attachmentId);
        $user = auth()->user();
        
        // Verificar que el usuario tiene acceso a la conversación
        $conversation = $attachment->message->conversation;
        if (!$user->can('view', $conversation)) {
            abort(403);
        }
        
        $path = storage_path('app/message-attachments/' . $attachment->filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        return response()->file($path, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"'
        ]);
    })->name('message.attachment');

    // ELIMINAR la ruta de envío de mensajes - ahora se maneja con Livewire
    // Route::post('/conversations/send-message', ...) ← ELIMINAR ESTA RUTA
});
/*
Route::get('/test-observer', function () {
    $conversation = \App\Models\Conversation::first();
    $user = \App\Models\User::find(283); // Tu ID
    
    $message = \App\Models\Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $user->id,
        'content' => 'Prueba desde ruta',
        'type' => 'text',
    ]);
    
    return "Mensaje creado: {$message->id}";
});
    
    /*
Route::get('/prueba-403', function () {
    abort(403); // ← Lanza el error 403 y Laravel renderiza la vista personalizada
});
Route::get('/prueba-404', function () {
    abort(404); // ← Lanza el error 404 y Laravel renderiza la vista personalizada
});
Route::get('/prueba-500', function () {
    abort(500); // ← Lanza el error 500 y Laravel renderiza la vista personalizada
});

*/
/*
// En routes/web.php o routes/console.php
Route::get('/debug-accounting', function () {
    $user = auth()->loginUsingId(55); // Forzar login como usuario 55
    
    \Log::info("=== DEBUG ACCOUNTING ===");
    \Log::info("Usuario actual: " . auth()->user()->name);
    
    // Test 1: ¿Existe el método en el modelo?
    $model = new \App\Models\AccountingTransaction();
    $methods = get_class_methods($model);
    $hasScope = in_array('scopeAccessibleRecords', $methods);
    \Log::info("¿Modelo tiene scopeAccessibleRecords?: " . ($hasScope ? 'SI' : 'NO'));
    
    // Test 2: Probar el scope directamente
    try {
        $query = \App\Models\AccountingTransaction::accessibleRecords();
        \Log::info("Scope creado exitosamente");
        
        $count = $query->count();
        \Log::info("Registros encontrados: " . $count);
        
        if ($count > 0) {
            $first = $query->first();
            \Log::info("Primer registro: ID=" . $first->id . ", accounting_id=" . $first->accounting_id);
        }
        
    } catch (\Exception $e) {
        \Log::error("Error en scope: " . $e->getMessage());
    }
    
    return "Check logs";
});*/