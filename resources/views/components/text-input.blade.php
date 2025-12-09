@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge([
        'class' =>
            'block mt-1 w-full rounded-md shadow-sm transition-colors duration-300
             bg-white dark:bg-[#1B263B]
             border border-gray-300 dark:border-gray-600
             text-gray-900 dark:text-gray-100
             focus:border-[#1E40AF] focus:ring-[#1E40AF]/50
             placeholder-gray-400 dark:placeholder-gray-500'
    ]) }}>
