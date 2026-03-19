@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' =>
            'block mt-1 w-full rounded-md shadow-sm transition-colors duration-200
             bg-white dark:bg-[#1B263B]
             border border-gray-300 dark:border-gray-600
             text-gray-900 dark:text-gray-100
             focus:border-[#1E40AF] focus:ring-2 focus:ring-[#1E40AF]/30
             dark:focus:border-[#A9D6E5] dark:focus:ring-[#A9D6E5]/20
             placeholder-gray-400 dark:placeholder-gray-500
             disabled:opacity-60 disabled:cursor-not-allowed'
    ]) }}>
