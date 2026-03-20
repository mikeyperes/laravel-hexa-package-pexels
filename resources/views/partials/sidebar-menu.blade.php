@auth
{{-- ── Pexels ── --}}
<div class="pt-3 pb-1 px-3">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pexels</p>
</div>

@if(Route::has('pexels.index'))
<a href="{{ route('pexels.index') }}"
   class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->routeIs('pexels.index') ? 'sidebar-active' : 'sidebar-hover' }}">
    <svg class="w-4 h-4 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    Raw Dev
</a>
@endif
@endauth
