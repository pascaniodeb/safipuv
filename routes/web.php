<?php

use App\Exports\PastorsExport;
use App\Exports\ChurchesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;
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