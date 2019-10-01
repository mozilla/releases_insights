<!doctype html>
<html lang="en" dir="ltr">
  <head>
    <title><?php if ($show_title == true) {
    echo $page_title;
} ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php foreach ($css_files as $css_file):?>
    <link rel="stylesheet" href="/style/<?=$css_file?>" type="text/css" media="all" />
<?php endforeach?>
  </head>
<body id="<?= $page ?>" class="nojs">
    <div id="pagecontent">
      <?= $content ?>
    </div>
</body>
</html>
