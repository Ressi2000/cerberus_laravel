<button {{ $attributes->merge([
    'type' => 'submit',
    'class' =>
        'inline-flex items-center justify-center px-5 py-2.5
         rounded-md font-semibold text-sm uppercase tracking-widest
         bg-[#1E40AF] hover:bg-[#1E3A8A] text-white
         dark:bg-[#A9D6E5] dark:text-[#0D1B2A] dark:hover:bg-[#89C2D9]
         focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1E40AF] dark:focus:ring-[#A9D6E5]
         transition-all duration-300 ease-in-out disabled:opacity-60 disabled:cursor-not-allowed'
]) }}>
    {{ $slot }}
</button>
