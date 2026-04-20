@props([
    'label' => '',
    'name',
    'options' => [],
    'required' => false
])

<div>
    <label class="block text-sm font-medium text-gray-900">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <div class="mt-1">
        <select 
            name="{{ $name }}"
            @if($required) required @endif

            class="block w-full rounded-md bg-white px-3 py-2 text-sm
            border {{ $errors->has($name) ? 'border-red-500' : 'border-gray-300' }}
            focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 outline-none"
        >
            <option value="">-- Pilih --</option>

            @foreach($options as $option)
                <option value="{{ $option }}" {{ old($name) == $option ? 'selected' : '' }}>
                    {{ $option }}
                </option>
            @endforeach
        </select>
    </div>

    @error($name)
        <p class="text-xs text-red-500 mt-1">
            {{ $message }}
        </p>
    @enderror
</div>