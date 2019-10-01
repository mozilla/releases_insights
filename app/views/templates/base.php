<!doctype html>
<html lang="en" dir="ltr">
  <head>
    <title><?=$page_title?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php foreach ($css_files as $css_file):?>
    <link rel="stylesheet" href="/style/<?=$css_file?>" type="text/css" media="all" />
<?php endforeach?>
  </head>
<body id="<?=$css_page_id?>">
    <div id="pagecontent">
      <?=$content?>
    </div>
</body>
</html>
