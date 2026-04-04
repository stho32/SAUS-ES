<?php

return [
    'min_votes_required' => env('SAUS_MIN_VOTES_REQUIRED', 4),
    'upload_path' => env('SAUS_UPLOAD_PATH', 'php/uploads/tickets'),
    'news_upload_path' => env('SAUS_NEWS_UPLOAD_PATH', 'php/uploads/news'),

    // Route prefixes — both areas as sibling subdirectories,
    // matching production layout: /saus/ and /public_information_saus/
    // A crawler finding one cannot discover the other.
    'admin_route_prefix' => env('SAUS_ADMIN_ROUTE_PREFIX', 'saus'),
    'public_route_prefix' => env('SAUS_PUBLIC_ROUTE_PREFIX', 'public_information_saus'),

    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
    'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif'],
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'max_image_size' => 2 * 1024 * 1024, // 2MB
    'thumbnail_width' => 200,
];
