@props(['href', 'text' => 'Kembali'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'back-button']) }}>
    <div class="back-button-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" height="20px" width="20px">
            <path d="M224 480h640a32 32 0 1 1 0 64H224a32 32 0 0 1 0-64z" fill="currentColor"></path>
            <path d="m237.248 512 265.408 265.344a32 32 0 0 1-45.312 45.312l-288-288a32 32 0 0 1 0-45.312l288-288a32 32 0 1 1 45.312 45.312L237.248 512z" fill="currentColor"></path>
        </svg>
    </div>
    <span class="back-button-text">{{ $text }}</span>
</a>

<style>
.back-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 0.75rem;
    height: 3rem;
    position: relative;
    color: #1e293b;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.back-button-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    border-radius: 0.5rem;
    height: 2.5rem;
    width: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.back-button-icon svg {
    transition: transform 0.3s ease;
}

.back-button-text {
    display: none;
    margin: 0;
    padding: 0 1rem;
    white-space: nowrap;
}

.back-button:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.back-button:hover .back-button-icon svg {
    transform: translateX(-2px);
}

.back-button:active {
    transform: scale(0.95);
}

@media (min-width: 640px) {
    .back-button {
        width: auto;
        padding-right: 1rem;
    }
    
    .back-button-text {
        display: block;
    }
}
</style>
