<div>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Blog Monitor Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600">Track the latest posts from your favorite companies</p>
            </div>
            <div>
                <button wire:click="markAllAsRead" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="flex-shrink-0 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Mark All as Read
                </button>
            </div>
        </div>

        @if($companies->count() > 0)
            <div class="overflow-hidden border border-gray-300 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Company
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ticker
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stock Price
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                LBS
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                New Posts
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Post At
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($companies as $company)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            @if($company->favicon_url)
                                                <img src="{{ $company->favicon_url }}" alt="{{ $company->name }}" class="h-10 w-10 rounded-full object-cover">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-blue-600">
                                                        {{ strtoupper(substr($company->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $company->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $company->url }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($company->ticker)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $company->ticker }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($company->latest_stock_price)
                                        <div class="flex flex-col">
                                            <span class="font-medium">${{ number_format($company->latest_stock_price->price, 2) }}</span>
                                            @if($company->latest_stock_price->change_percent)
                                                <span class="text-xs {{ $company->latest_stock_price->change_percent >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $company->latest_stock_price->change_percent >= 0 ? '+' : '' }}{{ number_format($company->latest_stock_price->change_percent, 2) }}%
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">No data</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($company->latest_blog_score)
                                        <div class="flex flex-col">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium">{{ $company->latest_blog_score['score'] }}</span>
                                                @if($company->latest_blog_score['is_huge'])
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 animate-pulse">
                                                        HUGE
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">No score</span>
                                    @endif
                                </td>   
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @php
                                        $lastPost = $company->posts()->latest('published_at')->first();
                                    @endphp
                                    @if($lastPost)
                                        {{ $lastPost->published_at->diffForHumans() }}
                                    @else
                                        No posts
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('companies.show', $company) }}" 
                                        class="text-blue-600 hover:text-blue-900">
                                        View Posts
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
