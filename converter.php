<?php
session_start();
//var_dump($_FILES);
try {
    if ($_FILES['export']['error'] === 0) {
        if ($_FILES['export']['size'] > 50000000) {
            throw new RuntimeException('Exceeded Filesize Limit of 5 MB.');
        }
        $exportFile = $_FILES['export'];
        unset($_FILES['export']);
        $fileType = $exportFile['type'];
        $arr = explode('.', $exportFile['name']);
        $extension = end($arr);
        $goodExts = array("csv");
        $goodTypes = array("text/csv", "application/vnd.ms-excel");
        if (in_array($extension, $goodExts) === false || in_array($fileType, $goodTypes) === false) {
            throw new Exception("This page only accepts .csv files, please upload the correct format. Format of your file: ." . $extension);
        }
        $fileData = array();
        $handle = fopen("php://temp", "w+");
        $contents = file_get_contents($exportFile['tmp_name']);
        fputs($handle, $contents);
        rewind($handle);
        while (!feof($handle)) {
            $fileData[] = fgetcsv($handle, 100000000, ",", '"');
        }
        fclose($handle);
        //var_dump($fileData);
        $data = $toProcess = $ui = array();
        $codesToCapture = array('M05','M08','M11','M12','M15','M17','M18','M19','M26');
        $codesToSkip = array('M20','M21','M22','M23','M24','M25');
        $codesToCalculate = array('E01','E07','E18','E33','E35','E37','E38','E40','E42');
        foreach($fileData as $key => $line){
            $eeNum = trim($line[0]);
            $earnCode = trim($line[2]);
            if(in_array($earnCode, $codesToCapture)){
                $toProcess[] = $eeNum;
            }
        }
        $toProcess = array_unique($toProcess);
        //var_dump($toProcess);

        foreach($toProcess as $eeNum){
            foreach($fileData as $key => $line){
                if($eeNum === trim($line[0])){
                    $data[$eeNum][] = array("EE NUM" => trim($line[0]), "EE NAME" => trim($line[1]), "EARNING CODE" => trim($line[2]), "EARNING TITLE" => trim($line[3]), "HOURS" => (float) trim($line[4]), "PROGRAM" => trim($line[5]));
                }
            }
        }
        //var_dump($data);

        $eeHoursWorked = $mHours = $filteredData = $mCodes = array();
        foreach($data as $eeNum => $array) {
            //Gather and Total the E Code Hours - sum array after getting hours
            $eeHoursWorked[$eeNum] = array_sum(array_map(function($element) use ($codesToCalculate) {
                if (in_array($element["EARNING CODE"], $codesToCalculate)) {
                    return $element["HOURS"];
                }else {
                    return 0.0;
                }
            }, $array));
            //Gather the M Code Hours - rekey array after filtering out 0.0s
            $mHours[$eeNum] = array_values(array_filter(array_map(function($element) use ($codesToCapture) {
                if (in_array($element["EARNING CODE"], $codesToCapture)) {
                    return $element["HOURS"];
                }else {
                    return 0.0;
                }
            }, $array)));
            // filter out m code lines
            $filteredData[$eeNum] = array_values(array_filter(array_map(function($element) use ($codesToCalculate){
                if(in_array($element["EARNING CODE"], $codesToCalculate)){
                    return $element;
                } else {
                    return null;
                }
            }, $array)));
            //collect mCodes (rekey(filter(apply function to each element of array))
            $mCodes[$eeNum]= array_values(array_filter(array_map(function($element) use ($codesToCapture){
               if(in_array($element["EARNING CODE"], $codesToCapture)) {
                   return $element["EARNING CODE"];
               }else {
                   return null;
               }
            },$array)));
        }
        //var_dump($eeHoursWorked, "MHOURS", $mHours, "FILTEREDDATA", $filteredData, "MCODES", $mCodes);

        $calculationData = array();
        foreach($filteredData as $eeNum => $array){
            //var_dump($eeNum);
            foreach($array as $key => $line){
                $hours = $line["HOURS"];
                $totalHours = $eeHoursWorked[$eeNum];
                //var_dump($hours, $totalHours);
                $percentage = $hours / $totalHours;
                //var_dump($percentage);
                $program = $line["PROGRAM"];
                $calculationData[$eeNum][] = array("PERCENTAGE" => round($percentage, 2), "PROGRAM" => $program, "EE NAME" => $line["EE NAME"]);
            }
        }
        //var_dump("CALCDATA", $calculationData);
        $warn = $calculationData2 = array();
        foreach($mHours as $eeNum => $array){
            //var_dump($array);
            for ($i = 0; $i < count($mHours[$eeNum]); $i++) {
                $hoursToDistribute = $mHours[$eeNum][$i];
                $earningCode = $mCodes[$eeNum][$i];
                $arr = $calculationData[$eeNum] ? $calculationData[$eeNum] : null;
                if($arr === null){
                    $warn[$eeNum] = '<p>Warning: Employee Number => '. $eeNum . ': No E Codes found only M Codes</p>';
                    continue;
                }
                //var_dump($eeNum, $hoursToDistribute);
                //var_dump("ARR", $arr);
                $calculationData2[$eeNum][] = array_map(function($element) use ($hoursToDistribute, $earningCode){
                    $element["ACCRUAL"] = round(($element["PERCENTAGE"] * $hoursToDistribute), 2);
                    $element["HOURS TO DISTRIBUTE"] = $hoursToDistribute;
                    $element["EARNING CODE"] = str_replace("M", "E", $earningCode);
                    //var_dump($element);
                    return $element;
                }, $arr);
            }
        }
        //var_dump("CALCDATA2", $calculationData2);

        foreach($calculationData2 as $eeNum => $arr){
            $ui[] = '<div class="border">';
            foreach($arr as $array){
            $eeName = $array[0]["EE NAME"];
            $totalHours = $array[0]["HOURS TO DISTRIBUTE"];
            $ui[] = <<<HTML
                <div class="card">  
                    <fieldset>
                    <legend><h4>$eeNum | $eeName</h4></legend>
                    <h5>Total Hours to Distribute ($totalHours)</h5>
                    <table class="table">
                        <thead>
                            <tr><th>Program</th><th>Percentage (%)</th><th>Distributed (Hours)</th><th>Earning Code</th></tr>
                        </thead>
                        <tbody>
HTML;

            foreach($array as $key => $line){

                $program = $line["PROGRAM"];
                $percentage = $line["PERCENTAGE"];
                $accrual = $line["ACCRUAL"];
                $earningCode = $line["EARNING CODE"];

                $ui[] = <<<HTML
                            <tr><td>$program</td><td>$percentage</td><td>$accrual</td><td>$earningCode</td></tr>
HTML;
            }
            $ui[] = <<<HTML
                        </tbody>
                    </table>
                    </fieldset>
                </div>
HTML;
        }
            $ui[] = '</div>';
        }

        $exportHeaders = array("Key", "Name", "E_Holiday_Hours", "E_Other $$_Hours", "E_Training_Hours", "E_Jury Duty_Hours", "E_Funeral Leave_Hours", "E_Extended Illnes_Hours", "E_PTO_Hours", "E_Event_Hours", "E_Other-WRI_Hours", "LaborValue3");
        $indexes = array('E05'=> 2, 'E08' => 3, 'E11' => 4,'E12' => 5, 'E15' => 6, 'E17' => 7, 'E18' => 8,'E19' => 9, 'E26' => 10);
        $values = array();
        foreach($calculationData2 as $eeNum => $arr) {
            foreach ($arr as $array) {
                foreach ($array as $key => $line) {
                    $index = $line["EARNING CODE"];
                    $column = $indexes[$index];
                    $values = array($eeNum, $line["EE NAME"], '', '', '', '', '', '', '', '', '', $line["PROGRAM"]);
                    $values[$column] = (string)$line["ACCRUAL"];
                    $output[] = $values;
                }
            }
        }
        //var_dump($output);
        sort($output);
        array_unshift($output,  $exportHeaders);

        $today = new DateTime('now');
        $uniqueId = $today->format("m-d-y_His");
        $fileName = 'Alivine_iSolved_Import-'.$uniqueId.'.csv';
        $directory = 'Files/';
        $handle = fopen($directory.$fileName, 'w+');
        if(!is_array($output) || empty($output)) {
            throw new Exception("Output is not correctly formatted. Report this error to Lisa and send along the file used for upload.");
        }
        foreach ($output as $line) {
            fputcsv($handle, $line);
        }
        fclose($handle);
        $_SESSION['fileName'] = $directory.$fileName;
        $_SESSION['output'] = array( "message" => "<p>iSolved Import File Successfully created!</p>", "link" => "<p><a href='./download.php'>Download Created File</a></p>", "ui" => $ui, "warn" => $warn);
        $_SESSION['data'] = $data;
        header("Location: index.php");
    } else {
        throw new Exception('File Upload Error: ' . $_FILES['export']['error']);
    }
}catch(Exception $e){
    $_SESSION['error'] = $e->getMessage();
    header("Location: index.php");
}
?>