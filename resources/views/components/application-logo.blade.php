@php
    $customLogoUrl = brand_logo_url();
@endphp

@if($customLogoUrl)
    <img src="{{ $customLogoUrl }}" alt="{{ brand_name() }}" {{ $attributes->merge(['class' => 'object-contain rounded-lg']) }} />
@else
    <svg viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
        <rect width="256" height="256" rx="54" fill="#142B4A"/>
        <rect x="34" y="34" width="188" height="188" rx="40" fill="#2563EB"/>
        <path d="M76 77h104v22H98v24h72v22H98v34H76z" fill="#fff"/>
        <path d="M147 143h33v36h-33z" fill="#10B981"/>
        <path d="M158 58h22v56h-22z" fill="#10B981"/>
    </svg>
@endif
