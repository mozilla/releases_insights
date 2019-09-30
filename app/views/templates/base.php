<?php
namespace ReleaseInsight;
?>
<!doctype html>

<html lang="en" dir="ltr">
  <head>
    <title><?php if ($show_title == true) { print $page_title . ' | ';} ?><?= $title_productname ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php foreach ($css_files as $css_file):?>
    <link rel="stylesheet" href="/style/<?= $css_file . $cache_bust ?>" type="text/css" media="all" />
<?php endforeach?>
  </head>
<body id="<?= $page ?>" class="nojs">
  ehemlpos

</body>
</html>
