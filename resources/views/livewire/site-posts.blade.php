<div>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $company->name }} Blog Posts</h1>
                <p class="mt-1 text-sm text-gray-600">All historic posts from {{ $company->url }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Back to Dashboard
                </a>
                <button wire:click="markAsViewed"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Mark All as Viewed
                </button>
            </div>
        </div>

        @if($posts->count() > 0)
        <div class="space-y-4 grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        <p class="text-gray-600 mb-3 line-clamp-5">
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
                         @if($post->is_huge_news)
                         <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No posts found</h3>
            <p class="mt-1 text-sm text-gray-500">Posts will appear here once the scraper runs.</p>
        </div>
        @endif
    </div>
</div>