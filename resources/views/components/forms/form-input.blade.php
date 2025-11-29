@props(['label', 'name', 'type' => 'text', 'placeholder' => '', 'required' => false, 'value' => ''])

<div>
    <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 mb-2">{{ $label }}</label>
    <input 
        type="{{ $type }}" 
        id="{{ $name }}" 
        name="{{ $name }}" 
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring focus:ring-blue-200 transition']) }}
        placeholder="{{ $placeholder }}"
    >
    @if($slot->isNotEmpty())
        <p class="mt-1 text-xs text-gray-500">{{ $slot }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
