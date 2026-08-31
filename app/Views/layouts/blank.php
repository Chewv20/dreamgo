<!doctype html>
<html lang="es-MX">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($meta['title'] ?? 'Dream Go', ENT_QUOTES, 'UTF-8') ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="/assets/icons/icon-96.png" type="image/png">
<link rel="stylesheet" href="<?= htmlspecialchars(\App\Helpers\Asset::url('/assets/css/site.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(\App\Helpers\Asset::url('/assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<?= $content ?>
</body>
</html>
