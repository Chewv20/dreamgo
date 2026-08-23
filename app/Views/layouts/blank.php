<!doctype html>
<html lang="es-MX">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($meta['title'] ?? 'Dream Go', ENT_QUOTES, 'UTF-8') ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="/assets/icons/icon-96.png" type="image/png">
<link rel="stylesheet" href="/assets/css/site.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?= $content ?>
</body>
</html>
