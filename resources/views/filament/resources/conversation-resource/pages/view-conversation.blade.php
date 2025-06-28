<div class="h-screen flex flex-col p-4 space-y-3 overflow-hidden">
    
    @include('filament.resources.conversation-resource.pages.includes.conversation-header', [
        'record' => $this->record,
        'participants' => $participants,
        'messages' => $messages
    ])

    @include('filament.resources.conversation-resource.pages.includes.conversation-messages', [
        'messages' => $messages
    ])

    @include('filament.resources.conversation-resource.pages.includes.conversation-scripts')

    @include('filament.resources.conversation-resource.pages.includes.conversation-styles')

</div>