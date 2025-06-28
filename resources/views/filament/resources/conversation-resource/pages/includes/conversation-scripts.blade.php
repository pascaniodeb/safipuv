<script>
    let isUserTyping = false;

    document.addEventListener('livewire:initialized', () => {
        Livewire.on('messagesSent', () => {
            setTimeout(() => {
                const container = document.getElementById('messages-container');
                if (container) {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            }, 100);
        });
    });

    // Auto-scroll inicial y después de cargar - SOLO SI NO ESTÁ ESCRIBIENDO
    function scrollToBottom() {
        if (!isUserTyping) {
            const container = document.getElementById('messages-container');
            if (container) {
                setTimeout(() => {
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                }, 200);
            }
        }
    }

    // Detectar cuando el usuario está escribiendo
    document.addEventListener('DOMContentLoaded', () => {
        const textarea = document.querySelector('textarea[wire\\:model\\.live\\.debounce\\.300ms="messageContent"]');
        if (textarea) {
            textarea.addEventListener('focus', () => isUserTyping = true);
            textarea.addEventListener('blur', () => setTimeout(() => isUserTyping = false, 1000));
            textarea.addEventListener('input', () => isUserTyping = true);
        }
        scrollToBottom();
    });

    document.addEventListener('livewire:update', scrollToBottom);
</script>