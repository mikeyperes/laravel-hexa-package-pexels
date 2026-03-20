@extends('layouts.app')

@section('title', 'Pexels — Raw Dev')
@section('header', 'Pexels — Raw Dev')

@section('content')
<div x-data="pexelsRaw()" x-init="init()">

    {{-- ═══ API Key Status ═══ --}}
    <div class="mb-6 flex items-center gap-3 bg-white rounded-lg border border-gray-200 px-4 py-3">
        <span class="text-sm font-medium text-gray-700">API Key:</span>
        <span class="font-mono text-sm text-gray-500" x-text="apiKeyMasked"></span>
        <span class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full" :class="apiKeyConfigured ? 'bg-green-500' : 'bg-yellow-400'"></span>
            <span class="text-xs" :class="apiKeyConfigured ? 'text-green-700' : 'text-yellow-700'" x-text="apiKeyConfigured ? 'Configured' : 'Not configured'"></span>
        </span>
    </div>

    {{-- ═══ Functions Index ═══ --}}
    <div class="mb-6 rounded-lg overflow-hidden border border-gray-700">
        <div class="bg-gray-900 px-4 py-3 border-b border-gray-700">
            <h2 class="text-sm font-semibold text-gray-200">Functions Index</h2>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-800 text-gray-400 text-xs uppercase tracking-wider">
                    <th class="px-4 py-2 text-left">Method</th>
                    <th class="px-4 py-2 text-left">Description</th>
                    <th class="px-4 py-2 text-left">Endpoint</th>
                    <th class="px-4 py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="bg-gray-900 text-gray-300 divide-y divide-gray-800">
                <tr>
                    <td class="px-4 py-2 font-mono text-green-400">testApiKey()</td>
                    <td class="px-4 py-2">Test the Pexels API key validity</td>
                    <td class="px-4 py-2 font-mono text-blue-400">Internal</td>
                    <td class="px-4 py-2"><span class="text-green-400">Available</span></td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-mono text-green-400">searchPhotos()</td>
                    <td class="px-4 py-2">Search for photos by keyword</td>
                    <td class="px-4 py-2 font-mono text-blue-400">POST /pexels/search</td>
                    <td class="px-4 py-2"><span class="text-green-400">Available</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ═══ Search Section ═══ --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Search Photos</h3>
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Query</label>
                <input type="text" x-model="query" @keydown.enter="searchPhotos()" placeholder="e.g. nature, office, technology..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div class="w-28">
                <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                <input type="number" x-model="perPage" min="1" max="80"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <button @click="searchPhotos()" :disabled="searching || !query.trim()"
                    class="btn-primary text-white px-5 py-2 rounded-lg text-sm font-medium disabled:opacity-50 flex items-center gap-2">
                <svg x-show="searching" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span x-text="searching ? 'Searching...' : 'Search'"></span>
            </button>
        </div>
    </div>

    {{-- ═══ Result Banner ═══ --}}
    <template x-if="resultMessage">
        <div class="mb-6 px-4 py-3 rounded-lg flex items-center gap-2 text-sm"
             :class="resultSuccess ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'">
            <svg x-show="resultSuccess" class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <svg x-show="!resultSuccess" class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span x-text="resultMessage"></span>
        </div>
    </template>

    {{-- ═══ Results Grid ═══ --}}
    <template x-if="photos.length > 0">
        <div>
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm text-gray-600">
                    Showing <span class="font-semibold" x-text="photos.length"></span> of <span class="font-semibold" x-text="totalResults.toLocaleString()"></span> results
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <template x-for="photo in photos" :key="photo.id">
                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="aspect-video bg-gray-100 overflow-hidden">
                            <img :src="photo.url_thumb" :alt="photo.alt" class="w-full h-full object-cover" loading="lazy">
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-800 break-words" x-text="photo.photographer"></p>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="photo.width + ' x ' + photo.height"></p>
                            <a :href="photo.pexels_url" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-800 mt-1.5">
                                View on Pexels
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>

@push('scripts')
<script>
function pexelsRaw() {
    return {
        query: '',
        perPage: 15,
        searching: false,
        photos: [],
        totalResults: 0,
        resultMessage: '',
        resultSuccess: false,
        apiKeyConfigured: false,
        apiKeyMasked: 'Loading...',

        init() {
            const key = @json(\hexa_core\Models\Setting::getValue('pexels_api_key', ''));
            if (key && key.length > 0) {
                this.apiKeyConfigured = true;
                this.apiKeyMasked = key.substring(0, 4) + '••••••••' + key.substring(key.length - 4);
            } else {
                this.apiKeyConfigured = false;
                this.apiKeyMasked = 'Not set';
            }
        },

        async searchPhotos() {
            if (!this.query.trim() || this.searching) return;
            this.searching = true;
            this.resultMessage = '';
            this.photos = [];

            try {
                const resp = await fetch('{{ route("pexels.search") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        query: this.query,
                        per_page: parseInt(this.perPage) || 15,
                        page: 1,
                    }),
                });

                const data = await resp.json();
                this.resultSuccess = data.success;
                this.resultMessage = data.message || (data.success ? 'Done.' : 'Request failed.');

                if (data.success && data.data) {
                    this.photos = data.data.photos || [];
                    this.totalResults = data.data.total || 0;
                }
            } catch (err) {
                this.resultSuccess = false;
                this.resultMessage = 'Network error: ' + err.message;
            } finally {
                this.searching = false;
            }
        },
    };
}
</script>
@endpush
@endsection
