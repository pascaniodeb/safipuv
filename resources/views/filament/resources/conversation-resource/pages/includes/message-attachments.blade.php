@if($message->attachments->count() > 0)
    <div style="margin-top: 8px;">
        @foreach($message->attachments as $attachment)
            <div style="background: {{ $isOwn ? 'rgba(255,255,255,0.1)' : '#f3f4f6' }}; padding: 8px; border-radius: 8px; margin-bottom: 4px;">
                <a href="{{ $attachment->url }}" target="_blank" style="color: {{ $isOwn ? '#dbeafe' : '#374151' }}; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    @if(str_starts_with($attachment->mime_type, 'audio/'))
                        <span style="font-size: 16px;">🎵</span>
                    @elseif(str_starts_with($attachment->mime_type, 'image/'))
                        <span style="font-size: 16px;">🖼️</span>
                    @else
                        <span style="font-size: 16px;">📎</span>
                    @endif
                    <div>
                        <div style="font-size: 12px; font-weight: 600;">{{ $attachment->original_name }}</div>
                        <div style="font-size: 11px; {{ $isOwn ? 'opacity: 0.8;' : 'color: #6b7280;' }}">{{ $attachment->formatted_size }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif