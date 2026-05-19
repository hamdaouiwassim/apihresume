<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $pageUrl }}">

    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $pageUrl }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:image:alt" content="{{ $post->title }}">
    <meta property="og:site_name" content="HResume">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $pageUrl }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">

    <meta http-equiv="refresh" content="0;url={{ $pageUrl }}">
</head>
<body>
    <p><a href="{{ $pageUrl }}">{{ $post->title }}</a></p>
</body>
</html>
