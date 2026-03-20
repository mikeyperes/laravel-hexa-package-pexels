@if(auth()->check())
@once('sandbox-sidebar-header')
<p class="text-xs text-gray-600 uppercase tracking-wider pt-4 pb-1 px-3">Sandbox</p>
@endonce

<a href="{{ route('pexels.index') }}"
   class="flex items-center px-3 py-2 rounded-lg text-sm pl-6 {{ request()->is('raw-pexels*') ? 'sidebar-active' : 'sidebar-hover' }}">
    Pexels
</a>
@endif
