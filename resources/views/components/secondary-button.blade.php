<button {{ $attributes->merge([
    'type' => 'button',
    'class' =>
        'inline-flex items-center justify-center px-5 py-2.5
         rounded-md font-semibold text-sm uppercase tracking-widest
         border border-gray-300 dark:border-gray-600
         bg-white dark:bg-[#1B263B]
         text-gray-800 dark:text-gray-100
         hover:bg-gray-100 dark:hover:bg-[#2A3A55]
         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1E40AF] dark:focus:ring-[#A9D6E5]
         shadow-sm transition-all duration-300 ease-in-out disabled:opacity-60 disabled:cursor-not-allowed'
]) }}>
    {{ $slot }}
</button>

