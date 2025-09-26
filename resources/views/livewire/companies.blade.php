<div>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Companies</h1>
            <p class="mt-1 text-sm text-gray-600">Discover and follow companies to track their latest news</p>
        </div>

        @if(session('message'))
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($companies->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @if($isLoading)
                    {{-- Skeleton Loaders --}}
                    @for($i = 0; $i < 6; $i++)
                        <div class="bg-white shadow rounded-lg p-6 flex flex-col h-full animate-pulse">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <div class="h-12 w-12 rounded-full bg-gray-200"></div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="h-4 bg-gray-200 rounded w-32 mb-2"></div>
                                        <div class="h-3 bg-gray-200 rounded w-24"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1">
                                <div class="space-y-3">
                                    <div class="h-3 bg-gray-200 rounded w-full"></div>
                                    <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                                
                                <div class="mt-4 space-y-2">
                                    <div class="h-4 bg-gray-200 rounded w-20"></div>
                                    <div class="h-4 bg-gray-200 rounded w-16"></div>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex space-x-2">
                                    <div class="flex-1 h-8 bg-gray-200 rounded"></div>
                                    <div class="flex-1 h-8 bg-gray-200 rounded"></div>
                                </div>
                            </div>
                        </div>
                    @endfor
                @else
                    @foreach($companies as $company)
                    <div class="bg-white shadow rounded-lg p-6 flex flex-col h-full">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                @if($company->favicon_url)
                                    <img src="{{ $company->favicon_url }}" alt="{{ $company->name }}" class="h-8 w-8 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="font-medium text-blue-600">
                                            {{ strtoupper(substr($company->name, 0, 2)) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $company->name }}</h3>
                                    <p class="text-sm text-gray-500">{{ $company->url }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Ticker</p>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $company->ticker ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Posts</p>
                                <p class="text-sm font-medium text-gray-900">{{ $company->posts_count }}</p>
                            </div>

                            @if($company->latest_stock_price)
                                <div>
                                    <p class="text-sm text-gray-500">Stock Price</p>
                                    <div class="flex items-center">
                                        <span class="text-lg font-medium text-gray-900">${{ number_format($company->latest_stock_price->price, 2) }}</span>
                                        @if($company->latest_stock_price->change_percent)
                                            <span class="ml-2 text-sm {{ $company->latest_stock_price->change_percent >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $company->latest_stock_price->change_percent >= 0 ? '+' : '' }}{{ number_format($company->latest_stock_price->change_percent, 2) }}%
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if($company->latest_blog_score)
                                <div>
                                    <p class="text-sm text-gray-500">Latest Blog Score</p>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-lg font-medium text-gray-900">{{ $company->latest_blog_score['score'] }}</span>
                                        @if($company->latest_blog_score['is_huge'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 animate-pulse">
                                                HUGE
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex space-x-2">
                                <a href="{{ route('companies.show', $company) }}" 
                                    class="flex-1 inline-flex items-center justify-center px-4 py-1 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    View Posts
                                </a>
                                <button wire:click="toggleFollow({{ $company->id }})" 
                                    class="flex-1 inline-flex items-center justify-center px-4 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white {{ $company->is_followed ? 'bg-red-500 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' }}">
                                    {{ $company->is_followed ? '- Unfollow' : '+ Follow' }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
            
            {{-- Pagination --}}
            @if($companies->hasPages())
                <div class="mt-8">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            @if($companies->onFirstPage())
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-500 bg-white cursor-not-allowed">
                                    Previous
                                </span>
                            @else
                                <button wire:click="previousPage" 
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 {{ $isLoading ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    @if($isLoading)
                                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @endif
                                    Previous
                                </button>
                            @endif

                            @if($companies->hasMorePages())
                                <button wire:click="nextPage" 
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 {{ $isLoading ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    @if($isLoading)
                                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @endif
                                    Next
                                </button>
                            @else
                                <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-500 bg-white cursor-not-allowed">
                                    Next
                                </span>
                            @endif
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ $companies->firstItem() }}</span>
                                    to
                                    <span class="font-medium">{{ $companies->lastItem() }}</span>
                                    of
                                    <span class="font-medium">{{ $companies->total() }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    @if($companies->onFirstPage())
                                        <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 cursor-not-allowed">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    @else
                                        <button wire:click="previousPage" 
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 {{ $isLoading ? 'opacity-50 cursor-not-allowed' : '' }}">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    @endif

                                    @foreach($companies->getUrlRange(1, $companies->lastPage()) as $page => $url)
                                        @if($page == $companies->currentPage())
                                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                                {{ $page }}
                                            </span>
                                        @else
                                            <button wire:click="gotoPage({{ $page }})" 
                                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 {{ $isLoading ? 'opacity-50 cursor-not-allowed' : '' }}">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    @endforeach

                                    @if($companies->hasMorePages())
                                        <button wire:click="nextPage" 
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 {{ $isLoading ? 'opacity-50 cursor-not-allowed' : '' }}">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 cursor-not-allowed">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    @endif
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <div class="text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No companies available</h3>
                <p class="mt-1 text-sm text-gray-500">Companies will appear here once they are added to the system.</p>
            </div>
        @endif
    </div>
</div>