<!DOCTYPE html>
<html>
<head>
    <title>Aliviane Percentage Distribution Tool</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.0/css/bootstrap.min.css" integrity="sha384-PDle/QlgIONtM1aqA2Qemk5gPOE7wFq8+Em+G/hmo5Iq0CCmYZLv3fVRDJ4MMwEA" crossorigin="anonymous">
    <script
        src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>
    <style>
        .container {
            padding: 30px;
            width: 1170px;
            margin: 0 auto;
            text-align: center;
            list-style-position: inside;
            background-color: lightblue;
        }
        .error {
            color: red;
        }
        span {
            padding: 8px;
        }
        #output {

        }
        .table {
            border: 3px solid white;
            margin: 8px 0;
        }
        .card {
            margin: 24px 0;
            border: none;
        }
        legend {
            background-color: lightskyblue;
        }
        h4 {
            padding: 8px;
        }
        .border {
            border: 3px solid blueviolet;
        }
    </style>
</head>
<body>
<div class="container">
    <form action="./converter.php" method="post" enctype="multipart/form-data">
        <h1>Aliviane Percentage Distribution Tool</h1>
        <label for="export"> Upload iSolved Export:
            <input type="file"/ id="export" name="export">
        </label>
        <input type="submit" value="Perform Calculations and Create iSolved Import"/>
    </form>
    <div id="output">
        <?php
        session_start();
        if(isset($_SESSION['output'])) {
            echo $_SESSION['output']['message'];
            echo $_SESSION['output']['link'];
            echo '<p><a href="./clear.php">Reset Form</a></p>';
            foreach($_SESSION['output']['warn'] as $value) {
                echo $value;
            }
            foreach ($_SESSION['output']['ui'] as $value) {
                //var_dump($value);
                echo $value;
            }
        }

        if(isset($_SESSION['error'])){
            echo '<p class="error">'.$_SESSION['error'].'</p>';
            echo '<p><a href="./clear.php">Reset Form</a></p>';
        }
        ?>
    </div>
</div>
</body>
</html>