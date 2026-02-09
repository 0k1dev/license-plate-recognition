@php
$hasLocation = !empty($lat) && !empty($lng);
$mapUrl = null;

// Prioritize the user-provided URL if it's a Google Maps Embed link
if (!empty($url) && (str_contains($url, 'google.com/maps/embed') || str_contains($url, 'google.com/maps/place'))) {
$mapUrl = $url;
// Handle regular place links by converting to embed
if (str_contains($url, '/place/')) {
// This is complex to convert reliably without an API key for places,
// but if it's an embed iframe src, it's already good.
// If it's a regular link, we fallback to lat/lng unless we can parse it easily.
$mapUrl = null;
}
}

// Fallback to constructing from Lat/Lng if no specific embed URL is matched or available
if (!$mapUrl && $hasLocation) {
$mapUrl = "https://maps.google.com/maps?q={$lat},{$lng}&t=&z=15&ie=UTF8&iwloc=&output=embed";
}
@endphp

<div class="w-full h-96 rounded-xl overflow-hidden border border-gray-300 dark:border-gray-700">
    @if($mapUrl)
    <iframe
        src="{{ $mapUrl }}"
        width="100%"
        height="100%"
        style="border:0;"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
    @elseif(!empty($url))
    <div class="flex items-center justify-center h-full bg-gray-100 dark:bg-gray-800 text-gray-500">
        <div class="text-center p-4">
            <x-heroicon-o-link class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>Link Maps không hỗ trợ nhúng trực tiếp.</p>
            <p class="text-xs mt-1">Hệ thống sẽ dùng tọa độ để hiển thị.</p>
        </div>
    </div>
    @else
    <div class="flex items-center justify-center h-full bg-gray-100 dark:bg-gray-800 text-gray-500">
        <div class="text-center">
            <x-heroicon-o-map class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>Vui lòng nhập tọa độ hoặc link Google Maps để xem trước</p>
        </div>
    </div>
    @endif
</div>