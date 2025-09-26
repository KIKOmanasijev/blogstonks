<div>
    <!-- Green Header Section -->
    <div class="relative bg-gradient-to-r from-green-600 to-green-700 text-white overflow-hidden pt-16">
        <!-- Low-opacity shapes -->
        <div class="absolute inset-0">
            <div class="absolute top-0 left-0 w-64 h-64 bg-white opacity-10 rounded-full -translate-x-32 -translate-y-32"></div>
            <div class="absolute top-0 right-0 w-48 h-48 bg-white opacity-10 rounded-full translate-x-24 -translate-y-24"></div>
            <div class="absolute bottom-0 left-1/4 w-32 h-32 bg-white opacity-10 rounded-full translate-y-16"></div>
            <div class="absolute bottom-0 right-1/3 w-40 h-40 bg-white opacity-10 rounded-full translate-y-20"></div>
        </div>
        
        <div class="relative z-10 py-8">
            <div class="container mx-auto px-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-16 w-16">
                            @if($company->favicon_url)
                                <img src="{{ $company->favicon_url }}" alt="{{ $company->name }}" class="h-16 w-16 rounded-full object-cover">
                            @else
                                <div class="h-16 w-16 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                                    <span class="text-2xl font-medium text-white">
                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="ml-6">
                            <h1 class="text-3xl font-bold">{{ $company->name }}</h1>
                            <p class="mt-1 text-lg text-green-100">Latest News & Updates</p>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-sm text-green-200">Website</p>
                                    <a href="{{ $company->url }}" target="_blank" class="text-sm text-white hover:text-green-100 underline">
                                        {{ $company->url }}
                                    </a>
                                </div>
                                <div>
                                    <p class="text-sm text-green-200">Ticker</p>
                                    <p class="text-sm text-white">{{ $company->ticker ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-green-200">Posts</p>
                                    <p class="text-sm text-white">{{ $company->posts_count }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <button wire:click="toggleFollow({{ $company->id }})" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-green-600 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            {{ $company->is_followed ? '- Unfollow' : '+ Follow' }}
                        </button>
                        <a href="{{ route('dashboard') }}" 
                            class="inline-flex items-center px-4 py-2 border border-white border-opacity-30 rounded-md shadow-sm text-sm font-medium text-white bg-transparent hover:bg-white hover:bg-opacity-10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white">
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="container mx-auto px-6 py-6">

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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
            <!-- News Section -->
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">News from {{ $company->name }}</h2>
                    <button wire:click="markAsViewed"
                        class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Mark All as Viewed
                    </button>
                </div>

                @if($posts->count() > 0)
                    <div class="space-y-4">
                        @foreach($posts as $post)
                            <div class="bg-white shadow rounded-lg p-6 flex flex-col h-full">
                                <div class="flex-1">
                                    <div class="mb-2">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <a href="{{ $post->url }}"
                                                target="_blank"
                                                class="hover:text-blue-600 transition-colors">
                                                {{ $post->title }}
                                            </a>
                                        </h3>
                                    </div>
                                    
                                    @if($post->content)
                                        <p class="text-gray-600 mb-3 line-clamp-4">
                                            {{ Str::limit(strip_tags($post->content), 500) }}
                                        </p>
                                    @endif
                                    
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Published {{ $post->published_at->format('M j, Y') }}
                                        <span class="mx-2">•</span>
                                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $post->published_at->diffForHumans() }}
                                        
                                        @if($post->isClassified())
                                            <span class="mx-2">•</span>
                                            <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Scored {{ $post->scored_at->diffForHumans() }}
                                        @endif
                                    </div>
                                    
                                    @if($post->isClassified() && $post->reasoning)
                                    <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                        <div class="flex items-start">
                                            <svg class="flex-shrink-0 mr-2 h-4 w-4 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                            </svg>
                                            <div>
                                                <h4 class="text-sm font-medium text-blue-900 mb-1">AI Reasoning:</h4>
                                                <p class="text-sm text-blue-800">{{ $post->reasoning }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <!-- Bottom section: Score, HUGE news, and Read More button -->
                                <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between">
                                    @if($post->isClassified())
                                    <div class="flex items-center space-x-2">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-sm font-medium text-gray-700">Score:</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium 
                                                            {{ $post->importance_score >= 80 ? 'bg-red-100 text-red-800' : 
                                                               ($post->importance_score >= 60 ? 'bg-orange-100 text-orange-800' : 
                                                               ($post->importance_score >= 40 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                                {{ $post->importance_score }}
                                            </span>
                                        </div>
                                        @if($post->is_huge_news && $post->published_at->diffInHours(now()) <= 12)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 animate-pulse">
                                            HUGE NEWS
                                        </span>
                                        @endif
                                    </div>
                                    @else
                                    <div>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                            Not Scored
                                        </span>
                                    </div>
                                    @endif

                                    <div class="flex-shrink-0">
                                        <a href="{{ $post->url }}"
                                            target="_blank"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Read More
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No posts found</h3>
                        <p class="mt-1 text-sm text-gray-500">No blog posts have been scraped for this company yet.</p>
                    </div>
                @endif
            </div>

            <!-- Stock Data Widgets -->
            <div class="space-y-6">
                <!-- Stock Price Widget -->
                @if($company->latest_stock_price)
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Stock Price</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Current Price</span>
                                <span class="text-lg font-semibold text-gray-900">${{ number_format($company->latest_stock_price->price, 2) }}</span>
                            </div>
                            @if($company->latest_stock_price->change_percent)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Change</span>
                                    <span class="text-sm {{ $company->latest_stock_price->change_percent >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $company->latest_stock_price->change_percent >= 0 ? '+' : '' }}{{ number_format($company->latest_stock_price->change_percent, 2) }}%
                                    </span>
                                </div>
                            @endif
                            @if($company->latest_stock_price->open)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Open</span>
                                    <span class="text-sm text-gray-900">${{ number_format($company->latest_stock_price->open, 2) }}</span>
                                </div>
                            @endif
                            @if($company->latest_stock_price->high)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">High</span>
                                    <span class="text-sm text-gray-900">${{ number_format($company->latest_stock_price->high, 2) }}</span>
                                </div>
                            @endif
                            @if($company->latest_stock_price->low)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Low</span>
                                    <span class="text-sm text-gray-900">${{ number_format($company->latest_stock_price->low, 2) }}</span>
                                </div>
                            @endif
                            @if($company->latest_stock_price->previous_close)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Previous Close</span>
                                    <span class="text-sm text-gray-900">${{ number_format($company->latest_stock_price->previous_close, 2) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Updated</span>
                                <span class="text-sm text-gray-500">{{ $company->latest_stock_price->price_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Stock Price</h3>
                        <div class="text-center py-4">
                            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No stock data available</p>
                        </div>
                    </div>
                @endif

                <!-- Blog Score Widget -->
                @if($company->latest_blog_score)
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Latest Blog Score</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Score</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg font-semibold text-gray-900">{{ $company->latest_blog_score['score'] }}</span>
                                    @if($company->latest_blog_score['is_huge'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            HUGE
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Scored</span>
                                <span class="text-sm text-gray-500">{{ $company->latest_blog_score['scored_at']->diffForHumans() }}</span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p class="truncate">{{ Str::limit($company->latest_blog_score['post_title'], 50) }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Company Stats Widget -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Company Stats</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Total Posts</span>
                            <span class="text-sm font-medium text-gray-900">{{ $company->posts()->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Last Scraped</span>
                            <span class="text-sm text-gray-500">{{ $company->last_scraped_at ? $company->last_scraped_at->diffForHumans() : 'Never' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Status</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $company->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $company->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>