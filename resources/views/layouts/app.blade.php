<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Director Search</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-light bg-light mb-4">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="{{ route('directors.search') }}">Director Search</a>
  </div>
</nav>
<main class="container mb-5">
  @yield('content')
</main>
</body>
</html>
