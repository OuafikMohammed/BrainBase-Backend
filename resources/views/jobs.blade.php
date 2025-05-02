<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body>
    {{-- FOREACH --}}
    {{-- @if (!empty($jobs))
    <ul>
        @foreach($jobs as $job)
            <li>{{ $job }}</li>
        @endforeach
    </ul>
    @endif --}}
    {{-- FORELSE --}}
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-4">{{ $title }}</h1>
        <ul>
            @forelse ($jobs as $job)
                <li class="text-red-500 font-semibold">{{ $job['title'] }}</li>
                <li class="ml-4 text-gray-700">{{ $job['description'] }}</li>
            @empty
                <li>No jobs available</li>
            @endforelse
        </ul>
    </div>
</body>
</html>