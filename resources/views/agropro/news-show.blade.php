<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $newsItem->title }} · ABJ Builders</title>
    <link rel="stylesheet" href="{{ asset('templates/agropro/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/agropro/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/agropro/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/agropro/css/font-awesome.min.css') }}">
    <style>
        .project-detail-page { padding: 48px 0 80px; background: #fff; }
        .project-detail-back { display: inline-block; margin-bottom: 24px; color: #104735; font-weight: 700; }
        .project-detail-title { margin: 0 0 12px; color: #292928; font-size: 40px; font-weight: 700; }
        .project-detail-date { color: #6b7908; font-weight: 700; text-transform: uppercase; }
        .project-detail-lead { margin: 0 0 24px; color: #555; font-size: 18px; line-height: 1.7; }
        .project-detail-image { width: 100%; max-height: 540px; margin-bottom: 28px; border-radius: 6px; object-fit: cover; }
        .project-detail-meta { margin-bottom: 30px; padding: 22px; background: #f7f8f2; border-left: 4px solid #6b7908; }
        .project-detail-meta strong { display: block; margin-bottom: 4px; color: #292928; }
        .project-detail-copy { color: #444; font-size: 16px; line-height: 1.8; }
        .project-gallery { margin-top: 40px; }
        .project-gallery h2 { margin-bottom: 20px; font-size: 28px; font-weight: 700; }
        .project-gallery img { width: 100%; height: 240px; margin-bottom: 24px; border-radius: 6px; object-fit: cover; }
    </style>
</head>
<body class="main-layout">
    <main class="project-detail-page">
        <div class="container">
            <a class="project-detail-back" href="{{ route('home') }}">← Back to homepage</a>
            <article>
                <p class="project-detail-date">{{ $newsItem->published_at->format('d F Y') }}</p>
                <h1 class="project-detail-title">{{ $newsItem->title }}</h1>
                <p class="project-detail-lead">{{ $newsItem->description }}</p>
                <img class="project-detail-image" src="{{ $newsItem->image_url }}" alt="{{ $newsItem->title }}">

                <div class="row project-detail-meta">
                    <div class="col-md-6">
                        <strong>Project name</strong>
                        <span>{{ $newsItem->project_name }}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Project location</strong>
                        <span>{{ $newsItem->project_location }}</span>
                    </div>
                </div>

                <section class="project-detail-copy">
                    <h2>About the project</h2>
                    <p>{!! nl2br(e($newsItem->project_about)) !!}</p>
                </section>

                @if($newsItem->galleryImages->isNotEmpty())
                    <section class="project-gallery">
                        <h2>Project gallery</h2>
                        <div class="row">
                            @foreach($newsItem->galleryImages as $galleryImage)
                                <div class="col-sm-6 col-lg-4">
                                    <img src="{{ $galleryImage->image_url }}" alt="{{ $newsItem->project_name }} gallery image {{ $loop->iteration }}">
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </article>
        </div>
    </main>
</body>
</html>
