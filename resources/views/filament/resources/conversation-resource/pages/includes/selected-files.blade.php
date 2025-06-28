@if(!empty($attachments))
    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-700">
        <div class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
            📎 Archivos seleccionados:
        </div>
        <div class="grid gap-2">
            @foreach($attachments as $index => $attachment)
                <div class="flex items-center gap-3 p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-blue-100 dark:border-gray-600">
                    <div class="flex-1">
                        <div class="font-medium text-sm">{{ $attachment->getClientOriginalName() }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($attachment->getSize() / 1024, 1) }} KB</div>
                    </div>
                    <button type="button" 
                            wire:click="$set('attachments.{{ $index }}', null)"
                            class="text-red-500 hover:text-red-700 font-bold text-lg p-1 rounded hover:bg-red-50">×</button>
                </div>
            @endforeach
        </div>
    </div>
@endif