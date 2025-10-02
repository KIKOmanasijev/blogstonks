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
                    <div>
                        <h1 class="text-3xl font-bold">Market</h1>
                        <p class="mt-1 text-green-100">Top rated</p>
                    </div>
                    <div>
                        <button wire:click="markAllAsRead" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-green-600 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="flex-shrink-0 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Mark All as Read
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Main Content Container -->
    <div class="container mx-auto px-6 py-6">
        <!-- Company Cards Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @foreach($companies->take(4) as $company)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 relative">
                    <!-- Company Logo and Name -->
                    <div class="flex items-center mb-3">
                        <div class="flex-shrink-0 h-8 w-8">
                            @if($company->favicon_url)
                                <img src="{{ $company->favicon_url }}" alt="{{ $company->name }}" class="h-8 w-8 rounded-full object-cover">
                            @else
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-xs font-medium text-blue-600">
                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">{{ $company->name }}</div>
                            <div class="text-xs text-gray-500">{{ $company->ticker ?? 'N/A' }}</div>
                        </div>
                    </div>
                    
                    <!-- Stock Price -->
                    @if($company->latest_stock_price)
                        <div class="text-lg font-semibold text-gray-900">${{ number_format($company->latest_stock_price->price, 2) }}</div>
                        @if($company->latest_stock_price->change_percent)
                            <div class="text-sm {{ $company->latest_stock_price->change_percent >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $company->latest_stock_price->change_percent >= 0 ? '+' : '' }}{{ number_format($company->latest_stock_price->change_percent, 2) }}%
                            </div>
                        @endif
                    @endif
                    
                    <!-- Circular LBS Score -->
                    <div class="absolute top-4 right-4">
                        @if($company->latest_blog_score)
                            @php
                                $score = $company->latest_blog_score['score'];
                                $percentage = min(100, max(0, $score));
                                
                                // Determine color based on score
                                $colorClass = 'text-blue-500';
                                if ($percentage >= 85) $colorClass = 'text-red-500';
                                elseif ($percentage >= 60) $colorClass = 'text-orange-500';
                                elseif ($percentage >= 40) $colorClass = 'text-yellow-500';
                                elseif ($percentage >= 20) $colorClass = 'text-green-500';
                            @endphp
                            
                            <div class="relative w-12 h-12">
                                <!-- Background circle -->
                                <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                                    <path class="text-gray-200" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                    <path class="{{ $colorClass }}" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="{{ $percentage }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                </svg>
                                <!-- Score text -->
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xs font-bold {{ $colorClass }}">{{ $score }}</span>
                                </div>
                            </div>
                        @else
                            <div class="w-12 h-12 rounded-full border-2 border-gray-200 flex items-center justify-center">
                                <span class="text-xs text-gray-400">-</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Table Section -->
        @if($companies->count() > 0)
            <div class="overflow-hidden border border-gray-300 md:rounded-lg">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <!-- Star column for unfollow -->
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Price
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                24H Change %
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                New Posts
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Post At
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rating
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <!-- More column -->
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($companies as $company)
                            <tr class="hover:bg-gray-50">
                                <!-- Star column for unfollow -->
                                <td class="pl-4 pr-0 py-4 whitespace-nowrap">
                                    <button wire:click="unfollowCompany({{ $company->id }})" 
                                        class="text-yellow-400 hover:text-yellow-600 transition-colors duration-200"
                                        title="Unfollow company">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </button>
                                </td>
                                
                                <!-- Company info with logo, name, and ticker -->
                                <td class="pl-2 pr-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            @if($company->favicon_url)
                                                <img src="{{ $company->favicon_url }}" alt="{{ $company->name }}" class="h-8 w-8 rounded-full object-cover">
                                            @else
                                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-blue-600">
                                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $company->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $company->ticker ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Last Price -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($company->latest_stock_price)
                                        <span class="font-medium">${{ number_format($company->latest_stock_price->price, 2) }}</span>
                                    @else
                                        <span class="text-sm text-gray-400">No data</span>
                                    @endif
                                </td>
                                
                                <!-- 24H Change % -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($company->latest_stock_price && $company->latest_stock_price->change_percent)
                                        <span class="{{ $company->latest_stock_price->change_percent >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $company->latest_stock_price->change_percent >= 0 ? '+' : '' }}{{ number_format($company->latest_stock_price->change_percent, 2) }}%
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400">-</span>
                                    @endif
                                </td>
                                
                                <!-- New Posts -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($company->new_posts_count > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ $company->new_posts_count }} new
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            0
                                        </span>
                                    @endif
                                </td>
                                
                                <!-- Last Post At -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($company->last_post_at)
                                        {{ $company->last_post_at->diffForHumans() }}
                                    @else
                                        No posts
                                    @endif
                                </td>
                                
                                <!-- Rating with score 0-100 -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($company->latest_blog_score)
                                        <div class="flex items-center space-x-2">
                                            <span class="font-medium">{{ $company->latest_blog_score['score'] }}</span>
                                            @if($company->latest_blog_score['is_huge'])
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 animate-pulse">
                                                    HUGE
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">No score</span>
                                    @endif
                                </td>
                                
                                <!-- More button -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('companies.show', $company) }}" 
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        More
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No companies configured</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding a company to monitor.</p>
            </div>
        @endif
    </div>
</div>
