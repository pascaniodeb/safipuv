<style>
    .message-item {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    #messages-container::-webkit-scrollbar {
        width: 6px;
    }
    
    #messages-container::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    #messages-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    #messages-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Mejorar selección de texto en mensajes */
    [style*="user-select: text"] {
        cursor: text;
    }

    /* Hover effects MÁS SUAVES */
    .message-item:hover {
        transform: translateY(-0.5px);
        transition: transform 0.1s ease;
    }

    /* Evitar temblor en botones */
    button[wire\:loading\.attr="disabled"] {
        transform: none !important;
    }

    /* Layout responsive con alturas REDUCIDAS */
    @media (max-width: 768px) {
        .h-48 { height: 12rem !important; }  /* 192px - antes era 256px */
    }
    
    @media (min-width: 769px) and (max-width: 1024px) {
        .lg\:h-60 { height: 15rem !important; }  /* 240px - antes era 320px */
    }
    
    @media (min-width: 1025px) {
        .xl\:h-72 { height: 18rem !important; }  /* 288px - antes era 384px */
    }
</style>