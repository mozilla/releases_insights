<?php
    // This is a pure old-schol PHP template as the waiting page doesn't depend on Twig
?>
<!DOCTYPE html>
<html class="waitingpage">
<head>
    <meta charset="utf-8" />
    <style nonce="<?=NONCE?>">
        html.waitingpage, html.waitingpage body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: sans-serif;
            background-color: #2a0c55;
        }

        .waitingpage .container {
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .waitingpage .item {
            min-width: 20%;
            text-align: center;
            color:black;
            background-color: white;
            padding: 10px 0;
            border-radius: 10px;
            font-weight: normal;
            font-size: 18px;
            line-height: 30px;
            color: rgb(33, 37, 41);
        }

        .waitingpage #html-spinner {
            width: 30px;
            height: 30px;
            border: 4px solid #0dcaf0;
            border-top: 4px solid white;
            border-radius: 50%;
            margin-right: 15px;
        }

        .waitingpage #html-spinner {
            transition-property: transform;
            animation-name: rotate;
            animation-duration: 1.2s;
            animation-iteration-count: infinite;
            animation-timing-function: linear;
        }

        @keyframes rotate {
            from {transform: rotate(0deg);}
            to {transform: rotate(360deg);}
        }

        /* spinner positioning */
        .waitingpage #html-spinner {
            display: inline-block;
            vertical-align: middle;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="item"><div id="html-spinner"></div>Data is being processed, please wait…</div>
    </div>
</body>
</html>
<?php
// Hack, fill the buffer fully to make sure the flush() method will work
echo str_repeat(' ', 4096);
flush();